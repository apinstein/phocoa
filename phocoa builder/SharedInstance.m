//
//  SharedInstance.m
//  PHOCOA Builder
//
//  Created by Alan Pinstein on 10/26/05.
//  Copyright 2005 __MyCompanyName__. All rights reserved.
//

#import "SharedInstance.h"

@implementation SharedInstance

+ (SharedInstance*) sharedInstance: (NSString*) instanceId withConfig: (NSDictionary*) config context: (NSManagedObjectContext*) context
{
	// set up shared instance
	SharedInstance *inst = [NSEntityDescription
    insertNewObjectForEntityForName:@"SharedInstance"
			 inManagedObjectContext:context];
	    
	// configure basic information
	[inst setValue: instanceId forKey: @"instanceID"];
    [inst setValue: [config valueForKeyPath: @"class"] forKey: @"instanceClass"];
    
	// set up config
    NSDictionary    *configProperties = [config objectForKey: @"properties"];
    if (configProperties)
    {
        NSEnumerator    *configEn = [configProperties keyEnumerator];
        NSString        *configProp;
        while (configProp = [configEn nextObject]) {
            SharedInstanceConfig	*config = [SharedInstanceConfig sharedInstance: configProp value: [configProperties valueForKey: configProp]  context: context];
            NSMutableSet *configs = [inst mutableSetValueForKey: @"config"];
            [configs addObject: config];
        }    
    }
    
    return inst;
}

- (id) toYAML
{
    NSMutableDictionary *yaml = [NSMutableDictionary dictionary];
    [yaml setObject: [self valueForKey: @"instanceClass"] forKey: @"class"];
    [yaml setObject: [self configAsDictionary] forKey: @"properties"];
    return yaml;
}

- (NSDictionary*) configAsDictionary
{
    NSMutableDictionary     *sicDict = [NSMutableDictionary dictionary];
    NSSet                   *sharedInstanceConfig = [self valueForKey: @"config"];
    NSEnumerator            *sicEn = [sharedInstanceConfig objectEnumerator];
    SharedInstanceConfig    *sic;
    while ( sic = [sicEn nextObject] ) {
        [sicDict setObject: [sic valueForKey: @"value"] forKey: [sic valueForKey: @"name"]];
    }
    return sicDict;
}



@end
