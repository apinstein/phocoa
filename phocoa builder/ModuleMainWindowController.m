//
//  ModuleMainWindowController.m
//  PHOCOA Builder
//
//  Created by Alan Pinstein on 10/30/05.
//  Copyright 2005 __MyCompanyName__. All rights reserved.
//

#import "ModuleMainWindowController.h"
#import "PageInstance.h"
#import "WFViewPhocoaBuilderIntegration.h"

@interface ModuleMainWindowController (Private)
- (void) registerAsObserver;
- (void) unregisterAsObserver;
@end

@implementation ModuleMainWindowController

- (id) init
{
	if (self = [self initWithWindowNibName: @"ModuleDocument"])
	{
		// extra initialization
	}
	return self;
}

- (NSManagedObjectContext*) managedObjectContext
{
	return [[self document] managedObjectContext];
}

- (void) startSaveProgress
{
    [saveProgress startAnimation: self];
}
- (void) stopSaveProgress
{
    [saveProgress stopAnimation: self];
}

- (void)windowDidLoad
{
    // due to some non-understood bug, often there will be a page selected, but the page instances will be "empty" until you
    // touch the page instances UI or de-select and re-select the page
    // thus, for now we clear the selection so that this doesn't occur
    // don't unsderstand why this bug exists for pages but not shared instances
    // could possibly be a bug in the 
    [pageController setSelectedObjects: [NSArray array]];
    [pageController selectNext: nil];
    
    [pageController setSortDescriptors: [NSArray arrayWithObject: [[NSSortDescriptor alloc] initWithKey: @"name" ascending: YES] ]];
    
    [self registerAsObserver];
}

- (void) windowWillClose: (NSNotification *) aNotification
{
    [self unregisterAsObserver];
}

- (NSArray*) subclassList
{
    return [[WFViewPhocoaBuilderIntegration sharedWFViewPhocoaBuilderIntegration] WFViewList];
}


- (NSArray*) controllerKeyList
{
    return [[WFViewPhocoaBuilderIntegration sharedWFViewPhocoaBuilderIntegration] controllerKeyList];
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

#pragma mark -- Detecting changes to page instance class --
- (void) registerAsObserver
{
    [pageInstancesController addObserver: self
           forKeyPath: @"selection.instanceClass" 
              options: nil
              context: NULL];
}

- (void) unregisterAsObserver
{
    [pageInstancesController removeObserver: self forKeyPath: @"selection.instanceClass"];
}

- (void)observeValueForKeyPath:(NSString *)keyPath ofObject:(id)object change:(NSDictionary *)change context:(void *)context
{
    if ([keyPath isEqual:@"selection.instanceClass"]) {
        // send fake selection changed event for the page instances hierarchy controller so that the property list updates
        [[pageInstanceTablePropertyColumn dataCell] removeAllItems];
        if ([pageInstancesController selectionIndexPath])
        {
            [[pageInstanceTablePropertyColumn dataCell] addItemsWithObjectValues: [ [[pageInstancesController selectedObjects] objectAtIndex: 0] instancePropertyList] ];            
        }
    }
}

#pragma mark -- Menu Handling -- 

- (void) showSharedInstancesInspector
{
    [sharedInstancesInspectorWindow makeKeyAndOrderFront: nil];
}

- (void) showPageInstancesInspector
{
    [pageInstancesInspectorWindow makeKeyAndOrderFront: nil];
}

@end
