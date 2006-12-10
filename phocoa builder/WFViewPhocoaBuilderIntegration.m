//
//  WFViewPhocoaBuilderIntegration.m
//  PHOCOA Builder
//
//  Created by Alan Pinstein on 12/10/06.
//  Copyright 2006 __MyCompanyName__. All rights reserved.
//

#import "WFViewPhocoaBuilderIntegration.h"
#import "Module.h"

@interface WFViewPhocoaBuilderIntegration (private)
- (void) setIntegrationInfo: (NSDictionary*) info;
@end

@implementation WFViewPhocoaBuilderIntegration

- (id) init
{
    self = [super init];
    
    // load info from plist
    NSString        *plistPath = [[NSBundle mainBundle] pathForResource: @"WFViewPhocoaBuilderIntegration" ofType: @"plist"];
    NSData          *plistData = [NSData dataWithContentsOfFile: plistPath];
    NSString        *errString;
    [self setIntegrationInfo: [NSPropertyListSerialization propertyListFromData: plistData 
                                                                   mutabilityOption: NSPropertyListImmutable
                                                                             format: NULL
                                                                   errorDescription: &errString]];
    return self;
}

- (void) dealloc
{
    [self setIntegrationInfo: nil];
    [super dealloc];
}

- (NSDictionary*) integrationInfo
{
    return integrationInfo;
}
- (void) setIntegrationInfo: (NSDictionary*) info
{
    if (info != integrationInfo)
    {
        [integrationInfo release];
        integrationInfo = info;
        [integrationInfo retain];
    }
}

- (NSArray*) propertyListForClass: (NSString*) className
{
    NSDictionary    *classProps = [[self integrationInfo] valueForKeyPath: [NSString stringWithFormat: @"%@.properties", className]];
    return [classProps allKeys];
}

- (NSArray*) propertyListValuesForClass: (NSString*) className property: (NSString*) propertyName
{
    return [[self integrationInfo] valueForKeyPath: [NSString stringWithFormat: @"%@.properties.%@", className, propertyName]];
}

- (NSArray*) bindingListForClass: (NSString*) class
{
    return [[self integrationInfo] valueForKeyPath: [NSString stringWithFormat: @"%@.bindings", class]];
}

- (NSArray*) bindToSharedInstanceIdList
{
    // should be #module#, #custom#, #current# plus name of all shared instances
    NSMutableArray  *instanceList = [NSMutableArray arrayWithObjects: @"#module#", @"#custom#", @"#current#", nil];
    NSDocumentController    *dc = [NSDocumentController sharedDocumentController];
    id          module = [dc currentDocument];
    [instanceList addObjectsFromArray: [[module valueForKey: @"module"] listOfSharedInstanceIDs]
        ];
    return instanceList;
}

- (NSArray*) controllerKeyList
{
    return [NSArray arrayWithObjects: @"content", @"selection", @"selectedObjects", @"arrangedObjects" , nil];
}

- (NSArray*) WFViewList
{
    return [integrationInfo allKeys];
}


+ (WFViewPhocoaBuilderIntegration*) sharedWFViewPhocoaBuilderIntegration
{
    static WFViewPhocoaBuilderIntegration   *instance = nil;
    if (!instance)
    {
        // singleton instantiator
        instance = [[WFViewPhocoaBuilderIntegration alloc] init];
    }
    return instance;
}

@end
