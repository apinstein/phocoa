//
//  TaskWrapper.m
//  PHOCOA Builder
//
//  Created by Alan Pinstein on 11/5/05.
//  Copyright 2005 __MyCompanyName__. All rights reserved.
//

#import "TaskWrapper.h"


@implementation TaskWrapper

- (id) initWithCommand: (NSString*) command arguments: (NSArray*) args
{
    if (self = [super init])
    {
        // set up task
        theTask = [[NSTask alloc] init];
        [theTask setLaunchPath: command];
        [theTask setArguments: args];
        
        // set up pipes to capture output
        NSPipe  *outPipe = [NSPipe pipe], *errPipe = [NSPipe pipe];
        [theTask setStandardOutput: outPipe];
        [theTask setStandardError: errPipe];
        outH = [outPipe fileHandleForReading];
        errH = [errPipe fileHandleForReading];

        // set up strings to capture data
        stdOut = [[NSMutableString string] retain];
        stdErr = [[NSMutableString string] retain];
    }
    return self;
}

- (void) readStdErr
{
    NSAutoreleasePool   *pool = [[NSAutoreleasePool alloc] init];
    
    NSData  *data;
    while (true) {
        data = [errH availableData];    // blocks
        if ([data length] == 0) break;

        [stdErr appendString: [[[NSString alloc] initWithData: data encoding: NSASCIIStringEncoding] autorelease]];
    }
    
    [pool release];
}

- (void) readStdOut
{
    NSAutoreleasePool   *pool = [[NSAutoreleasePool alloc] init];

    NSData  *data;
    while (true) {
        data = [outH availableData];    // blocks
        if ([data length] == 0) break;
        
        [stdOut appendString: [[[NSString alloc] initWithData: data encoding: NSASCIIStringEncoding] autorelease]];
    }

    [pool release];
}

- (void) dealloc
{
    [stdOut release];
    [stdErr release];
        
    [theTask release];
    
    [super dealloc];
}

- (void) exec
{
    // start background reading
    [NSThread detachNewThreadSelector: @selector(readStdOut) toTarget: self withObject: nil];
    [NSThread detachNewThreadSelector: @selector(readStdErr) toTarget: self withObject: nil];
    
    [theTask launch];
    [theTask waitUntilExit];
}

- (int) terminationStatus
{
    return [theTask terminationStatus];
}

- (NSString*) stdOut
{
    return [NSString stringWithString: stdOut];
}

- (NSString*) stdErr
{
    return [NSString stringWithString: stdErr];
}

+ (TaskWrapper*) runShellCommand: (NSString*) command arguments: (NSArray*) args
{
    TaskWrapper *t = [[TaskWrapper alloc] initWithCommand: command arguments: args];
    [t exec];
    return t;
}


@end
