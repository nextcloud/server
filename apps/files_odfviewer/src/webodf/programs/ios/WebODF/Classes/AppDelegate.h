/*
 Licensed to the Apache Software Foundation (ASF) under one
 or more contributor license agreements.  See the NOTICE file
 distributed with this work for additional information
 regarding copyright ownership.  The ASF licenses this file
 to you under the Apache License, Version 2.0 (the
 "License"); you may not use this file except in compliance
 with the License.  You may obtain a copy of the License at
 
 http://www.apache.org/licenses/LICENSE-2.0
 
 Unless required by applicable law or agreed to in writing,
 software distributed under the License is distributed on an
 "AS IS" BASIS, WITHOUT WARRANTIES OR CONDITIONS OF ANY
 KIND, either express or implied.  See the License for the
 specific language governing permissions and limitations
 under the License.
 */

//
//  AppDelegate.h
//  WebODF
//
//  Created by KO GmbH on 2/2/12.
//  Copyright __MyCompanyName__ 2012. All rights reserved.
//

#import <UIKit/UIKit.h>

#ifdef PHONEGAP_FRAMEWORK
    #import <PhoneGap/PGViewController.h>
#else
    #import "PGViewController.h"
#endif


@interface AppDelegate : NSObject < UIApplicationDelegate, UIWebViewDelegate, PGCommandDelegate > {

	NSString* invokeString;
}

// invoke string is passed to your app on launch, this is only valid if you 
// edit FooBar.plist to add a protocol
// a simple tutorial can be found here : 
// http://iphonedevelopertips.com/cocoa/launching-your-own-application-via-a-custom-url-scheme.html

@property (nonatomic, copy)  NSString* invokeString;
@property (nonatomic, retain) IBOutlet UIWindow* window;
@property (nonatomic, retain) IBOutlet PGViewController* viewController;

@end

