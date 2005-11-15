//
//  ModuleMainWindowController.m
//  PHOCOA Builder
//
//  Created by Alan Pinstein on 10/30/05.
//  Copyright 2005 __MyCompanyName__. All rights reserved.
//

#import "ModuleMainWindowController.h"
#import "PageInstance.h"
#import "WFViewSubclassList.h"

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

- (void) startSaveProgress
{
    [saveProgress startAnimation: self];
}
- (void) stopSaveProgress
{
    [saveProgress stopAnimation: self];
}

- (NSArray*) subclassList
{
    return [WFViewSubclassList subclasses];
}

// inserts a new binding with default values based on the currently selected shared instance
- (IBAction) insertBinding: (id) sender
{
    NSManagedObjectContext *managedObjectContext = [self managedObjectContext];
    PageInstanceBinding *binding = [NSEntityDescription insertNewObjectForEntityForName:@"PageInstanceBinding"
                                                                 inManagedObjectContext:managedObjectContext];

    // bind to the selected shared instance
    if ([sharedInstancesController selectionIndex] != NSNotFound)
    {
        [binding setValue: [[sharedInstancesController selection] valueForKey: @"instanceID"] forKey: @"bindToSharedInstanceID"];
    }
    [binding setValue: @"value" forKey: @"bindProperty"];
    // if the PageInstance is a prototype, controllerKey should be "#current#", else should be "selection"
    // if the PageInstance is a prototype, modelKeyPath should be the ID of the parent of the selected PageInstance, 
    // else should be the ID of the selected PageInstance
    // NOTE: this assumes the convention that users will name widgets with their KVC names.
    PageInstance *selectedPageInstance = nil;
    if ([pageInstancesController selectionIndexPath])
    {
        selectedPageInstance = [[pageInstancesController selectedObjects] objectAtIndex: 0];
    }
    if (selectedPageInstance && [selectedPageInstance isPrototype])
    {
        [binding setValue: @"#current#" forKey: @"controllerKey"];
        [binding setValue: [[[pageInstancesController selection] valueForKey: @"parent"] valueForKey: @"instanceID"] forKey: @"modelKeyPath"];
    }
    else
    {
        [binding setValue: @"selection" forKey: @"controllerKey"];
        [binding setValue: [[pageInstancesController selection] valueForKey: @"instanceID"] forKey: @"modelKeyPath"];
    }
    // add the binding to the controller
    [pageInstanceBindingController addObject: binding];
}



@end
