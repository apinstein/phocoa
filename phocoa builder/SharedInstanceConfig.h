//
//  SharedInstanceConfig.h
//  PHOCOA Builder
//
//  Created by Alan Pinstein on 10/26/05.
//  Copyright 2005 __MyCompanyName__. All rights reserved.
//

#import <Cocoa/Cocoa.h>


// TODO: refactor all the *Config stuff into a single NameValuePair superclass?
@interface SharedInstanceConfig : NSManagedObject {

}

+ (SharedInstanceConfig*) sharedInstance: (NSString*) name value: (NSString*) value context: (NSManagedObjectContext*) context;

@end
