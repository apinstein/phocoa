//
//  TaskWrapper.h
//  PHOCOA Builder
//
//  Created by Alan Pinstein on 11/5/05.
//  Copyright 2005 Alan Pinstein. All rights reserved.
//

#import <Cocoa/Cocoa.h>


@interface TaskWrapper : NSObject {
    NSTask              *theTask;
    NSFileHandle        *outH;
    NSFileHandle        *errH;
    NSMutableString     *stdOut;
    NSMutableString     *stdErr;
}
+ (TaskWrapper*) runShellCommand: (NSString*) command arguments: (NSArray*) args;
- (NSString*) stdOut;
- (NSString*) stdErr;
- (int) terminationStatus;
@end
