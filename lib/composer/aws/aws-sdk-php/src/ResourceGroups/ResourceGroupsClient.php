<?php
namespace Aws\ResourceGroups;

use Aws\AwsClient;

/**
 * This client is used to interact with the **AWS Resource Groups** service.
 * @method \Aws\Result createGroup(array $args = [])
 * @method \GuzzleHttp\Promise\Promise createGroupAsync(array $args = [])
 * @method \Aws\Result deleteGroup(array $args = [])
 * @method \GuzzleHttp\Promise\Promise deleteGroupAsync(array $args = [])
 * @method \Aws\Result getGroup(array $args = [])
 * @method \GuzzleHttp\Promise\Promise getGroupAsync(array $args = [])
 * @method \Aws\Result getGroupConfiguration(array $args = [])
 * @method \GuzzleHttp\Promise\Promise getGroupConfigurationAsync(array $args = [])
 * @method \Aws\Result getGroupQuery(array $args = [])
 * @method \GuzzleHttp\Promise\Promise getGroupQueryAsync(array $args = [])
 * @method \Aws\Result getTags(array $args = [])
 * @method \GuzzleHttp\Promise\Promise getTagsAsync(array $args = [])
 * @method \Aws\Result groupResources(array $args = [])
 * @method \GuzzleHttp\Promise\Promise groupResourcesAsync(array $args = [])
 * @method \Aws\Result listGroupResources(array $args = [])
 * @method \GuzzleHttp\Promise\Promise listGroupResourcesAsync(array $args = [])
 * @method \Aws\Result listGroups(array $args = [])
 * @method \GuzzleHttp\Promise\Promise listGroupsAsync(array $args = [])
 * @method \Aws\Result putGroupConfiguration(array $args = [])
 * @method \GuzzleHttp\Promise\Promise putGroupConfigurationAsync(array $args = [])
 * @method \Aws\Result searchResources(array $args = [])
 * @method \GuzzleHttp\Promise\Promise searchResourcesAsync(array $args = [])
 * @method \Aws\Result tag(array $args = [])
 * @method \GuzzleHttp\Promise\Promise tagAsync(array $args = [])
 * @method \Aws\Result ungroupResources(array $args = [])
 * @method \GuzzleHttp\Promise\Promise ungroupResourcesAsync(array $args = [])
 * @method \Aws\Result untag(array $args = [])
 * @method \GuzzleHttp\Promise\Promise untagAsync(array $args = [])
 * @method \Aws\Result updateGroup(array $args = [])
 * @method \GuzzleHttp\Promise\Promise updateGroupAsync(array $args = [])
 * @method \Aws\Result updateGroupQuery(array $args = [])
 * @method \GuzzleHttp\Promise\Promise updateGroupQueryAsync(array $args = [])
 */
class ResourceGroupsClient extends AwsClient {}
