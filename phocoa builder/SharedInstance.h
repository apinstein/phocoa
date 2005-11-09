//
//  SharedInstance.h
//  PHOCOA Builder
//
//  Created by Alan Pinstein on 10/26/05.
//  Copyright 2005 __MyCompanyName__. All rights reserved.
//

#import <Cocoa/Cocoa.h>
#import "SharedInstanceConfig.h"

@interface SharedInstance : NSManagedObject {

}

+ (SharedInstance*) sharedInstanceFromXMLNode: (NSXMLNode*) siNode withConfig: (NSArray*) configNodes context: (NSManagedObjectContext*) context;
@end
