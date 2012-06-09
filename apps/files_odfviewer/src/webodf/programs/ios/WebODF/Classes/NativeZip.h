#import <PhoneGap/PGPlugin.h>

@interface NativeZip : PGPlugin {
     NSString* callbackID;
}

@property (nonatomic, copy) NSString* callbackID;

- (void) load:(BOOL)base64 arguments:(NSMutableArray*)arguments withDict:(NSMutableDictionary*)options;
- (void) loadAsString:(NSMutableArray*)arguments withDict:(NSMutableDictionary*)options;
- (void) loadAsDataURL:(NSMutableArray*)arguments withDict:(NSMutableDictionary*)options;

@end