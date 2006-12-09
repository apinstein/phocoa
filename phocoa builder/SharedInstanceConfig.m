//
//  SharedInstanceConfig.m
//  PHOCOA Builder
//
//  Created by Alan Pinstein on 10/26/05.
//  Copyright 2005 __MyCompanyName__. All rights reserved.
//

#import "SharedInstanceConfig.h"


@implementation SharedInstanceConfig

- (NSString*) escapedValue
{
    NSMutableString *str = [NSMutableString stringWithString: [self valueForKey: @"value"]];
    [str replaceOccurrencesOfString: @"'" withString: @"\\'" options: NSLiteralSearch range: NSMakeRange(0, [[self valueForKey: @"value"] length])];
    return str;
}

+ (SharedInstanceConfig*) sharedInstance: (NSString*) name value: (NSString*) value context: (NSManagedObjectContext*) context
{
	// set up new config
    SharedInstanceConfig  *nvPair = [NSEntityDescription
											insertNewObjectForEntityForName: @"SharedInstanceConfig"
													 inManagedObjectContext: context];    
	[nvPair setValue: name forKey: @"name"];
    [nvPair setValue: value forKey: @"value"];
    
    return nvPair;
}


@end
