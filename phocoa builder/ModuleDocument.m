//  MyDocument.m
//  PHOCOA Builder
//
//  Created by Alan Pinstein on 10/26/05.
//  Copyright __MyCompanyName__ 2005 . All rights reserved.

#import "ModuleDocument.h"
#import "TaskWrapper.h"

@interface ModuleDocument (Private)
- (void)setModule:(NSManagedObject *)aModule;
@end

@implementation ModuleDocument

- (id)init 
{
    self = [super init];
    if (self != nil) {
        // initialization code
		module = nil;
    }
    return self;
}

// this will be called with "New" -- need to bootstrap a new module
- (id)initWithType:(NSString *)type error:(NSError **)error {
    self = [super initWithType:type error:error];
    if (self != nil) {
		NSManagedObjectContext *managedObjectContext = [self managedObjectContext];
		[self setModule:[NSEntityDescription insertNewObjectForEntityForName:@"Module"
                                                      inManagedObjectContext:managedObjectContext]];
		[managedObjectContext processPendingChanges];
		[[managedObjectContext undoManager] removeAllActions];
		[self updateChangeCount:NSChangeCleared];
    }
    return self;
}

- (void)setModule:(NSManagedObject *)aModule
{
    if (module != aModule) {
        [module release];
        module = [aModule retain];
    }
}

- (NSManagedObject*) module
{
	return module;
}

- (NSString*) getXMLRepresentationOfPHPFile: (NSString*) path
{
    NSUserDefaults *prefs = [NSUserDefaults standardUserDefaults];
    
    // use the bundled PHP serializer to turn the array into XML, which we can parse easily.
    NSString *phpSerializerPath;
    NSBundle *thisBundle = [NSBundle bundleForClass:[self class]];
    NSString    *xml;
    if (phpSerializerPath = [thisBundle pathForResource:@"phocoa_serializer" ofType:@"php"])
    {
        NSArray *args = [NSArray arrayWithObjects: phpSerializerPath, @"-f", path, nil];
        
        TaskWrapper     *theTask = [TaskWrapper runShellCommand: [prefs stringForKey: @"php_executable"] arguments: args];        
        if ([theTask terminationStatus] == 0)
        {
            xml = [theTask stdOut];
        }
        else
        {
            NSLog(@"Error with PHP subtask");
            NSAlert*    errAlert = [[[NSAlert alloc] init] autorelease];
            // get stdErr too... show that in case of parse errors
            [errAlert setMessageText: [NSString stringWithFormat: @"PHP sub-task failed:\n\n%@\n\nMake sure that php is installed at:\n\n%@\n\nand that the following PEAR packages are installed: Console_GetOpt, XML_Serializer.", [theTask stdErr], [prefs stringForKey: @"php_executable"]] ];
            [errAlert runModal];
            [NSException raise: @"InvalidSetupException" format: @"Couldn't execute PHP subtask."];
        }
    }
//    NSLog(@"Got data:\n%@", xml);
    return xml;
}

- (void) makeWindowControllers
{
	// make main window
	mainWindow = [[ModuleMainWindowController alloc] init];
	[mainWindow autorelease];
	[self addWindowController: mainWindow];
}

- (NSString *)windowNibName 
{
    return @"MyDocument";
}

- (void)windowControllerDidLoadNib:(NSWindowController *)windowController 
{
    [super windowControllerDidLoadNib:windowController];
    // user interface preparation code
}

- (void)dealloc {
    [self setModule:nil];
    [super dealloc];
}

#pragma mark -- Loading & Saving --

