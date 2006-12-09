//
//  PageInstanceConfig.h
//  PHOCOA Builder
//
//  Created by Alan Pinstein on 10/31/05.
//  Copyright 2005 __MyCompanyName__. All rights reserved.
//

#import <Cocoa/Cocoa.h>

// TODO: refactor all the *Config stuff into a single NameValuePair superclass?
@interface PageInstanceConfig : NSManagedObject {

}

+ (PageInstanceConfig*) configWithName: (NSString*) name value: (NSString*) value context: (NSManagedObjectContext*) context;

@end
