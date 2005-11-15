//
//  FixedNSTreeController.m
//  PHOCOA Builder
//
//  Created by Alan Pinstein on 11/14/05.
//  Copyright 2005 __MyCompanyName__. All rights reserved.
//

#import "FixedNSTreeController.h"

// What is this for, you ask?
// See http://www.cocoadev.com/index.pl?NSTreeControllerBug

@implementation FixedNSTreeController

- (void)setContent:(id)content
{
	if(![content isEqual:[self content]])
	{
		NSArray *paths = [[self selectionIndexPaths] retain];
		[super setContent:nil];
		[super setContent:content];
		[self setSelectionIndexPaths:paths];
		[paths release];
	}
}

@end
