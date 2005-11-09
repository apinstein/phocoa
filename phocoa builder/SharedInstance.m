//
//  SharedInstance.m
//  PHOCOA Builder
//
//  Created by Alan Pinstein on 10/26/05.
//  Copyright 2005 __MyCompanyName__. All rights reserved.
//

#import "SharedInstance.h"


@implementation SharedInstance

+ (SharedInstance*) sharedInstanceFromXMLNode: (NSXMLNode*) siNode withConfig: (NSArray*) configNodes context: (NSManagedObjectContext*) context
{
	// set up shared instance
	SharedInstance *inst = [NSEntityDescription
    insertNewObjectForEntityForName:@"SharedInstance"
			 inManagedObjectContext:context];
	    
	[inst setValue: [siNode name] forKey: @"instanceID"];
    [inst setValue: [siNode stringValue] forKey: @"instanceClass"];
    
	// set up SharedInstanceConfig's
    NSEnumerator    *configEn = [configNodes objectEnumerator];
    NSXMLNode       *configNode;
    while (configNode = [configEn nextObject]) {
		SharedInstanceConfig	*config = [SharedInstanceConfig nameValuePairFromXMLNode: configNode 
																				 context: context];
		NSMutableSet *configs = [inst mutableSetValueForKey: @"config"];
		[configs addObject: config];
	}
    
    return inst;
}

@end
