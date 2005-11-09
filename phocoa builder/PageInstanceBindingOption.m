//
//  PageInstanceBindingOption.m
//  PHOCOA Builder
//
//  Created by Alan Pinstein on 10/31/05.
//  Copyright 2005 __MyCompanyName__. All rights reserved.
//

#import "PageInstanceBindingOption.h"


@implementation PageInstanceBindingOption

+ (PageInstanceBindingOption*) pageInstanceBindingOptionFromXMLNode: (NSXMLNode*) node context: (NSManagedObjectContext*) context;
{
	// set up new config
    PageInstanceBindingOption  *nvPair = [NSEntityDescription
											insertNewObjectForEntityForName: @"PageInstanceBindingOption"
													 inManagedObjectContext: context];    
	[nvPair setValue: [node name] forKey: @"name"];
    
    // determine type, also set value
    NSError     *err;
    NSString    *xpq = [NSString stringWithFormat: @"./@_type"];
    NSArray     *xpr = [node nodesForXPath: xpq error: &err];
    NSString    *type = [[xpr objectAtIndex: 0] stringValue];
    if ([type isEqualTo: @"string"])
    {
        [nvPair setValue: [NSNumber numberWithBool: YES] forKey: @"encapsulate"];
        [nvPair setValue: [node stringValue] forKey: @"value"];
    }
    else if ([type isEqualTo: @"boolean"])
    {
        [nvPair setValue: [NSNumber numberWithBool: NO] forKey: @"encapsulate"];
        if ([[node stringValue] isEqualTo: @"1"])
        {
            [nvPair setValue: @"true" forKey: @"value"];
        }
        else
        {
            [nvPair setValue: @"false" forKey: @"value"];
        }
    }
    else if ([type isEqualTo: @"array"])
    {
        [nvPair setValue: [NSNumber numberWithBool: NO] forKey: @"encapsulate"];
        
        // query all array values
        if ([[node stringValue] isEqualTo: @"1"])
        {
            [nvPair setValue: @"true" forKey: @"value"];
        }
        else
        {
            [nvPair setValue: @"false" forKey: @"value"];
        }
    }
    else
    {
        [nvPair setValue: [NSNumber numberWithBool: NO] forKey: @"encapsulate"];
        [nvPair setValue: [node stringValue] forKey: @"value"];
    }
    
    return nvPair;
}
@end
