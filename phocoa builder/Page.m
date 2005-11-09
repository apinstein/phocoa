//
//  Page.m
//  PHOCOA Builder
//
//  Created by Alan Pinstein on 10/30/05.
//  Copyright 2005 __MyCompanyName__. All rights reserved.
//

#import "Page.h"


@implementation Page

- (NSString*) tplFilePath
{
	NSString	*pathToTPLFile = [[self valueForKey: @"module"] valueForKey: @"path"];
	pathToTPLFile = [pathToTPLFile stringByAppendingPathComponent: [self valueForKey: @"name"]];
	pathToTPLFile = [pathToTPLFile stringByAppendingPathExtension: @"tpl"];
	return pathToTPLFile;
}

@end
