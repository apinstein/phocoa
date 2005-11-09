//
//  ModuleMainWindowController.m
//  PHOCOA Builder
//
//  Created by Alan Pinstein on 10/30/05.
//  Copyright 2005 __MyCompanyName__. All rights reserved.
//

#import "ModuleMainWindowController.h"

@implementation ModuleMainWindowController

- (NSManagedObjectContext*) managedObjectContext
{
	return [[self document] managedObjectContext];
}

- (id) init
{
	if (self = [self initWithWindowNibName: @"ModuleDocument"])
	{
		// extra initialization
	}
	return self;
}
@end
