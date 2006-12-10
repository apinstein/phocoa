//
//  PageInstance.m
//  PHOCOA Builder
//
//  Created by Alan Pinstein on 10/31/05.
//  Copyright 2005 __MyCompanyName__. All rights reserved.
//

#import "PageInstance.h"
#import "WFViewPhocoaBuilderIntegration.h"


@implementation PageInstance

- (NSArray*) instancePropertyList
{
    return [[WFViewPhocoaBuilderIntegration sharedWFViewPhocoaBuilderIntegration] propertyListForClass: [self valueForKey: @"instanceClass"]];
}

- (NSArray*) instanceBindPropertyList
{
    return [[WFViewPhocoaBuilderIntegration sharedWFViewPhocoaBuilderIntegration] bindingListForClass: [self valueForKey: @"instanceClass"]];
}

- (NSArray*) bindToSharedInstanceIdList
{
    return [[WFViewPhocoaBuilderIntegration sharedWFViewPhocoaBuilderIntegration] bindToSharedInstanceIdList];
}

- (BOOL) isPrototype
{
    if (![self valueForKey: @"parent"]) return NO;
    
    NSString *expectedPrototypeName = [NSString stringWithFormat: @"%@Prototype", [[self valueForKey: @"parent"] valueForKey: @"instanceID"]];
    if ([[self valueForKey: @"instanceID"] isEqualTo: expectedPrototypeName]) return YES;

    return NO;
}

+ (PageInstance*) pageInstance: (NSString*) instanceId withConfig: (NSDictionary*) config context: (NSManagedObjectContext*) context
{
	// set up new page instance
    PageInstance  *inst = [NSEntityDescription
								insertNewObjectForEntityForName: @"PageInstance"
										 inManagedObjectContext: context];
    [inst setValue: instanceId forKey: @"instanceID"];
    [inst setValue: [config objectForKey: @"class"] forKey: @"instanceClass"];
	
	// set up config
    NSDictionary    *configProperties = [config objectForKey: @"properties"];
    if (configProperties)
    {
        NSEnumerator    *configEn = [configProperties keyEnumerator];
        NSString        *configProp;
        while (configProp = [configEn nextObject]) {
            PageInstanceConfig	*config = [PageInstanceConfig configWithName: configProp value: [configProperties valueForKey: configProp]  context: context];
            NSMutableSet *configs = [inst mutableSetValueForKey: @"config"];
            [configs addObject: config];
        }    
    }
    
    // set up bindings
    NSDictionary    *bindings = [config objectForKey: @"bindings"];
    if (bindings)
    {
        NSEnumerator    *bindingsEn = [bindings keyEnumerator];
        NSString        *bindProperty;
        while (bindProperty = [bindingsEn nextObject]) {
            PageInstanceBinding	*binding = [PageInstanceBinding bindProperty: bindProperty withSetup: [bindings valueForKey: bindProperty]  context: context];
            NSMutableSet *bindings = [inst mutableSetValueForKey: @"bindings"];
            [bindings addObject: binding];
        }    
    }
		  
    // recurse into children
    NSDictionary    *children = [config objectForKey: @"children"];
    if (children)
    {
        NSEnumerator    *childrenEn = [children keyEnumerator];
        NSString        *childInstanceId;
        while (childInstanceId = [childrenEn nextObject]) {
            PageInstance    *childPageInstance = [PageInstance pageInstance: childInstanceId withConfig: [children objectForKey: childInstanceId] context: context];
            [[inst mutableSetValueForKey: @"children"] addObject: childPageInstance];
        }
    }
     
    return inst;
}

- (NSDictionary*) configAsDictionary
{
    NSMutableDictionary     *picDict = [NSMutableDictionary dictionary];
    NSSet                   *pageInstanceConfig = [self valueForKey: @"config"];
    NSEnumerator            *picEn = [pageInstanceConfig objectEnumerator];
    PageInstanceConfig      *pic;
    while ( pic = [picEn nextObject] ) {
        [picDict setObject: [pic valueForKey: @"value"] forKey: [pic valueForKey: @"name"]];
    }
    return picDict;
}

- (NSDictionary*) bindingsAsDictionary
{
    NSMutableDictionary     *bDict = [NSMutableDictionary dictionary];
    NSSet                   *bindings = [self valueForKey: @"bindings"];
    NSEnumerator            *bEn = [bindings objectEnumerator];
    PageInstanceBinding     *b;
    while ( b = [bEn nextObject] ) {
        [bDict setObject: [b bindingAsDictionary] forKey: [b valueForKey: @"bindProperty"]];
    }
    return bDict;
}

- (NSDictionary*) childrenAsDictionary
{
    NSMutableDictionary     *cDict = [NSMutableDictionary dictionary];
    NSSet                   *children = [self valueForKey: @"children"];
    NSEnumerator            *cEn = [children objectEnumerator];
    PageInstance            *cInst;
    while ( cInst = [cEn nextObject] ) {
        [cDict setObject: [cInst instanceAsDictionary] forKey: [cInst valueForKey: @"instanceID"]];
    }
    return cDict;
}

- (NSDictionary*) instanceAsDictionary
{
    NSMutableDictionary     *picDict = [NSMutableDictionary dictionary];
    [picDict setValue: [self valueForKey: @"instanceClass"] forKey: @"class"];
    
    if ([[self valueForKey: @"config"] count] > 0)
    {
        [picDict setValue: [self configAsDictionary] forKey: @"properties"];
    }
    if ([[self valueForKey: @"bindings"] count] > 0)
    {
        [picDict setValue: [self bindingsAsDictionary] forKey: @"bindings"];
    }
    if ([[self valueForKey: @"children"] count] > 0)
    {
        [picDict setValue: [self childrenAsDictionary] forKey: @"children"];
    }
    return picDict;
}

@end
