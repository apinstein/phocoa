//
//  Module.h
//  PHOCOA Builder
//
//  Created by Alan Pinstein on 12/9/06.
//  Copyright 2006 __MyCompanyName__. All rights reserved.
//

#import <Cocoa/Cocoa.h>
#import "SharedInstance.h"


@interface Module : NSManagedObject {

}

- (NSDictionary*) sharedInstancesDictionary;
- (void) saveSetupToDirectory: (NSString*) moduleDirPath;
- (NSArray*) listOfSharedInstanceIDs;

@end
