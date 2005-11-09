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

+ (PageInstance*) pageInstanceFromXMLNode: (NSXMLNode*) node withConfig: (NSXMLNode*) configXML context: (NSManagedObjectContext*) context;
@end
