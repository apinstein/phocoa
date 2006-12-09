//  MyDocument.h
//  PHOCOA Builder
//
//  Created by Alan Pinstein on 10/26/05.
//  Copyright __MyCompanyName__ 2005 . All rights reserved.

#import <Cocoa/Cocoa.h>

#import "Module.h"
#import "SharedInstance.h"
#import "Page.h"
#import "PageInstance.h"
#import "ModuleMainWindowController.h"

@interface ModuleDocument : NSPersistentDocument {
    Module                      *module;
    ModuleMainWindowController  *mainWindow;
}

@end
