//
//  WFViewSubclassList.h
//  PHOCOA Builder
//
//  Created by Alan Pinstein on 5/23/05.
//  Copyright 2005 __MyCompanyName__. All rights reserved.
//

#import <Cocoa/Cocoa.h>


@interface WFViewSubclassList : NSObject {
    NSArray *subclasses;
}

+ (NSArray*) subclasses;
@end
