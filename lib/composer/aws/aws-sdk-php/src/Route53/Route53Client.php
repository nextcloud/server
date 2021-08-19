<?php
namespace Aws\Route53;

use Aws\AwsClient;
use Aws\CommandInterface;
use Psr\Http\Message\RequestInterface;

/**
 * This client is used to interact with the **Amazon Route 53** service.
 *
 * @method \Aws\Result activateKeySigningKey(array $args = [])
 * @method \GuzzleHttp\Promise\Promise activateKeySigningKeyAsync(array $args = [])
 * @method \Aws\Result associateVPCWithHostedZone(array $args = [])
 * @method \GuzzleHttp\Promise\Promise associateVPCWithHostedZoneAsync(array $args = [])
 * @method \Aws\Result changeResourceRecordSets(array $args = [])
 * @method \GuzzleHttp\Promise\Promise changeResourceRecordSetsAsync(array $args = [])
 * @method \Aws\Result changeTagsForResource(array $args = [])
 * @method \GuzzleHttp\Promise\Promise changeTagsForResourceAsync(array $args = [])
 * @method \Aws\Result createHealthCheck(array $args = [])
 * @method \GuzzleHttp\Promise\Promise createHealthCheckAsync(array $args = [])
 * @method \Aws\Result createHostedZone(array $args = [])
 * @method \GuzzleHttp\Promise\Promise createHostedZoneAsync(array $args = [])
 * @method \Aws\Result createKeySigningKey(array $args = [])
 * @method \GuzzleHttp\Promise\Promise createKeySigningKeyAsync(array $args = [])
 * @method \Aws\Result createQueryLoggingConfig(array $args = [])
 * @method \GuzzleHttp\Promise\Promise createQueryLoggingConfigAsync(array $args = [])
 * @method \Aws\Result createReusableDelegationSet(array $args = [])
 * @method \GuzzleHttp\Promise\Promise createReusableDelegationSetAsync(array $args = [])
 * @method \Aws\Result createTrafficPolicy(array $args = [])
 * @method \GuzzleHttp\Promise\Promise createTrafficPolicyAsync(array $args = [])
 * @method \Aws\Result createTrafficPolicyInstance(array $args = [])
 * @method \GuzzleHttp\Promise\Promise createTrafficPolicyInstanceAsync(array $args = [])
 * @method \Aws\Result createTrafficPolicyVersion(array $args = [])
 * @method \GuzzleHttp\Promise\Promise createTrafficPolicyVersionAsync(array $args = [])
 * @method \Aws\Result createVPCAssociationAuthorization(array $args = [])
 * @method \GuzzleHttp\Promise\Promise createVPCAssociationAuthorizationAsync(array $args = [])
 * @method \Aws\Result deactivateKeySigningKey(array $args = [])
 * @method \GuzzleHttp\Promise\Promise deactivateKeySigningKeyAsync(array $args = [])
 * @method \Aws\Result deleteHealthCheck(array $args = [])
 * @method \GuzzleHttp\Promise\Promise deleteHealthCheckAsync(array $args = [])
 * @method \Aws\Result deleteHostedZone(array $args = [])
 * @method \GuzzleHttp\Promise\Promise deleteHostedZoneAsync(array $args = [])
 * @method \Aws\Result deleteKeySigningKey(array $args = [])
 * @method \GuzzleHttp\Promise\Promise deleteKeySigningKeyAsync(array $args = [])
 * @method \Aws\Result deleteQueryLoggingConfig(array $args = [])
 * @method \GuzzleHttp\Promise\Promise deleteQueryLoggingConfigAsync(array $args = [])
 * @method \Aws\Result deleteReusableDelegationSet(array $args = [])
 * @method \GuzzleHttp\Promise\Promise deleteReusableDelegationSetAsync(array $args = [])
 * @method \Aws\Result deleteTrafficPolicy(array $args = [])
 * @method \GuzzleHttp\Promise\Promise deleteTrafficPolicyAsync(array $args = [])
 * @method \Aws\Result deleteTrafficPolicyInstance(array $args = [])
 * @method \GuzzleHttp\Promise\Promise deleteTrafficPolicyInstanceAsync(array $args = [])
 * @method \Aws\Result deleteVPCAssociationAuthorization(array $args = [])
 * @method \GuzzleHttp\Promise\Promise deleteVPCAssociationAuthorizationAsync(array $args = [])
 * @method \Aws\Result disableHostedZoneDNSSEC(array $args = [])
 * @method \GuzzleHttp\Promise\Promise disableHostedZoneDNSSECAsync(array $args = [])
 * @method \Aws\Result disassociateVPCFromHostedZone(array $args = [])
 * @method \GuzzleHttp\Promise\Promise disassociateVPCFromHostedZoneAsync(array $args = [])
 * @method \Aws\Result enableHostedZoneDNSSEC(array $args = [])
 * @method \GuzzleHttp\Promise\Promise enableHostedZoneDNSSECAsync(array $args = [])
 * @method \Aws\Result getAccountLimit(array $args = [])
 * @method \GuzzleHttp\Promise\Promise getAccountLimitAsync(array $args = [])
 * @method \Aws\Result getChange(array $args = [])
 * @method \GuzzleHttp\Promise\Promise getChangeAsync(array $args = [])
 * @method \Aws\Result getCheckerIpRanges(array $args = [])
 * @method \GuzzleHttp\Promise\Promise getCheckerIpRangesAsync(array $args = [])
 * @method \Aws\Result getDNSSEC(array $args = [])
 * @method \GuzzleHttp\Promise\Promise getDNSSECAsync(array $args = [])
 * @method \Aws\Result getGeoLocation(array $args = [])
 * @method \GuzzleHttp\Promise\Promise getGeoLocationAsync(array $args = [])
 * @method \Aws\Result getHealthCheck(array $args = [])
 * @method \GuzzleHttp\Promise\Promise getHealthCheckAsync(array $args = [])
 * @method \Aws\Result getHealthCheckCount(array $args = [])
 * @method \GuzzleHttp\Promise\Promise getHealthCheckCountAsync(array $args = [])
 * @method \Aws\Result getHealthCheckLastFailureReason(array $args = [])
 * @method \GuzzleHttp\Promise\Promise getHealthCheckLastFailureReasonAsync(array $args = [])
 * @method \Aws\Result getHealthCheckStatus(array $args = [])
 * @method \GuzzleHttp\Promise\Promise getHealthCheckStatusAsync(array $args = [])
 * @method \Aws\Result getHostedZone(array $args = [])
 * @method \GuzzleHttp\Promise\Promise getHostedZoneAsync(array $args = [])
 * @method \Aws\Result getHostedZoneCount(array $args = [])
 * @method \GuzzleHttp\Promise\Promise getHostedZoneCountAsync(array $args = [])
 * @method \Aws\Result getHostedZoneLimit(array $args = [])
 * @method \GuzzleHttp\Promise\Promise getHostedZoneLimitAsync(array $args = [])
 * @method \Aws\Result getQueryLoggingConfig(array $args = [])
 * @method \GuzzleHttp\Promise\Promise getQueryLoggingConfigAsync(array $args = [])
 * @method \Aws\Result getReusableDelegationSet(array $args = [])
 * @method \GuzzleHttp\Promise\Promise getReusableDelegationSetAsync(array $args = [])
 * @method \Aws\Result getReusableDelegationSetLimit(array $args = [])
 * @method \GuzzleHttp\Promise\Promise getReusableDelegationSetLimitAsync(array $args = [])
 * @method \Aws\Result getTrafficPolicy(array $args = [])
 * @method \GuzzleHttp\Promise\Promise getTrafficPolicyAsync(array $args = [])
 * @method \Aws\Result getTrafficPolicyInstance(array $args = [])
 * @method \GuzzleHttp\Promise\Promise getTrafficPolicyInstanceAsync(array $args = [])
 * @method \Aws\Result getTrafficPolicyInstanceCount(array $args = [])
 * @method \GuzzleHttp\Promise\Promise getTrafficPolicyInstanceCountAsync(array $args = [])
 * @method \Aws\Result listGeoLocations(array $args = [])
 * @method \GuzzleHttp\Promise\Promise listGeoLocationsAsync(array $args = [])
 * @method \Aws\Result listHealthChecks(array $args = [])
 * @method \GuzzleHttp\Promise\Promise listHealthChecksAsync(array $args = [])
 * @method \Aws\Result listHostedZones(array $args = [])
 * @method \GuzzleHttp\Promise\Promise listHostedZonesAsync(array $args = [])
 * @method \Aws\Result listHostedZonesByName(array $args = [])
 * @method \GuzzleHttp\Promise\Promise listHostedZonesByNameAsync(array $args = [])
 * @method \Aws\Result listHostedZonesByVPC(array $args = [])
 * @method \GuzzleHttp\Promise\Promise listHostedZonesByVPCAsync(array $args = [])
 * @method \Aws\Result listQueryLoggingConfigs(array $args = [])
 * @method \GuzzleHttp\Promise\Promise listQueryLoggingConfigsAsync(array $args = [])
 * @method \Aws\Result listResourceRecordSets(array $args = [])
 * @method \GuzzleHttp\Promise\Promise listResourceRecordSetsAsync(array $args = [])
 * @method \Aws\Result listReusableDelegationSets(array $args = [])
 * @method \GuzzleHttp\Promise\Promise listReusableDelegationSetsAsync(array $args = [])
 * @method \Aws\Result listTagsForResource(array $args = [])
 * @method \GuzzleHttp\Promise\Promise listTagsForResourceAsync(array $args = [])
 * @method \Aws\Result listTagsForResources(array $args = [])
 * @method \GuzzleHttp\Promise\Promise listTagsForResourcesAsync(array $args = [])
 * @method \Aws\Result listTrafficPolicies(array $args = [])
 * @method \GuzzleHttp\Promise\Promise listTrafficPoliciesAsync(array $args = [])
 * @method \Aws\Result listTrafficPolicyInstances(array $args = [])
 * @method \GuzzleHttp\Promise\Promise listTrafficPolicyInstancesAsync(array $args = [])
 * @method \Aws\Result listTrafficPolicyInstancesByHostedZone(array $args = [])
 * @method \GuzzleHttp\Promise\Promise listTrafficPolicyInstancesByHostedZoneAsync(array $args = [])
 * @method \Aws\Result listTrafficPolicyInstancesByPolicy(array $args = [])
 * @method \GuzzleHttp\Promise\Promise listTrafficPolicyInstancesByPolicyAsync(array $args = [])
 * @method \Aws\Result listTrafficPolicyVersions(array $args = [])
 * @method \GuzzleHttp\Promise\Promise listTrafficPolicyVersionsAsync(array $args = [])
 * @method \Aws\Result listVPCAssociationAuthorizations(array $args = [])
 * @method \GuzzleHttp\Promise\Promise listVPCAssociationAuthorizationsAsync(array $args = [])
 * @method \Aws\Result testDNSAnswer(array $args = [])
 * @method \GuzzleHttp\Promise\Promise testDNSAnswerAsync(array $args = [])
 * @method \Aws\Result updateHealthCheck(array $args = [])
 * @method \GuzzleHttp\Promise\Promise updateHealthCheckAsync(array $args = [])
 * @method \Aws\Result updateHostedZoneComment(array $args = [])
 * @method \GuzzleHttp\Promise\Promise updateHostedZoneCommentAsync(array $args = [])
 * @method \Aws\Result updateTrafficPolicyComment(array $args = [])
 * @method \GuzzleHttp\Promise\Promise updateTrafficPolicyCommentAsync(array $args = [])
 * @method \Aws\Result updateTrafficPolicyInstance(array $args = [])
 * @method \GuzzleHttp\Promise\Promise updateTrafficPolicyInstanceAsync(array $args = [])
 */
class Route53Client extends AwsClient
{
    public function __construct(array $args)
    {
        parent::__construct($args);
        $this->getHandlerList()->appendInit($this->cleanIdFn(), 'route53.clean_id');
    }

    private function cleanIdFn()
    {
        return function (callable $handler) {
            return function (CommandInterface $c, RequestInterface $r = null) use ($handler) {
                foreach (['Id', 'HostedZoneId', 'DelegationSetId'] as $clean) {
                    if ($c->hasParam($clean)) {
                        $c[$clean] = $this->cleanId($c[$clean]);
                    }
                }
                return $handler($c, $r);
            };
        };
    }

    private function cleanId($id)
    {
        static $toClean = ['/hostedzone/', '/change/', '/delegationset/'];

        return str_replace($toClean, '', $id);
    }
}
