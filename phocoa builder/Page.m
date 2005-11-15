//
//  Page.m
//  PHOCOA Builder
//
//  Created by Alan Pinstein on 10/30/05.
//  Copyright 2005 __MyCompanyName__. All rights reserved.
//

#import "Page.h"
#import "PageInstance.h"
#import "PageInstanceConfig.h"
#import "PageInstanceBinding.h"
#import "PageInstanceBindingOption.h"

@implementation Page

- (NSString*) tplFilePath
{
	NSString	*pathToTPLFile = [[self valueForKey: @"module"] valueForKey: @"path"];
	pathToTPLFile = [pathToTPLFile stringByAppendingPathComponent: [self valueForKey: @"name"]];
	pathToTPLFile = [pathToTPLFile stringByAppendingPathExtension: @"tpl"];
	return pathToTPLFile;
}

// drill-down through all instances creating a master array of all instances at any level
// iterative algorithm with internal stack
- (NSArray*) allInstances
{
    NSMutableArray  *allInstances = [NSMutableArray array];
    PageInstance    *inst;
    
    // add base-level items to stack
    NSMutableArray  *stack = [NSMutableArray arrayWithArray: [[self valueForKey: @"instances"] allObjects]];

    // run drill-down algorithm until stack is empty
    while ([stack count] && (inst = [stack objectAtIndex: 0])) {
        [stack removeObjectAtIndex: 0]; // pop
        [allInstances addObject: inst];
        if ([[inst valueForKey: @"children"] count] > 0)
        {
            [stack addObjectsFromArray: [[inst valueForKey: @"children"] allObjects]]; // push
        }
    }
    return allInstances;    
}

- (void) saveSetupToDirectory: (NSString*) dir
{
    NSLog(@"Saving setup for page: %@", [self valueForKey: @"name"]);
    BOOL        wroteFile;
    NSError     *err;
    NSMutableString    *configOut = [NSMutableString string], *instancesOut = [NSMutableString string];
    
    // process the config file
    [configOut appendString: @"<?php\n\n$__config = array("]; // open main array
    
    // need to get a list of ALL instances; not just top-level
    NSEnumerator    *instanceEn = [[self allInstances] objectEnumerator];
    PageInstance    *instance;
    while (instance = [instanceEn nextObject]) {
        // skip instances that have NO config (bindings or properties)
        if ([[instance valueForKey: @"bindings"] count] == 0 && [[instance valueForKey: @"config"] count] == 0) continue;
        
        [configOut appendFormat: @"\n\t'%@' => array(", [instance valueForKey: @"instanceID"]]; // open instance ID
        
        // manifest the config (properties & bindings) for this instance
        // properties
        if ([[instance valueForKey: @"config"] count] > 0)
        {
            [configOut appendString: @"\n\t\t'properties' => array("]; // open properties array
            
            NSEnumerator *configEn = [[instance valueForKey: @"config"] objectEnumerator];
            PageInstanceConfig  *config;
            while (config = [configEn nextObject]) {
                if ([[config valueForKey: @"encapsulate"] boolValue] == YES)
                {
                    [configOut appendFormat: @"\n\t\t\t'%@' => '%@',", [config valueForKey: @"name"], [config valueForKey: @"escapedValue"]];
                }
                else
                {
                    [configOut appendFormat: @"\n\t\t\t'%@' => %@,", [config valueForKey: @"name"], [config valueForKey: @"escapedValue"]];
                }
            }
            
            [configOut appendString: @"\n\t\t),"]; // close properties array
        }
        
        // bindings
        if ([[instance valueForKey: @"bindings"] count] > 0)
        {
            [configOut appendString: @"\n\t\t'bindings' => array("]; // open bindings array
            
            NSEnumerator *bindingEn = [[instance valueForKey: @"bindings"] objectEnumerator];
            PageInstanceBinding *binding;
            while (binding = [bindingEn nextObject]) {
                [configOut appendFormat: @"\n\t\t\t'%@' => array(", [binding valueForKey: @"bindProperty"]]; // open bound prop array
                [configOut appendFormat: @"\n\t\t\t\t'instanceID' => '%@',", ([binding valueForKey: @"bindToSharedInstanceID"] ? [binding valueForKey: @"bindToSharedInstanceID"] : @"")];
                [configOut appendFormat: @"\n\t\t\t\t'controllerKey' => '%@',", ([binding valueForKey: @"controllerKey"] ? [binding valueForKey: @"controllerKey"] : @"") ];
                [configOut appendFormat: @"\n\t\t\t\t'modelKeyPath' => '%@',", ([binding valueForKey: @"modelKeyPath"] ? [binding valueForKey: @"modelKeyPath"] : @"")];
                
                if ([[binding valueForKey: @"options"] count] > 0)
                {
                    [configOut appendString: @"\n\t\t\t\t'options' => array("]; // open options array
                    
                    NSEnumerator    *bindOptEn = [[binding valueForKey: @"options"] objectEnumerator];
                    PageInstanceBindingOption   *bindOpt;
                    while (bindOpt = [bindOptEn nextObject]) {
                        [configOut appendFormat: @"\n\t\t\t\t\t'%@' => '%@',", [bindOpt valueForKey: @"name"], [bindOpt valueForKey: @"value"]];
                    }
                    
                    [configOut appendString: @"\n\t\t\t\t),"]; // close options array                    
                }
                
                [configOut appendString: @"\n\t\t\t),"]; // close bound prop array
            }
            
            [configOut appendString: @"\n\t\t),"]; // close bindings array
        }
        
        [configOut appendString: @"\n\t),"]; // close instance id
    }
    [configOut appendString: @"\n);\n?>\n"]; // close main array
                                             // output file
    NSString    *configOutFileName = [NSString stringWithFormat: @"%@.config", [self valueForKey: @"name"]];
    wroteFile = [configOut writeToFile: [dir stringByAppendingPathComponent: configOutFileName] atomically: YES encoding: NSASCIIStringEncoding error: &err];
    if (!wroteFile)
    {
        NSLog(@"Failed writing %@, reason: %@.", configOutFileName, err);
    }
    
    // process the instances file
    [instancesOut appendString: @"<?php\n\n$__instances = array("];
    NSEnumerator    *instEn = [[self valueForKey: @"instances"] objectEnumerator];
    PageInstance    *inst;
    while (inst = [instEn nextObject]) {
        [inst instanceToPHPArray: instancesOut];
    }
    [instancesOut appendString: @"\n);\n?>\n"];
    // output file
    NSString    *instancesOutFileName = [NSString stringWithFormat: @"%@.instances", [self valueForKey: @"name"]];
    wroteFile = [instancesOut writeToFile: [dir stringByAppendingPathComponent: instancesOutFileName] atomically: YES encoding: NSASCIIStringEncoding error: &err];
    if (!wroteFile)
    {
        NSLog(@"Failed writing %@, reason: %@.", instancesOutFileName, err);
    }
}

@end
