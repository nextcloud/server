<?php
namespace Aws\CloudFront;

use Aws\AwsClient;

/**
 * This client is used to interact with the **Amazon CloudFront** service.
 *
 * @method \Aws\Result createCloudFrontOriginAccessIdentity(array $args = [])
 * @method \GuzzleHttp\Promise\Promise createCloudFrontOriginAccessIdentityAsync(array $args = [])
 * @method \Aws\Result createDistribution(array $args = [])
 * @method \GuzzleHttp\Promise\Promise createDistributionAsync(array $args = [])
 * @method \Aws\Result createInvalidation(array $args = [])
 * @method \GuzzleHttp\Promise\Promise createInvalidationAsync(array $args = [])
 * @method \Aws\Result createStreamingDistribution(array $args = [])
 * @method \GuzzleHttp\Promise\Promise createStreamingDistributionAsync(array $args = [])
 * @method \Aws\Result deleteCloudFrontOriginAccessIdentity(array $args = [])
 * @method \GuzzleHttp\Promise\Promise deleteCloudFrontOriginAccessIdentityAsync(array $args = [])
 * @method \Aws\Result deleteDistribution(array $args = [])
 * @method \GuzzleHttp\Promise\Promise deleteDistributionAsync(array $args = [])
 * @method \Aws\Result deleteStreamingDistribution(array $args = [])
 * @method \GuzzleHttp\Promise\Promise deleteStreamingDistributionAsync(array $args = [])
 * @method \Aws\Result getCloudFrontOriginAccessIdentity(array $args = [])
 * @method \GuzzleHttp\Promise\Promise getCloudFrontOriginAccessIdentityAsync(array $args = [])
 * @method \Aws\Result getCloudFrontOriginAccessIdentityConfig(array $args = [])
 * @method \GuzzleHttp\Promise\Promise getCloudFrontOriginAccessIdentityConfigAsync(array $args = [])
 * @method \Aws\Result getDistribution(array $args = [])
 * @method \GuzzleHttp\Promise\Promise getDistributionAsync(array $args = [])
 * @method \Aws\Result getDistributionConfig(array $args = [])
 * @method \GuzzleHttp\Promise\Promise getDistributionConfigAsync(array $args = [])
 * @method \Aws\Result getInvalidation(array $args = [])
 * @method \GuzzleHttp\Promise\Promise getInvalidationAsync(array $args = [])
 * @method \Aws\Result getStreamingDistribution(array $args = [])
 * @method \GuzzleHttp\Promise\Promise getStreamingDistributionAsync(array $args = [])
 * @method \Aws\Result getStreamingDistributionConfig(array $args = [])
 * @method \GuzzleHttp\Promise\Promise getStreamingDistributionConfigAsync(array $args = [])
 * @method \Aws\Result listCloudFrontOriginAccessIdentities(array $args = [])
 * @method \GuzzleHttp\Promise\Promise listCloudFrontOriginAccessIdentitiesAsync(array $args = [])
 * @method \Aws\Result listDistributions(array $args = [])
 * @method \GuzzleHttp\Promise\Promise listDistributionsAsync(array $args = [])
 * @method \Aws\Result listDistributionsByWebACLId(array $args = [])
 * @method \GuzzleHttp\Promise\Promise listDistributionsByWebACLIdAsync(array $args = [])
 * @method \Aws\Result listInvalidations(array $args = [])
 * @method \GuzzleHttp\Promise\Promise listInvalidationsAsync(array $args = [])
 * @method \Aws\Result listStreamingDistributions(array $args = [])
 * @method \GuzzleHttp\Promise\Promise listStreamingDistributionsAsync(array $args = [])
 * @method \Aws\Result updateCloudFrontOriginAccessIdentity(array $args = [])
 * @method \GuzzleHttp\Promise\Promise updateCloudFrontOriginAccessIdentityAsync(array $args = [])
 * @method \Aws\Result updateDistribution(array $args = [])
 * @method \GuzzleHttp\Promise\Promise updateDistributionAsync(array $args = [])
 * @method \Aws\Result updateStreamingDistribution(array $args = [])
 * @method \GuzzleHttp\Promise\Promise updateStreamingDistributionAsync(array $args = [])
 * @method \Aws\Result createDistributionWithTags(array $args = []) (supported in versions 2016-08-01, 2016-08-20, 2016-09-07, 2016-09-29, 2016-11-25, 2017-03-25, 2017-10-30, 2018-06-18, 2018-11-05, 2019-03-26, 2020-05-31)
 * @method \GuzzleHttp\Promise\Promise createDistributionWithTagsAsync(array $args = []) (supported in versions 2016-08-01, 2016-08-20, 2016-09-07, 2016-09-29, 2016-11-25, 2017-03-25, 2017-10-30, 2018-06-18, 2018-11-05, 2019-03-26, 2020-05-31)
 * @method \Aws\Result createStreamingDistributionWithTags(array $args = []) (supported in versions 2016-08-01, 2016-08-20, 2016-09-07, 2016-09-29, 2016-11-25, 2017-03-25, 2017-10-30, 2018-06-18, 2018-11-05, 2019-03-26, 2020-05-31)
 * @method \GuzzleHttp\Promise\Promise createStreamingDistributionWithTagsAsync(array $args = []) (supported in versions 2016-08-01, 2016-08-20, 2016-09-07, 2016-09-29, 2016-11-25, 2017-03-25, 2017-10-30, 2018-06-18, 2018-11-05, 2019-03-26, 2020-05-31)
 * @method \Aws\Result listTagsForResource(array $args = []) (supported in versions 2016-08-01, 2016-08-20, 2016-09-07, 2016-09-29, 2016-11-25, 2017-03-25, 2017-10-30, 2018-06-18, 2018-11-05, 2019-03-26, 2020-05-31)
 * @method \GuzzleHttp\Promise\Promise listTagsForResourceAsync(array $args = []) (supported in versions 2016-08-01, 2016-08-20, 2016-09-07, 2016-09-29, 2016-11-25, 2017-03-25, 2017-10-30, 2018-06-18, 2018-11-05, 2019-03-26, 2020-05-31)
 * @method \Aws\Result tagResource(array $args = []) (supported in versions 2016-08-01, 2016-08-20, 2016-09-07, 2016-09-29, 2016-11-25, 2017-03-25, 2017-10-30, 2018-06-18, 2018-11-05, 2019-03-26, 2020-05-31)
 * @method \GuzzleHttp\Promise\Promise tagResourceAsync(array $args = []) (supported in versions 2016-08-01, 2016-08-20, 2016-09-07, 2016-09-29, 2016-11-25, 2017-03-25, 2017-10-30, 2018-06-18, 2018-11-05, 2019-03-26, 2020-05-31)
 * @method \Aws\Result untagResource(array $args = []) (supported in versions 2016-08-01, 2016-08-20, 2016-09-07, 2016-09-29, 2016-11-25, 2017-03-25, 2017-10-30, 2018-06-18, 2018-11-05, 2019-03-26, 2020-05-31)
 * @method \GuzzleHttp\Promise\Promise untagResourceAsync(array $args = []) (supported in versions 2016-08-01, 2016-08-20, 2016-09-07, 2016-09-29, 2016-11-25, 2017-03-25, 2017-10-30, 2018-06-18, 2018-11-05, 2019-03-26, 2020-05-31)
 * @method \Aws\Result deleteServiceLinkedRole(array $args = []) (supported in versions 2017-03-25)
 * @method \GuzzleHttp\Promise\Promise deleteServiceLinkedRoleAsync(array $args = []) (supported in versions 2017-03-25)
 * @method \Aws\Result createFieldLevelEncryptionConfig(array $args = []) (supported in versions 2017-10-30, 2018-06-18, 2018-11-05, 2019-03-26, 2020-05-31)
 * @method \GuzzleHttp\Promise\Promise createFieldLevelEncryptionConfigAsync(array $args = []) (supported in versions 2017-10-30, 2018-06-18, 2018-11-05, 2019-03-26, 2020-05-31)
 * @method \Aws\Result createFieldLevelEncryptionProfile(array $args = []) (supported in versions 2017-10-30, 2018-06-18, 2018-11-05, 2019-03-26, 2020-05-31)
 * @method \GuzzleHttp\Promise\Promise createFieldLevelEncryptionProfileAsync(array $args = []) (supported in versions 2017-10-30, 2018-06-18, 2018-11-05, 2019-03-26, 2020-05-31)
 * @method \Aws\Result createPublicKey(array $args = []) (supported in versions 2017-10-30, 2018-06-18, 2018-11-05, 2019-03-26, 2020-05-31)
 * @method \GuzzleHttp\Promise\Promise createPublicKeyAsync(array $args = []) (supported in versions 2017-10-30, 2018-06-18, 2018-11-05, 2019-03-26, 2020-05-31)
 * @method \Aws\Result deleteFieldLevelEncryptionConfig(array $args = []) (supported in versions 2017-10-30, 2018-06-18, 2018-11-05, 2019-03-26, 2020-05-31)
 * @method \GuzzleHttp\Promise\Promise deleteFieldLevelEncryptionConfigAsync(array $args = []) (supported in versions 2017-10-30, 2018-06-18, 2018-11-05, 2019-03-26, 2020-05-31)
 * @method \Aws\Result deleteFieldLevelEncryptionProfile(array $args = []) (supported in versions 2017-10-30, 2018-06-18, 2018-11-05, 2019-03-26, 2020-05-31)
 * @method \GuzzleHttp\Promise\Promise deleteFieldLevelEncryptionProfileAsync(array $args = []) (supported in versions 2017-10-30, 2018-06-18, 2018-11-05, 2019-03-26, 2020-05-31)
 * @method \Aws\Result deletePublicKey(array $args = []) (supported in versions 2017-10-30, 2018-06-18, 2018-11-05, 2019-03-26, 2020-05-31)
 * @method \GuzzleHttp\Promise\Promise deletePublicKeyAsync(array $args = []) (supported in versions 2017-10-30, 2018-06-18, 2018-11-05, 2019-03-26, 2020-05-31)
 * @method \Aws\Result getFieldLevelEncryption(array $args = []) (supported in versions 2017-10-30, 2018-06-18, 2018-11-05, 2019-03-26, 2020-05-31)
 * @method \GuzzleHttp\Promise\Promise getFieldLevelEncryptionAsync(array $args = []) (supported in versions 2017-10-30, 2018-06-18, 2018-11-05, 2019-03-26, 2020-05-31)
 * @method \Aws\Result getFieldLevelEncryptionConfig(array $args = []) (supported in versions 2017-10-30, 2018-06-18, 2018-11-05, 2019-03-26, 2020-05-31)
 * @method \GuzzleHttp\Promise\Promise getFieldLevelEncryptionConfigAsync(array $args = []) (supported in versions 2017-10-30, 2018-06-18, 2018-11-05, 2019-03-26, 2020-05-31)
 * @method \Aws\Result getFieldLevelEncryptionProfile(array $args = []) (supported in versions 2017-10-30, 2018-06-18, 2018-11-05, 2019-03-26, 2020-05-31)
 * @method \GuzzleHttp\Promise\Promise getFieldLevelEncryptionProfileAsync(array $args = []) (supported in versions 2017-10-30, 2018-06-18, 2018-11-05, 2019-03-26, 2020-05-31)
 * @method \Aws\Result getFieldLevelEncryptionProfileConfig(array $args = []) (supported in versions 2017-10-30, 2018-06-18, 2018-11-05, 2019-03-26, 2020-05-31)
 * @method \GuzzleHttp\Promise\Promise getFieldLevelEncryptionProfileConfigAsync(array $args = []) (supported in versions 2017-10-30, 2018-06-18, 2018-11-05, 2019-03-26, 2020-05-31)
 * @method \Aws\Result getPublicKey(array $args = []) (supported in versions 2017-10-30, 2018-06-18, 2018-11-05, 2019-03-26, 2020-05-31)
 * @method \GuzzleHttp\Promise\Promise getPublicKeyAsync(array $args = []) (supported in versions 2017-10-30, 2018-06-18, 2018-11-05, 2019-03-26, 2020-05-31)
 * @method \Aws\Result getPublicKeyConfig(array $args = []) (supported in versions 2017-10-30, 2018-06-18, 2018-11-05, 2019-03-26, 2020-05-31)
 * @method \GuzzleHttp\Promise\Promise getPublicKeyConfigAsync(array $args = []) (supported in versions 2017-10-30, 2018-06-18, 2018-11-05, 2019-03-26, 2020-05-31)
 * @method \Aws\Result listFieldLevelEncryptionConfigs(array $args = []) (supported in versions 2017-10-30, 2018-06-18, 2018-11-05, 2019-03-26, 2020-05-31)
 * @method \GuzzleHttp\Promise\Promise listFieldLevelEncryptionConfigsAsync(array $args = []) (supported in versions 2017-10-30, 2018-06-18, 2018-11-05, 2019-03-26, 2020-05-31)
 * @method \Aws\Result listFieldLevelEncryptionProfiles(array $args = []) (supported in versions 2017-10-30, 2018-06-18, 2018-11-05, 2019-03-26, 2020-05-31)
 * @method \GuzzleHttp\Promise\Promise listFieldLevelEncryptionProfilesAsync(array $args = []) (supported in versions 2017-10-30, 2018-06-18, 2018-11-05, 2019-03-26, 2020-05-31)
 * @method \Aws\Result listPublicKeys(array $args = []) (supported in versions 2017-10-30, 2018-06-18, 2018-11-05, 2019-03-26, 2020-05-31)
 * @method \GuzzleHttp\Promise\Promise listPublicKeysAsync(array $args = []) (supported in versions 2017-10-30, 2018-06-18, 2018-11-05, 2019-03-26, 2020-05-31)
 * @method \Aws\Result updateFieldLevelEncryptionConfig(array $args = []) (supported in versions 2017-10-30, 2018-06-18, 2018-11-05, 2019-03-26, 2020-05-31)
 * @method \GuzzleHttp\Promise\Promise updateFieldLevelEncryptionConfigAsync(array $args = []) (supported in versions 2017-10-30, 2018-06-18, 2018-11-05, 2019-03-26, 2020-05-31)
 * @method \Aws\Result updateFieldLevelEncryptionProfile(array $args = []) (supported in versions 2017-10-30, 2018-06-18, 2018-11-05, 2019-03-26, 2020-05-31)
 * @method \GuzzleHttp\Promise\Promise updateFieldLevelEncryptionProfileAsync(array $args = []) (supported in versions 2017-10-30, 2018-06-18, 2018-11-05, 2019-03-26, 2020-05-31)
 * @method \Aws\Result updatePublicKey(array $args = []) (supported in versions 2017-10-30, 2018-06-18, 2018-11-05, 2019-03-26, 2020-05-31)
 * @method \GuzzleHttp\Promise\Promise updatePublicKeyAsync(array $args = []) (supported in versions 2017-10-30, 2018-06-18, 2018-11-05, 2019-03-26, 2020-05-31)
 * @method \Aws\Result associateAlias(array $args = []) (supported in versions 2020-05-31)
 * @method \GuzzleHttp\Promise\Promise associateAliasAsync(array $args = []) (supported in versions 2020-05-31)
 * @method \Aws\Result createCachePolicy(array $args = []) (supported in versions 2020-05-31)
 * @method \GuzzleHttp\Promise\Promise createCachePolicyAsync(array $args = []) (supported in versions 2020-05-31)
 * @method \Aws\Result createFunction(array $args = []) (supported in versions 2020-05-31)
 * @method \GuzzleHttp\Promise\Promise createFunctionAsync(array $args = []) (supported in versions 2020-05-31)
 * @method \Aws\Result createKeyGroup(array $args = []) (supported in versions 2020-05-31)
 * @method \GuzzleHttp\Promise\Promise createKeyGroupAsync(array $args = []) (supported in versions 2020-05-31)
 * @method \Aws\Result createMonitoringSubscription(array $args = []) (supported in versions 2020-05-31)
 * @method \GuzzleHttp\Promise\Promise createMonitoringSubscriptionAsync(array $args = []) (supported in versions 2020-05-31)
 * @method \Aws\Result createOriginRequestPolicy(array $args = []) (supported in versions 2020-05-31)
 * @method \GuzzleHttp\Promise\Promise createOriginRequestPolicyAsync(array $args = []) (supported in versions 2020-05-31)
 * @method \Aws\Result createRealtimeLogConfig(array $args = []) (supported in versions 2020-05-31)
 * @method \GuzzleHttp\Promise\Promise createRealtimeLogConfigAsync(array $args = []) (supported in versions 2020-05-31)
 * @method \Aws\Result deleteCachePolicy(array $args = []) (supported in versions 2020-05-31)
 * @method \GuzzleHttp\Promise\Promise deleteCachePolicyAsync(array $args = []) (supported in versions 2020-05-31)
 * @method \Aws\Result deleteFunction(array $args = []) (supported in versions 2020-05-31)
 * @method \GuzzleHttp\Promise\Promise deleteFunctionAsync(array $args = []) (supported in versions 2020-05-31)
 * @method \Aws\Result deleteKeyGroup(array $args = []) (supported in versions 2020-05-31)
 * @method \GuzzleHttp\Promise\Promise deleteKeyGroupAsync(array $args = []) (supported in versions 2020-05-31)
 * @method \Aws\Result deleteMonitoringSubscription(array $args = []) (supported in versions 2020-05-31)
 * @method \GuzzleHttp\Promise\Promise deleteMonitoringSubscriptionAsync(array $args = []) (supported in versions 2020-05-31)
 * @method \Aws\Result deleteOriginRequestPolicy(array $args = []) (supported in versions 2020-05-31)
 * @method \GuzzleHttp\Promise\Promise deleteOriginRequestPolicyAsync(array $args = []) (supported in versions 2020-05-31)
 * @method \Aws\Result deleteRealtimeLogConfig(array $args = []) (supported in versions 2020-05-31)
 * @method \GuzzleHttp\Promise\Promise deleteRealtimeLogConfigAsync(array $args = []) (supported in versions 2020-05-31)
 * @method \Aws\Result describeFunction(array $args = []) (supported in versions 2020-05-31)
 * @method \GuzzleHttp\Promise\Promise describeFunctionAsync(array $args = []) (supported in versions 2020-05-31)
 * @method \Aws\Result getCachePolicy(array $args = []) (supported in versions 2020-05-31)
 * @method \GuzzleHttp\Promise\Promise getCachePolicyAsync(array $args = []) (supported in versions 2020-05-31)
 * @method \Aws\Result getCachePolicyConfig(array $args = []) (supported in versions 2020-05-31)
 * @method \GuzzleHttp\Promise\Promise getCachePolicyConfigAsync(array $args = []) (supported in versions 2020-05-31)
 * @method \Aws\Result getFunction(array $args = []) (supported in versions 2020-05-31)
 * @method \GuzzleHttp\Promise\Promise getFunctionAsync(array $args = []) (supported in versions 2020-05-31)
 * @method \Aws\Result getKeyGroup(array $args = []) (supported in versions 2020-05-31)
 * @method \GuzzleHttp\Promise\Promise getKeyGroupAsync(array $args = []) (supported in versions 2020-05-31)
 * @method \Aws\Result getKeyGroupConfig(array $args = []) (supported in versions 2020-05-31)
 * @method \GuzzleHttp\Promise\Promise getKeyGroupConfigAsync(array $args = []) (supported in versions 2020-05-31)
 * @method \Aws\Result getMonitoringSubscription(array $args = []) (supported in versions 2020-05-31)
 * @method \GuzzleHttp\Promise\Promise getMonitoringSubscriptionAsync(array $args = []) (supported in versions 2020-05-31)
 * @method \Aws\Result getOriginRequestPolicy(array $args = []) (supported in versions 2020-05-31)
 * @method \GuzzleHttp\Promise\Promise getOriginRequestPolicyAsync(array $args = []) (supported in versions 2020-05-31)
 * @method \Aws\Result getOriginRequestPolicyConfig(array $args = []) (supported in versions 2020-05-31)
 * @method \GuzzleHttp\Promise\Promise getOriginRequestPolicyConfigAsync(array $args = []) (supported in versions 2020-05-31)
 * @method \Aws\Result getRealtimeLogConfig(array $args = []) (supported in versions 2020-05-31)
 * @method \GuzzleHttp\Promise\Promise getRealtimeLogConfigAsync(array $args = []) (supported in versions 2020-05-31)
 * @method \Aws\Result listCachePolicies(array $args = []) (supported in versions 2020-05-31)
 * @method \GuzzleHttp\Promise\Promise listCachePoliciesAsync(array $args = []) (supported in versions 2020-05-31)
 * @method \Aws\Result listConflictingAliases(array $args = []) (supported in versions 2020-05-31)
 * @method \GuzzleHttp\Promise\Promise listConflictingAliasesAsync(array $args = []) (supported in versions 2020-05-31)
 * @method \Aws\Result listDistributionsByCachePolicyId(array $args = []) (supported in versions 2020-05-31)
 * @method \GuzzleHttp\Promise\Promise listDistributionsByCachePolicyIdAsync(array $args = []) (supported in versions 2020-05-31)
 * @method \Aws\Result listDistributionsByKeyGroup(array $args = []) (supported in versions 2020-05-31)
 * @method \GuzzleHttp\Promise\Promise listDistributionsByKeyGroupAsync(array $args = []) (supported in versions 2020-05-31)
 * @method \Aws\Result listDistributionsByOriginRequestPolicyId(array $args = []) (supported in versions 2020-05-31)
 * @method \GuzzleHttp\Promise\Promise listDistributionsByOriginRequestPolicyIdAsync(array $args = []) (supported in versions 2020-05-31)
 * @method \Aws\Result listDistributionsByRealtimeLogConfig(array $args = []) (supported in versions 2020-05-31)
 * @method \GuzzleHttp\Promise\Promise listDistributionsByRealtimeLogConfigAsync(array $args = []) (supported in versions 2020-05-31)
 * @method \Aws\Result listFunctions(array $args = []) (supported in versions 2020-05-31)
 * @method \GuzzleHttp\Promise\Promise listFunctionsAsync(array $args = []) (supported in versions 2020-05-31)
 * @method \Aws\Result listKeyGroups(array $args = []) (supported in versions 2020-05-31)
 * @method \GuzzleHttp\Promise\Promise listKeyGroupsAsync(array $args = []) (supported in versions 2020-05-31)
 * @method \Aws\Result listOriginRequestPolicies(array $args = []) (supported in versions 2020-05-31)
 * @method \GuzzleHttp\Promise\Promise listOriginRequestPoliciesAsync(array $args = []) (supported in versions 2020-05-31)
 * @method \Aws\Result listRealtimeLogConfigs(array $args = []) (supported in versions 2020-05-31)
 * @method \GuzzleHttp\Promise\Promise listRealtimeLogConfigsAsync(array $args = []) (supported in versions 2020-05-31)
 * @method \Aws\Result publishFunction(array $args = []) (supported in versions 2020-05-31)
 * @method \GuzzleHttp\Promise\Promise publishFunctionAsync(array $args = []) (supported in versions 2020-05-31)
 * @method \Aws\Result testFunction(array $args = []) (supported in versions 2020-05-31)
 * @method \GuzzleHttp\Promise\Promise testFunctionAsync(array $args = []) (supported in versions 2020-05-31)
 * @method \Aws\Result updateCachePolicy(array $args = []) (supported in versions 2020-05-31)
 * @method \GuzzleHttp\Promise\Promise updateCachePolicyAsync(array $args = []) (supported in versions 2020-05-31)
 * @method \Aws\Result updateFunction(array $args = []) (supported in versions 2020-05-31)
 * @method \GuzzleHttp\Promise\Promise updateFunctionAsync(array $args = []) (supported in versions 2020-05-31)
 * @method \Aws\Result updateKeyGroup(array $args = []) (supported in versions 2020-05-31)
 * @method \GuzzleHttp\Promise\Promise updateKeyGroupAsync(array $args = []) (supported in versions 2020-05-31)
 * @method \Aws\Result updateOriginRequestPolicy(array $args = []) (supported in versions 2020-05-31)
 * @method \GuzzleHttp\Promise\Promise updateOriginRequestPolicyAsync(array $args = []) (supported in versions 2020-05-31)
 * @method \Aws\Result updateRealtimeLogConfig(array $args = []) (supported in versions 2020-05-31)
 * @method \GuzzleHttp\Promise\Promise updateRealtimeLogConfigAsync(array $args = []) (supported in versions 2020-05-31)
 */
