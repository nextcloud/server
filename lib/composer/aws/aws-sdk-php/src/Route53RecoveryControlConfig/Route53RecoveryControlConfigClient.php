<?php
namespace Aws\Route53RecoveryControlConfig;

use Aws\AwsClient;

/**
 * This client is used to interact with the **AWS Route53 Recovery Control Config** service.
 * @method \Aws\Result createCluster(array $args = [])
 * @method \GuzzleHttp\Promise\Promise createClusterAsync(array $args = [])
 * @method \Aws\Result createControlPanel(array $args = [])
 * @method \GuzzleHttp\Promise\Promise createControlPanelAsync(array $args = [])
 * @method \Aws\Result createRoutingControl(array $args = [])
 * @method \GuzzleHttp\Promise\Promise createRoutingControlAsync(array $args = [])
 * @method \Aws\Result createSafetyRule(array $args = [])
 * @method \GuzzleHttp\Promise\Promise createSafetyRuleAsync(array $args = [])
 * @method \Aws\Result deleteCluster(array $args = [])
 * @method \GuzzleHttp\Promise\Promise deleteClusterAsync(array $args = [])
 * @method \Aws\Result deleteControlPanel(array $args = [])
 * @method \GuzzleHttp\Promise\Promise deleteControlPanelAsync(array $args = [])
 * @method \Aws\Result deleteRoutingControl(array $args = [])
 * @method \GuzzleHttp\Promise\Promise deleteRoutingControlAsync(array $args = [])
 * @method \Aws\Result deleteSafetyRule(array $args = [])
 * @method \GuzzleHttp\Promise\Promise deleteSafetyRuleAsync(array $args = [])
 * @method \Aws\Result describeCluster(array $args = [])
 * @method \GuzzleHttp\Promise\Promise describeClusterAsync(array $args = [])
 * @method \Aws\Result describeControlPanel(array $args = [])
 * @method \GuzzleHttp\Promise\Promise describeControlPanelAsync(array $args = [])
 * @method \Aws\Result describeRoutingControl(array $args = [])
 * @method \GuzzleHttp\Promise\Promise describeRoutingControlAsync(array $args = [])
 * @method \Aws\Result describeSafetyRule(array $args = [])
 * @method \GuzzleHttp\Promise\Promise describeSafetyRuleAsync(array $args = [])
 * @method \Aws\Result listAssociatedRoute53HealthChecks(array $args = [])
 * @method \GuzzleHttp\Promise\Promise listAssociatedRoute53HealthChecksAsync(array $args = [])
 * @method \Aws\Result listClusters(array $args = [])
 * @method \GuzzleHttp\Promise\Promise listClustersAsync(array $args = [])
 * @method \Aws\Result listControlPanels(array $args = [])
 * @method \GuzzleHttp\Promise\Promise listControlPanelsAsync(array $args = [])
 * @method \Aws\Result listRoutingControls(array $args = [])
 * @method \GuzzleHttp\Promise\Promise listRoutingControlsAsync(array $args = [])
 * @method \Aws\Result listSafetyRules(array $args = [])
 * @method \GuzzleHttp\Promise\Promise listSafetyRulesAsync(array $args = [])
 * @method \Aws\Result updateControlPanel(array $args = [])
 * @method \GuzzleHttp\Promise\Promise updateControlPanelAsync(array $args = [])
 * @method \Aws\Result updateRoutingControl(array $args = [])
 * @method \GuzzleHttp\Promise\Promise updateRoutingControlAsync(array $args = [])
 * @method \Aws\Result updateSafetyRule(array $args = [])
 * @method \GuzzleHttp\Promise\Promise updateSafetyRuleAsync(array $args = [])
 */
class Route53RecoveryControlConfigClient extends AwsClient {}
