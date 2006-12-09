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
#import "YAML/YAML.h"

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

- (NSDictionary*) pageInstancesDictionary
{
    NSMutableDictionary     *piDict = [NSMutableDictionary dictionary];
    NSSet                   *pageInstances = [self valueForKey: @"instances"];
    NSEnumerator            *piEn = [pageInstances objectEnumerator];
    PageInstance            *pi;
    while ( pi = [piEn nextObject] ) {
        [piDict setObject: [pi instanceAsDictionary] forKey: [pi valueForKey: @"instanceID"]];
    }
    return piDict;
}

- (void) saveSetupToDirectory: (NSString*) moduleDirPath
{
    NSLog(@"Saving setup for page: %@", [self valueForKey: @"name"]);

    // convert page setup to YAML
    NSString    *pageYAML = [[self pageInstancesDictionary] yamlDescription];
    
    NSError *err;
    BOOL    wroteFile;
    NSString    *pageYAMLPath = [moduleDirPath stringByAppendingPathComponent: [NSString stringWithFormat: @"%@.yaml", [self valueForKey: @"name"]]];
    wroteFile = [pageYAML writeToFile: pageYAMLPath atomically: YES encoding: NSUTF8StringEncoding error: &err];
    if (!wroteFile)
    {
        NSLog(@"Failed writing %@, reason: %@.", pageYAML, err);
    }
}

@end