class CloudFrontClient extends AwsClient
{
    /**
     * Create a signed Amazon CloudFront URL.
     *
     * This method accepts an array of configuration options:
     *
     * - url: (string)  URL of the resource being signed (can include query
     *   string and wildcards). For example: rtmp://s5c39gqb8ow64r.cloudfront.net/videos/mp3_name.mp3
     *   http://d111111abcdef8.cloudfront.net/images/horizon.jpg?size=large&license=yes
     * - policy: (string) JSON policy. Use this option when creating a signed
     *   URL for a custom policy.
     * - expires: (int) UTC Unix timestamp used when signing with a canned
     *   policy. Not required when passing a custom 'policy' option.
     * - key_pair_id: (string) The ID of the key pair used to sign CloudFront
     *   URLs for private distributions.
     * - private_key: (string) The filepath ot the private key used to sign
     *   CloudFront URLs for private distributions.
     *
     * @param array $options Array of configuration options used when signing
     *
     * @return string Signed URL with authentication parameters
     * @throws \InvalidArgumentException if url, key_pair_id, or private_key
     *     were not specified.
     * @link http://docs.aws.amazon.com/AmazonCloudFront/latest/DeveloperGuide/WorkingWithStreamingDistributions.html
     */
    public function getSignedUrl(array $options)
    {
        foreach (['url', 'key_pair_id', 'private_key'] as $required) {
            if (!isset($options[$required])) {
                throw new \InvalidArgumentException("$required is required");
            }
        }

        $urlSigner = new UrlSigner(
            $options['key_pair_id'],
            $options['private_key']
        );

        return $urlSigner->getSignedUrl(
            $options['url'],
            isset($options['expires']) ? $options['expires'] : null,
            isset($options['policy']) ? $options['policy'] : null
        );
    }