// we use saveDocument because we are core data, but we don't want the normal NSPersistentDocument save handler to run
// instead, we just grab the menu action and do the right thing for us, which is output a bunch of config files
- (IBAction)saveDocument:(id)sender
{  
    // did we open a doc or are we saving a new doc?
    if ([self fileURL] == nil)
    {
        NSSavePanel *savePanel = [NSSavePanel savePanel];
        int result = [savePanel runModal];
        if (result == NSFileHandlingPanelCancelButton)
        {
            // save was cancelled
            return;
        }
        
        // set doc's fileURL
        [self setFileURL: [savePanel URL]];

        // update the module with the proper module name / path info
        NSString	*fileName = [[self fileURL] path];
        NSString	*moduleName = [[fileName stringByDeletingLastPathComponent] lastPathComponent];
        [module setValue: moduleName forKey: @"name"];
        [module setValue: [fileName stringByDeletingLastPathComponent] forKey: @"path"];
    }
    
    [mainWindow startSaveProgress];
    
    // prepare the shared instances and config
    NSMutableString         *siOut = [NSMutableString stringWithString: @"<?php\n\n$__instances = array("];
    NSMutableString         *scOut = [NSMutableString stringWithString: @"<?php\n\n$__config = array("];
    NSEnumerator            *siEn = [[module valueForKey: @"sharedInstances"] objectEnumerator];
    SharedInstance          *sharedInstance;
    SharedInstanceConfig    *config;
    while (sharedInstance = [siEn nextObject]) {
        // manifest the instance in the shared.instance file
        [siOut appendFormat: @"\n\t'%@' => '%@',", [sharedInstance valueForKey: @"instanceID"], [sharedInstance valueForKey: @"instanceClass"]];
        
        // look for config for this instance
        NSEnumerator *scEn = [[sharedInstance valueForKey: @"config"] objectEnumerator];
        if ([[sharedInstance valueForKey: @"config"] count] > 0)
        {
            [scOut appendFormat: @"\n\t'%@' => array(", [sharedInstance valueForKey: @"instanceID"]];
            [scOut appendString: @"\n\t\t'properties' => array("];
            while (config = [scEn nextObject]) {
                if ([[config valueForKey: @"encapsulate"] boolValue] == YES)
                {
                    [scOut appendFormat: @"\n\t\t\t'%@' => '%@',", [config valueForKey: @"name"],  ([config valueForKey: @"value"] ? [config valueForKey: @"value"] : @"")];
                }
                else
                {
                    [scOut appendFormat: @"\n\t\t\t'%@' => %@,", [config valueForKey: @"name"],  ([config valueForKey: @"value"] ? [config valueForKey: @"value"] : @"")];
                }
            }
            [scOut appendString: @"\n\t\t),"];
            [scOut appendString: @"\n\t),"];
        }
    }
    [siOut appendString: @"\n);\n?>\n"];
    [scOut appendString: @"\n);\n?>\n"];
    
    // ensure module directory exists
    NSString        *moduleDirPath = [module valueForKey: @"path"];
    NSFileManager   *fm = [NSFileManager defaultManager];
    if (![fm fileExistsAtPath: moduleDirPath])
    {
        [fm createDirectoryAtPath: moduleDirPath attributes: nil];
    }
    
    // write shared.*
    NSError *err;
    BOOL    wroteFile;
    NSString    *siPath = [moduleDirPath stringByAppendingPathComponent: @"shared.instances"];
    NSString    *scPath = [moduleDirPath stringByAppendingPathComponent: @"shared.config"];
    wroteFile = [siOut writeToFile: siPath atomically: YES encoding: NSASCIIStringEncoding error: &err];
    if (!wroteFile)
    {
        NSLog(@"Failed writing %@, reason: %@.", siPath, err);
    }
    wroteFile = [scOut writeToFile: scPath atomically: YES encoding: NSASCIIStringEncoding error: &err];
    if (!wroteFile)
    {
        NSLog(@"Failed writing %@, reason: %@.", scPath, err);
    }
    
    // write page.*
    NSEnumerator    *pageEn = [[module valueForKey: @"pages"] objectEnumerator];
    Page            *page;
    while (page = [pageEn nextObject]) {
        [page saveSetupToDirectory: moduleDirPath];
    }
    
    [mainWindow stopSaveProgress];

    // mark as un-edited
    NSManagedObjectContext *managedObjectContext = [self managedObjectContext];
    [managedObjectContext processPendingChanges];
    [[managedObjectContext undoManager] removeAllActions];
    [self updateChangeCount:NSChangeCleared];    
}

