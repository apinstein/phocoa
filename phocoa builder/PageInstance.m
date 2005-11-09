//
//  PageInstance.m
//  PHOCOA Builder
//
//  Created by Alan Pinstein on 10/31/05.
//  Copyright 2005 __MyCompanyName__. All rights reserved.
//

#import "PageInstance.h"


@implementation PageInstance

+ (PageInstance*) pageInstanceFromXMLNode: (NSXMLNode*) node withConfig: (NSXMLNode*) configXML context: (NSManagedObjectContext*) context
{
	// set up new page instance
    PageInstance  *inst = [NSEntityDescription
								insertNewObjectForEntityForName: @"PageInstance"
										 inManagedObjectContext: context];
    [inst setValue: [node name] forKey: @"instanceID"];
    
    // extract class
    NSError     *err;
    NSString    *xpq = [NSString stringWithFormat: @"./class"];
    NSArray     *classNodes = [node nodesForXPath: xpq error: &err];
    [inst setValue: [[classNodes objectAtIndex: 0] stringValue] forKey: @"instanceClass"];
	
    // extract config
    if (configXML)
    {
        NSString    *xpq;
		
		// search for the properties for this instance
		xpq = [NSString stringWithFormat: @"//%@/properties/*", [node name]];
		 NSArray     *configs = [configXML nodesForXPath: xpq error: &err];
		 
		 NSEnumerator    *configEn = [configs objectEnumerator];
		 NSXMLNode       *configNode;
		 while (configNode = [configEn nextObject])
		 {
			 [[inst mutableSetValueForKey: @"config"] addObject: [PageInstanceConfig pageInstanceConfigFromXMLNode: configNode context: context]];
		 }
         
         // search for bindings for this instance
         xpq = [NSString stringWithFormat: @"//%@/bindings/*", [node name]];
		  NSArray     *bindings = [configXML nodesForXPath: xpq error: &err];
          
          NSEnumerator    *bindingsEn = [bindings objectEnumerator];
          NSXMLNode       *bindingNode;
          while (bindingNode = [bindingsEn nextObject])
		  {
              [[inst mutableSetValueForKey: @"bindings"] addObject: [PageInstanceBinding pageInstanceBindingFromXMLNode: bindingNode context: context]];
          }
    }
		  
		  // recurse into children
		  xpq = [NSString stringWithFormat: @"./children/*"];
		   NSArray     *childNodes = [node nodesForXPath: xpq error: &err];
		   int i, count = [childNodes count];
		   for (i=0; i < count; i++) {
			   PageInstance    *childPageInstance = [PageInstance pageInstanceFromXMLNode: [childNodes objectAtIndex: i] withConfig: configXML context: context];
			   [[inst mutableSetValueForKey: @"children"] addObject: childPageInstance];
		   }    
		   
		   return inst;
}

@end
