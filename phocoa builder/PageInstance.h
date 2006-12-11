//
//  PageInstance.h
//  PHOCOA Builder
//
//  Created by Alan Pinstein on 10/31/05.
//  Copyright 2005 __MyCompanyName__. All rights reserved.
//

#import <Cocoa/Cocoa.h>

#import "PageInstanceConfig.h"
#import "PageInstanceBinding.h"


@interface PageInstance : NSManagedObject {

}

- (BOOL) isPrototype;

- (NSDictionary*) instanceAsDictionary;
+ (PageInstance*) pageInstance: (NSString*) instanceId withConfig: (NSDictionary*) config context: (NSManagedObjectContext*) context;

- (NSArray*) instancePropertyList;

@end
