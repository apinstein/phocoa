//
//  PageInstanceBinding.m
//  PHOCOA Builder
//
//  Created by Alan Pinstein on 10/31/05.
//  Copyright 2005 __MyCompanyName__. All rights reserved.
//

#import "PageInstanceBinding.h"


@implementation PageInstanceBinding

+ (PageInstanceBinding*) pageInstanceBindingFromXMLNode: (NSXMLNode*) node context: (NSManagedObjectContext*) context;
{
    PageInstanceBinding  *binding = [NSEntityDescription
											insertNewObjectForEntityForName: @"PageInstanceBinding"
													 inManagedObjectContext: context];
    
    [binding setValue: [node name] forKey: @"bindProperty"];
    
    // need to grab all of the bindings config from node:
    NSError     *err;
    NSString    *xpq;
    NSArray     *xpr;
    
    xpq = [NSString stringWithFormat: @"./instanceID"];
    xpr = [node nodesForXPath: xpq error: &err];
    [binding setValue: [[xpr objectAtIndex: 0] stringValue] forKey: @"bindToSharedInstanceID"];
    
    xpq = [NSString stringWithFormat: @"./controllerKey"];
    xpr = [node nodesForXPath: xpq error: &err];
    [binding setValue: [[xpr objectAtIndex: 0] stringValue] forKey: @"controllerKey"];
    
    xpq = [NSString stringWithFormat: @"./modelKeyPath"];
    xpr = [node nodesForXPath: xpq error: &err];
    [binding setValue: [[xpr objectAtIndex: 0] stringValue] forKey: @"modelKeyPath"];
    
    xpq = [NSString stringWithFormat: @"./options/*"];
	 xpr = [node nodesForXPath: xpq error: &err];
	 NSEnumerator   *optionEn = [xpr objectEnumerator];
     NSXMLNode      *optionNode;
	 while (optionNode = [optionEn nextObject])
	 {
         [[binding mutableSetValueForKey: @"options"] addObject: [PageInstanceBindingOption pageInstanceBindingOptionFromXMLNode: optionNode context: context]];
	 }
	 
	 return binding;    
}
@end
