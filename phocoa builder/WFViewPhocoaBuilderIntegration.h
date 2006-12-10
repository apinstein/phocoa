//
//  WFViewPhocoaBuilderIntegration.h
//  PHOCOA Builder
//
//  Created by Alan Pinstein on 12/10/06.
//  Copyright 2006 __MyCompanyName__. All rights reserved.
//

#import <Cocoa/Cocoa.h>


@interface WFViewPhocoaBuilderIntegration : NSObject {
    NSDictionary    *integrationInfo;
}

+ (WFViewPhocoaBuilderIntegration*) sharedWFViewPhocoaBuilderIntegration;
- (NSArray*) WFViewList;
- (NSArray*) propertyListForClass: (NSString*) class;
- (NSArray*) propertyListValuesForClass: (NSString*) className property: (NSString*) propertyName;
- (NSArray*) bindingListForClass: (NSString*) class;
- (NSArray*) bindToSharedInstanceIdList;
- (NSArray*) controllerKeyList;
@end
