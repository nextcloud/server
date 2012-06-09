//
//  WebCache.h
//  KO Viewer
//
//  Created by Tobias Hintze on 3/5/12.
//  Copyright (c) 2012 KO GmbH. All rights reserved.
//

#import <Foundation/Foundation.h>

@interface WebViewCache : NSURLCache

- (NSData*)getSomeData:(NSString*)zip entry:(NSString*)entry;

@end
