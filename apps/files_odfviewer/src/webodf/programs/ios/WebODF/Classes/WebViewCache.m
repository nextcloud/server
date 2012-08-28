//
//  WebCache.m
//  KO Viewer
//
//  Created by Tobias Hintze on 3/5/12.
//  Copyright (c) 2012 KO GmbH. All rights reserved.
//

#import "WebViewCache.h"
#import "minizip/unzip.h"

@implementation WebViewCache

- (NSCachedURLResponse*)cachedResponseForRequest:(NSURLRequest*)request
{
    [super removeAllCachedResponses];
    NSURL *url = [request URL];
        if ([url query]) {
        NSData *somedata = [self getSomeData:[url path] entry:[url query]];
        NSHTTPURLResponse *response = [[NSHTTPURLResponse alloc] initWithURL:url
                              MIMEType:@"text/xml"
                 expectedContentLength:[somedata length]
                      textEncodingName:nil];
        NSCachedURLResponse *cachedResponse = [[NSCachedURLResponse alloc] 
            initWithResponse:response data:somedata];
        return cachedResponse;
    }
    return [super cachedResponseForRequest:request];
}

- (NSData*)getSomeData:(NSString*)zip entry:(NSString*)entry
{
    NSLog(@"get some data: %@ %@", zip, entry);
    const char* path = [ zip cStringUsingEncoding:NSUTF8StringEncoding ];
    unzFile unzipFile = unzOpen(path);
    NSData *data = nil;
    if (!unzipFile) {
		NSLog(@"cannot open file %@", zip);
    } else {
        path = [ entry cStringUsingEncoding:NSUTF8StringEncoding ];
        int r = unzLocateFile(unzipFile, path, 2);
        if (r != UNZ_OK) {
		    NSLog(@"cannot find entry %@", entry);
        } else {
            unz_file_info info;
            r = unzGetCurrentFileInfo(unzipFile, &info, 0, 0, 0, 0, 0, 0);
            if (r != UNZ_OK) {
		        NSLog(@"cannot determine size of %@", entry);
            } else {
                r = unzOpenCurrentFile(unzipFile);
                if (r != UNZ_OK) {
		            NSLog(@"cannot open entry %@", entry);
                } else {
                    char* contents = malloc(info.uncompressed_size);
                    r = unzReadCurrentFile(unzipFile, contents, info.uncompressed_size);
                    if (r != info.uncompressed_size) {
                        NSLog(@"cannot uncompress file %@", entry);
                    } else {
                        data = [NSData dataWithBytes:(const void *)contents length:sizeof(unsigned char)*info.uncompressed_size];
                        NSLog(@"read file entry %li %@", info.uncompressed_size, entry);
                    }
                    unzCloseCurrentFile(unzipFile);
                    free(contents);
                }
            }
        }
        unzClose(unzipFile);
    }
    return data;
}

@end
