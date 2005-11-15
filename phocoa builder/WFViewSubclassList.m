//
//  WFViewSubclassList.m
//  PHOCOA Builder
//
//  Created by Alan Pinstein on 5/23/05.
//  Copyright 2005 __MyCompanyName__. All rights reserved.
//

#import "WFViewSubclassList.h"


@implementation WFViewSubclassList

- (NSArray*) subclasses
{
    return subclasses;
}
- (void) setSubclasses: (NSArray*) list
{
    if (list != subclasses)
    {
        [subclasses release];
        subclasses = [list sortedArrayUsingSelector: @selector(compare:)]; // sort!
        [subclasses retain];
    }
}

- (void) dealloc
{
    [subclasses release];
    [super dealloc];
}

+ (NSArray*) subclasses
{
    static WFViewSubclassList   *instance = nil;
    if (!instance)
    {
        // singleton instantiator
        instance = [[WFViewSubclassList alloc] init];
        
        // load info from plist
        NSString        *plistPath = [[NSBundle mainBundle] pathForResource: @"WFViewSubclasses" ofType: @"plist"];
        NSData          *plistData = [NSData dataWithContentsOfFile: plistPath];
        NSString        *errString;
        [instance setSubclasses: [NSPropertyListSerialization propertyListFromData: plistData 
                                                                    mutabilityOption: NSPropertyListImmutable
                                                                              format: NULL
                                                                    errorDescription: &errString]];
    }
    return [instance subclasses];
}

@end
