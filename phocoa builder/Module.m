//
//  Module.m
//  PHOCOA Builder
//
//  Created by Alan Pinstein on 12/9/06.
//  Copyright 2006 __MyCompanyName__. All rights reserved.
//

#import "Module.h"
#import "YAML/YAML.h"
#import "WFViewPhocoaBuilderIntegration.h"

@implementation Module

+ (void) initialize
{
//    [self setKeys: [NSArray arrayWithObject: @"sharedInstances"] triggerChangeNotificationsForDependentKey: @"bindToSharedInstanceIdList"];
}

- (NSDictionary*) sharedInstancesDictionary
{
    NSMutableDictionary     *siDict = [NSMutableDictionary dictionary];
    NSSet                   *sharedInstances = [self valueForKey: @"sharedInstances"];
    NSEnumerator            *siEn = [sharedInstances objectEnumerator];
    SharedInstance          *si;
    while ( si = [siEn nextObject] ) {
        [siDict setObject: [si toYAML] forKey: [si valueForKey: @"instanceID"]];
    }
    return siDict;
}

- (NSArray*) listOfSharedInstanceIDs
{
    return [[self valueForKeyPath: @"sharedInstances.instanceID"] allObjects];
}

- (void) saveSetupToDirectory: (NSString*) moduleDirPath
{
    // convert shared setup to YAML
    NSString    *sharedYaml = [[self sharedInstancesDictionary] yamlDescription];

    NSError *err;
    BOOL    wroteFile;
    NSString    *sharedYAMLPath = [moduleDirPath stringByAppendingPathComponent: @"shared.yaml"];
    wroteFile = [sharedYaml writeToFile: sharedYAMLPath atomically: YES encoding: NSUTF8StringEncoding error: &err];
    if (!wroteFile)
    {
        NSLog(@"Failed writing %@, reason: %@.", sharedYaml, err);
    }
}
@end
