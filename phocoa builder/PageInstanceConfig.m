//
//  PageInstanceConfig.m
//  PHOCOA Builder
//
//  Created by Alan Pinstein on 10/31/05.
//  Copyright 2005 __MyCompanyName__. All rights reserved.
//

#import "PageInstanceConfig.h"
#import "WFViewPhocoaBuilderIntegration.h"


@implementation PageInstanceConfig

- (NSArray*) instancePropertyListValues
{
    return [[WFViewPhocoaBuilderIntegration sharedWFViewPhocoaBuilderIntegration] propertyListValuesForClass: [self valueForKeyPath: @"pageInstance.instanceClass"] property: [self valueForKey: @"name"]];
}

+ (PageInstanceConfig*) configWithName: (NSString*) name value: (NSString*) value context: (NSManagedObjectContext*) context
{
	// set up new config
    PageInstanceConfig  *nvPair = [NSEntityDescription
											insertNewObjectForEntityForName: @"PageInstanceConfig"
													 inManagedObjectContext: context];    
	[nvPair setValue: name forKey: @"name"];
	[nvPair setValue: value forKey: @"value"];
    
    return nvPair;
}


@end