    /**
     * Create a signed Amazon CloudFront cookie.
     *
     * This method accepts an array of configuration options:
     *
     * - url: (string)  URL of the resource being signed (can include query
     *   string and wildcards). For example: http://d111111abcdef8.cloudfront.net/images/horizon.jpg?size=large&license=yes
     * - policy: (string) JSON policy. Use this option when creating a signed
     *   URL for a custom policy.
     * - expires: (int) UTC Unix timestamp used when signing with a canned
     *   policy. Not required when passing a custom 'policy' option.
     * - key_pair_id: (string) The ID of the key pair used to sign CloudFront
     *   URLs for private distributions.
     * - private_key: (string) The filepath ot the private key used to sign
     *   CloudFront URLs for private distributions.
     *
     * @param array $options Array of configuration options used when signing
     *
     * @return array Key => value pairs of signed cookies to set
     * @throws \InvalidArgumentException if url, key_pair_id, or private_key
     *     were not specified.
     * @link http://docs.aws.amazon.com/AmazonCloudFront/latest/DeveloperGuide/WorkingWithStreamingDistributions.html
     */
    public function getSignedCookie(array $options)
    {
        foreach (['key_pair_id', 'private_key'] as $required) {
            if (!isset($options[$required])) {
                throw new \InvalidArgumentException("$required is required");
            }
        }

        $cookieSigner = new CookieSigner(
            $options['key_pair_id'],
            $options['private_key']
        );

        return $cookieSigner->getSignedCookie(
            isset($options['url']) ? $options['url'] : null,
            isset($options['expires']) ? $options['expires'] : null,
            isset($options['policy']) ? $options['policy'] : null
        );
    }
}
