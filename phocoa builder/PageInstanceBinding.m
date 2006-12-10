//
//  PageInstanceBinding.m
//  PHOCOA Builder
//
//  Created by Alan Pinstein on 10/31/05.
//  Copyright 2005 __MyCompanyName__. All rights reserved.
//

#import "PageInstanceBinding.h"
#import "WFViewPhocoaBuilderIntegration.h"


@implementation PageInstanceBinding

+ (PageInstanceBinding*) bindProperty: (NSString*) bindProperty withSetup: (NSDictionary*) bindingSetup context: (NSManagedObjectContext*) context
{
    PageInstanceBinding  *binding = [NSEntityDescription
											insertNewObjectForEntityForName: @"PageInstanceBinding"
													 inManagedObjectContext: context];
    
    [binding setValue: bindProperty forKey: @"bindProperty"];
    [binding setValue: [bindingSetup objectForKey: @"instanceID"] forKey: @"bindToSharedInstanceID"];
    [binding setValue: [bindingSetup objectForKey: @"controllerKey"] forKey: @"controllerKey"];
    [binding setValue: [bindingSetup objectForKey: @"modelKeyPath"] forKey: @"modelKeyPath"];
    
    NSDictionary    *bindingOptions = [bindingSetup objectForKey: @"options"];
    NSEnumerator    *optionEn = [bindingOptions keyEnumerator];
    NSString        *optionName;
    while (optionName = [optionEn nextObject])
	{
         [[binding mutableSetValueForKey: @"options"] addObject: [PageInstanceBindingOption bindingOption: optionName withValue: [bindingOptions objectForKey: optionName] context: context]];
	}
	 
	return binding;    
}

- (NSDictionary*) bindingAsDictionary
{
    NSMutableDictionary     *bDict = [NSMutableDictionary dictionary];
    [bDict setValue: [self valueForKey: @"bindToSharedInstanceID"] forKey: @"instanceID"];
    [bDict setValue: [self valueForKey: @"controllerKey"] forKey: @"controllerKey"];
    [bDict setValue: [self valueForKey: @"modelKeyPath"] forKey: @"modelKeyPath"];
    
    // binding options
    NSSet       *options = [self valueForKey: @"options"];
    if ([options count] > 0)
    {
        NSMutableDictionary         *optionDict = [NSMutableDictionary dictionary];
        NSEnumerator                *optionEn = [options objectEnumerator];
        PageInstanceBindingOption   *option;
        while ( option = [optionEn nextObject] ) {
            [optionDict setObject: [option valueForKey: @"value"] forKey: [option valueForKey: @"name"]];
        }
        [bDict setValue: optionDict forKey: @"options"];
    }
    
    return bDict;
}
@end