- (void) loadStateFromFiles: (NSError**) outError
{
	NSLog(@"loading module data from files in: %@", [module valueForKey: @"path"]);
	
	NSString		*moduleDir = [module valueForKey: @"path"];
	NSFileManager	*fm = [NSFileManager defaultManager];
	NSError			*err;
				
    // parse shared config file first, so we can access configs via XPATH as we init the instances
    NSString    *sharedConfigPath = [moduleDir stringByAppendingPathComponent: @"shared.config"];
    NSXMLDocument   *scXML = nil;
    if ([fm fileExistsAtPath: sharedConfigPath])
    {
        NSString    *sharedConfigXML = [self getXMLRepresentationOfPHPFile: sharedConfigPath];
        scXML = [[NSXMLDocument alloc] initWithXMLString: sharedConfigXML options: 0 error: &err];
    }
    
    // now parse all shared instances
    NSString    *sharedInstancePath = [moduleDir stringByAppendingPathComponent: @"shared.instances"];
    if ([fm fileExistsAtPath: sharedInstancePath])
    {
        NSString    *sharedInstancesXML = [self getXMLRepresentationOfPHPFile: sharedInstancePath];
        
        NSXMLDocument *siXML = [[NSXMLDocument alloc] initWithXMLString: sharedInstancesXML options: 0 error: &err];
        NSArray *instances = [[siXML rootElement] children];
        int i, count = [instances count];
        for (i=0; i < count; i++) 
		{
            NSXMLNode *child = [instances objectAtIndex:i];
            NSString    *xpq = [NSString stringWithFormat: @"//%@/properties/*", [child name]];
             NSArray    *configs = [NSArray array];
             if (scXML)
             {
                 configs = [scXML nodesForXPath: xpq error: &err];
             }
             
			 // create the shared instance.. instance!
			 SharedInstance  *si = [SharedInstance sharedInstanceFromXMLNode: child withConfig: configs context: [self managedObjectContext]];
			 NSMutableSet *sharedInstances = [module mutableSetValueForKey:@"sharedInstances"];
			 [sharedInstances addObject: si];
        }
    }
	
	// read pages
	NSDirectoryEnumerator *dirEn = [[NSFileManager defaultManager] enumeratorAtPath: [module valueForKey: @"path"]];
	[dirEn skipDescendents];
	NSString   *file;
	while (file = [dirEn nextObject]) {
		if ([[file pathExtension] isEqualToString: @"tpl"])
		{
			NSString   *pageBasePath = [[module valueForKey: @"path"] stringByAppendingPathComponent: [file stringByDeletingPathExtension]];
			NSString   *pageName = [pageBasePath lastPathComponent];
			Page       *page = [NSEntityDescription insertNewObjectForEntityForName: @"Page"
															 inManagedObjectContext: [self managedObjectContext]];
												
			NSLog(@"Processing page: %@", pageName);
			[page setValue: pageName forKey: @"name"];
			NSMutableSet *pages = [module mutableSetValueForKey: @"pages"];
			[pages addObject: page];
			
			// open page config, if there is one
			NSString       *pageConfigPath = [pageBasePath stringByAppendingPathExtension: @"config"];
			NSXMLDocument  *pageConfigXML = nil;
			if ([fm fileExistsAtPath: pageConfigPath])
			{
				NSLog(@"Processing %@.config...", pageName);
				NSString    *parsedXML = [self getXMLRepresentationOfPHPFile: pageConfigPath];
				pageConfigXML = [[NSXMLDocument alloc] initWithXMLString: parsedXML options: 0 error: &err];
			}
			else
			{
				NSLog(@"No config file found for page '%@' at: %@", pageName, pageConfigPath);
			}
			// open page instances
			NSString       *pageInstancesPath = [pageBasePath stringByAppendingPathExtension: @"instances"];
			if ([fm fileExistsAtPath: pageInstancesPath])
			{
				NSLog(@"Processing %@.instances...", pageName);
				// parse the page.instances file
				NSString       *parsedXML = [self getXMLRepresentationOfPHPFile: pageInstancesPath];
				NSXMLDocument  *pageInstanceXML = [[NSXMLDocument alloc] initWithXMLString: parsedXML options: 0 error: &err];
				
				// instantiate all of the page instances
				NSArray *instances = [[pageInstanceXML rootElement] children];
				int i, count = [instances count];
				for (i=0; i < count; i++) {
					NSXMLNode *child = [instances objectAtIndex:i];
					
					// create the page instance...
					PageInstance  *pi = [PageInstance pageInstanceFromXMLNode: child withConfig: pageConfigXML context: [self managedObjectContext]];
					NSMutableSet *pageInstances = [page mutableSetValueForKey: @"instances"];
					[pageInstances addObject: pi];
				}
			}
			else
			{
				NSLog(@"No instances file found for page '%@' at: %@", pageName, pageInstancesPath);
			}
		}
	}
}

- (BOOL) readFromURL:(NSURL *)url ofType:(NSString *)typeName error:(NSError **)outError
{
    NSLog(@"opening module file: %@ (%@).", url, typeName);
    
    // check input
    if (![url isFileURL])
    {
        *outError = [NSError errorWithDomain: @"badFileURL" code: 0 userInfo: nil];
        return NO;
    }
    
    // bootstrap a new module
    NSManagedObjectContext *managedObjectContext = [self managedObjectContext];
    [self setModule:[NSEntityDescription insertNewObjectForEntityForName:@"Module"
                                                  inManagedObjectContext:managedObjectContext]];
    
    // set up the new module
    NSString	*fileName = [url path];
    NSString	*moduleName = [[fileName stringByDeletingLastPathComponent] lastPathComponent];
    [module setValue: moduleName forKey: @"name"];
    [module setValue: [fileName stringByDeletingLastPathComponent] forKey: @"path"];

    [self loadStateFromFiles: outError];

    // mark as un-edited
    [managedObjectContext processPendingChanges];
    [[managedObjectContext undoManager] removeAllActions];
    [self updateChangeCount:NSChangeCleared];
    
    return YES;
}



@end