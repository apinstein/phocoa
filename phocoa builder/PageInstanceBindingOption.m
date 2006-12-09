//
//  PageInstanceBindingOption.m
//  PHOCOA Builder
//
//  Created by Alan Pinstein on 10/31/05.
//  Copyright 2005 __MyCompanyName__. All rights reserved.
//

#import "PageInstanceBindingOption.h"


@implementation PageInstanceBindingOption

+ (PageInstanceBindingOption*) bindingOption: (NSString*) name withValue: (NSString*) value context: (NSManagedObjectContext*) context
{
	// set up new config
    PageInstanceBindingOption  *nvPair = [NSEntityDescription
											insertNewObjectForEntityForName: @"PageInstanceBindingOption"
													 inManagedObjectContext: context];    
	[nvPair setValue: name forKey: @"name"];
	[nvPair setValue: value forKey: @"value"];

    return nvPair;
}
@end
