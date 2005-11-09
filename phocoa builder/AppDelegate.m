//
//  AppDelegate.m
//  PHOCOA Builder
//
//  Created by Alan Pinstein on 8/1/05.
//  Copyright 2005 __MyCompanyName__. All rights reserved.
//

#import "AppDelegate.h"


@implementation AppDelegate

+ (void) initialize
{
	// set up default prefs
	NSDictionary *appDefaults = [NSDictionary dictionaryWithObjectsAndKeys:
        @"/usr/bin/php", @"php_executable",
        nil
        ];

    NSUserDefaults *defaults = [NSUserDefaults standardUserDefaults];
	// register default prefs 
    [defaults registerDefaults:appDefaults];
	// set up same defaults as initialValues
    [[NSUserDefaultsController sharedUserDefaultsController] setInitialValues: appDefaults];
	
    
    NSLog(@"Core Data version: %f, Using php executable: %@",
          NSCoreDataVersionNumber, [defaults stringForKey: @"php_executable"]);
}

@end
