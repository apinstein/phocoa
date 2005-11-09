//
//  PageInstanceBindingOption.h
//  PHOCOA Builder
//
//  Created by Alan Pinstein on 10/31/05.
//  Copyright 2005 __MyCompanyName__. All rights reserved.
//

#import <Cocoa/Cocoa.h>

// TODO: refactor all the *Config stuff into a single NameValuePair superclass?
@interface PageInstanceBindingOption : NSManagedObject {

}

+ (PageInstanceBindingOption*) pageInstanceBindingOptionFromXMLNode: (NSXMLNode*) node context: (NSManagedObjectContext*) context;

@end
