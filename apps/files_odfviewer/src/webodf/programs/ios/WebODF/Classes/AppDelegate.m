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
//  AppDelegate.m
//  WebODF
//
//  Created by KO GmbH on 2/2/12.
//  Copyright __MyCompanyName__ 2012. All rights reserved.
//

#import "AppDelegate.h"
#import "MainViewController.h"

#ifdef PHONEGAP_FRAMEWORK
    #import <PhoneGap/PGPlugin.h>
    #import <PhoneGap/PGURLProtocol.h>
#else
    #import "PGPlugin.h"
    #import "PGURLProtocol.h"
#endif
#import "WebViewCache.h"


@implementation AppDelegate

@synthesize invokeString, window, viewController;

- (id) init
{	
	/** If you need to do any extra app-specific initialization, you can do it here
	 *  -jm
	 **/
    NSHTTPCookieStorage *cookieStorage = [NSHTTPCookieStorage sharedHTTPCookieStorage]; 
    [cookieStorage setCookieAcceptPolicy:NSHTTPCookieAcceptPolicyAlways];
    
    [PGURLProtocol registerPGHttpURLProtocol];
    
    return [super init];
}

#pragma UIApplicationDelegate implementation

/**
 * This is main kick off after the app inits, the views and Settings are setup here. (preferred - iOS4 and up)
 */
- (BOOL) application:(UIApplication*)application didFinishLaunchingWithOptions:(NSDictionary*)launchOptions
{    
    NSURL* url = [launchOptions objectForKey:UIApplicationLaunchOptionsURLKey];
    if (url && [url isKindOfClass:[NSURL class]]) {
        self.invokeString = [url absoluteString];
		NSLog(@"WebODF launchOptions = %@", url);
    }    
    
    CGRect screenBounds = [[UIScreen mainScreen] bounds];
    self.window = [[UIWindow alloc] initWithFrame:screenBounds];
    self.window.autoresizesSubviews = YES;
    
    CGRect viewBounds = [[UIScreen mainScreen] applicationFrame];
    
    self.viewController = [[MainViewController alloc] init];
    self.viewController.useSplashScreen = YES;
    self.viewController.wwwFolderName = @"www";
    self.viewController.startPage = @"index.html";
    self.viewController.view.frame = viewBounds;
    
    // over-ride delegates
    self.viewController.webView.delegate = self;
    self.viewController.commandDelegate = self;

    // check whether the current orientation is supported: if it is, keep it, rather than forcing a rotation
    BOOL forceStartupRotation = YES;
    UIDeviceOrientation curDevOrientation = [[UIDevice currentDevice] orientation];
    
    if (UIDeviceOrientationUnknown == curDevOrientation) {
        // UIDevice isn't firing orientation notifications yet… go look at the status bar
        curDevOrientation = (UIDeviceOrientation)[[UIApplication sharedApplication] statusBarOrientation];
    }
    
    if (UIDeviceOrientationIsValidInterfaceOrientation(curDevOrientation)) {
        for (NSNumber *orient in self.viewController.supportedOrientations) {
            if ([orient intValue] == curDevOrientation) {
                forceStartupRotation = NO;
                break;
            }
        }
    }
    
    if (forceStartupRotation) {
        NSLog(@"supportedOrientations: %@", self.viewController.supportedOrientations);
        // The first item in the supportedOrientations array is the start orientation (guaranteed to be at least Portrait)
        UIInterfaceOrientation newOrient = [[self.viewController.supportedOrientations objectAtIndex:0] intValue];
        NSLog(@"AppDelegate forcing status bar to: %d from: %d", newOrient, curDevOrientation);
        [[UIApplication sharedApplication] setStatusBarOrientation:newOrient];
    }
    
    [self.window addSubview:self.viewController.view];
    [self.window makeKeyAndVisible];
    
    
    NSString *path = @"./cache";
    NSUInteger discCapacity = 1*1024*1024;
    NSUInteger memoryCapacity = 0*1024*1024;
    
    WebViewCache *cache =
    [[WebViewCache alloc] initWithMemoryCapacity: memoryCapacity
                                        diskCapacity: discCapacity diskPath:path];
    [NSURLCache setSharedURLCache:cache];

    
    return YES;
}

// this happens while we are running ( in the background, or from within our own app )
// only valid if FooBar.plist specifies a protocol to handle
- (BOOL) application:(UIApplication*)application handleOpenURL:(NSURL*)url 
{
    if (!url) { 
        return NO; 
    }
    
	// calls into javascript global function 'handleOpenURL'
    NSString* jsString = [NSString stringWithFormat:@"handleOpenURL(\"%@\");", url];
    [self.viewController.webView stringByEvaluatingJavaScriptFromString:jsString];
    
    // all plugins will get the notification, and their handlers will be called 
    [[NSNotificationCenter defaultCenter] postNotification:[NSNotification notificationWithName:PGPluginHandleOpenURLNotification object:url]];
    
    return YES;    
}

#pragma PGCommandDelegate implementation

- (id) getCommandInstance:(NSString*)className
{
	return [self.viewController getCommandInstance:className];
}

- (BOOL) execute:(InvokedUrlCommand*)command
{
	return [self.viewController execute:command];
}

- (NSString*) pathForResource:(NSString*)resourcepath;
{
	return [self.viewController pathForResource:resourcepath];
}

#pragma UIWebDelegate implementation

- (void) webViewDidFinishLoad:(UIWebView*) theWebView 
{
	// only valid if FooBar.plist specifies a protocol to handle
	if (self.invokeString)
	{
		// this is passed before the deviceready event is fired, so you can access it in js when you receive deviceready
		NSString* jsString = [NSString stringWithFormat:@"var invokeString = \"%@\";", self.invokeString];
		[theWebView stringByEvaluatingJavaScriptFromString:jsString];
	}
	
	 // Black base color for background matches the native apps
   	theWebView.backgroundColor = [UIColor blackColor];
    
	return [self.viewController webViewDidFinishLoad:theWebView];
}

- (void) webViewDidStartLoad:(UIWebView*)theWebView 
{
	return [self.viewController webViewDidStartLoad:theWebView];
}

- (void) webView:(UIWebView*)theWebView didFailLoadWithError:(NSError*)error 
{
	return [self.viewController webView:theWebView didFailLoadWithError:error];
}

- (BOOL) webView:(UIWebView*)theWebView shouldStartLoadWithRequest:(NSURLRequest*)request navigationType:(UIWebViewNavigationType)navigationType
{
	return [self.viewController webView:theWebView shouldStartLoadWithRequest:request navigationType:navigationType];
}

- (void) dealloc
{
}

@end
