//  MyDocument.m
//  PHOCOA Builder
//
//  Created by Alan Pinstein on 10/26/05.
//  Copyright __MyCompanyName__ 2005 . All rights reserved.

#import "ModuleDocument.h"
#import "YAML/YAML.h"


@interface ModuleDocument (Private)
- (void)setModule:(Module*) aModule;
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

- (void)setModule:(Module*) aModule
{
    if (module != aModule) {
        [module release];
        module = [aModule retain];
    }
}

- (Module*) module
{
	return module;
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
    // THIS NEVER GETS CALLED....???
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

    // ensure module directory exists
    NSString        *moduleDirPath = [module valueForKey: @"path"];
    NSFileManager   *fm = [NSFileManager defaultManager];
    if (![fm fileExistsAtPath: moduleDirPath])
    {
        [fm createDirectoryAtPath: moduleDirPath attributes: nil];
    }
        
    // convert shared setup to YAML
    [module saveSetupToDirectory: moduleDirPath];
    
    // write pages
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
	
    NSDictionary    *yamlOptions = [NSDictionary dictionaryWithObjectsAndKeys: [NSNumber numberWithBool: NO], @"implicit_typing", [NSNumber numberWithBool: YES], @"taguri_expansion",  nil];
	NSString		*moduleDir = [module valueForKey: @"path"];
	NSFileManager	*fm = [NSFileManager defaultManager];
	NSError			**err;

    // parse shared config file first, so we can access configs via XPATH as we init the instances
    NSString    *sharedConfigPath = [moduleDir stringByAppendingPathComponent: @"shared.yaml"];
    if ([fm fileExistsAtPath: sharedConfigPath])
    {
        NSString        *sharedYAML = [NSString stringWithContentsOfFile: sharedConfigPath encoding: NSUTF8StringEncoding error: err];
        NSDictionary    *yaml = yaml_parse(sharedYAML, yamlOptions);
        if ((id) yaml != (id) [NSNull null])
        {
            NSString        *instanceId;
            NSEnumerator    *en = [yaml keyEnumerator];
            while ( (instanceId = [en nextObject]) ) {
                // create the shared instance.. instance!
                SharedInstance  *si = [SharedInstance sharedInstance: instanceId withConfig: [yaml objectForKey: instanceId] context: [self managedObjectContext]];
                NSMutableSet *sharedInstances = [module mutableSetValueForKey:@"sharedInstances"];
                [sharedInstances addObject: si];
            }            
        }
    }
             
	// read pages
	NSDirectoryEnumerator *dirEn = [[NSFileManager defaultManager] enumeratorAtPath: [module valueForKey: @"path"]];
	[dirEn skipDescendents];
	NSString        *file;
    NSMutableSet    *pages = [module mutableSetValueForKey: @"pages"];
	while (file = [dirEn nextObject]) {
        // skip .myTemplate.tpl files (webdav artifact)
        NSArray     *components = [file pathComponents];
        NSString    *fileName = [components objectAtIndex: ([components count] - 1)];
        unichar     firstChar = [fileName characterAtIndex: 0];
        // skip hidden files
        if (firstChar == '.') continue;
        // skip files that aren't ending in .tpl
        if (![[file pathExtension] isEqualToString: @"tpl"]) continue;
        
        // here we have just .tpl files; these are either "page" templates (has corresponding .yaml file) or "sub" templates (no corresponding .yaml file)
        NSString   *pageBasePath = [[module valueForKey: @"path"] stringByAppendingPathComponent: [file stringByDeletingPathExtension]];
        NSString   *pageName = [pageBasePath lastPathComponent];
        NSString    *pageYamlPath = [pageBasePath stringByAppendingPathExtension: @"yaml"];
        
        // skip .tpl files if they don't have a corresponding .yaml file; these are sub-templates
        if (![[NSFileManager defaultManager] fileExistsAtPath: pageYamlPath]) continue; 
        
        // here we know we have a page with a .yaml file; thus we process it and add to our model
        Page       *page = [NSEntityDescription insertNewObjectForEntityForName: @"Page"
                                                         inManagedObjectContext: [self managedObjectContext]];
                                            
        NSLog(@"Processing page: %@", pageName);
        [page setValue: pageName forKey: @"name"];
        [pages addObject: page];
        
        // open .yaml config file
        NSString        *pageYAML = [NSString stringWithContentsOfFile: pageYamlPath encoding: NSUTF8StringEncoding error: err];
        NSDictionary    *yaml = yaml_parse(pageYAML, yamlOptions);
        
        if ((id) yaml != (id) [NSNull null])
        {
            NSString        *instanceId;
            NSEnumerator    *en = [yaml keyEnumerator];
            while ( (instanceId = [en nextObject]) ) {
                // create the page instance... instance!
                PageInstance  *pi = [PageInstance pageInstance: instanceId withConfig: [yaml objectForKey: instanceId] context: [self managedObjectContext]];
                NSMutableSet *pageInstances = [page mutableSetValueForKey: @"instances"];
                [pageInstances addObject: pi];
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
    [self setModule: [NSEntityDescription insertNewObjectForEntityForName:@"Module"
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