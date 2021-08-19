<?php
namespace Aws\AppRegistry;

use Aws\AwsClient;

/**
 * This client is used to interact with the **AWS Service Catalog App Registry** service.
 * @method \Aws\Result associateAttributeGroup(array $args = [])
 * @method \GuzzleHttp\Promise\Promise associateAttributeGroupAsync(array $args = [])
 * @method \Aws\Result associateResource(array $args = [])
 * @method \GuzzleHttp\Promise\Promise associateResourceAsync(array $args = [])
 * @method \Aws\Result createApplication(array $args = [])
 * @method \GuzzleHttp\Promise\Promise createApplicationAsync(array $args = [])
 * @method \Aws\Result createAttributeGroup(array $args = [])
 * @method \GuzzleHttp\Promise\Promise createAttributeGroupAsync(array $args = [])
 * @method \Aws\Result deleteApplication(array $args = [])
 * @method \GuzzleHttp\Promise\Promise deleteApplicationAsync(array $args = [])
 * @method \Aws\Result deleteAttributeGroup(array $args = [])
 * @method \GuzzleHttp\Promise\Promise deleteAttributeGroupAsync(array $args = [])
 * @method \Aws\Result disassociateAttributeGroup(array $args = [])
 * @method \GuzzleHttp\Promise\Promise disassociateAttributeGroupAsync(array $args = [])
 * @method \Aws\Result disassociateResource(array $args = [])
 * @method \GuzzleHttp\Promise\Promise disassociateResourceAsync(array $args = [])
 * @method \Aws\Result getApplication(array $args = [])
 * @method \GuzzleHttp\Promise\Promise getApplicationAsync(array $args = [])
 * @method \Aws\Result getAttributeGroup(array $args = [])
 * @method \GuzzleHttp\Promise\Promise getAttributeGroupAsync(array $args = [])
 * @method \Aws\Result listApplications(array $args = [])
 * @method \GuzzleHttp\Promise\Promise listApplicationsAsync(array $args = [])
 * @method \Aws\Result listAssociatedAttributeGroups(array $args = [])
 * @method \GuzzleHttp\Promise\Promise listAssociatedAttributeGroupsAsync(array $args = [])
 * @method \Aws\Result listAssociatedResources(array $args = [])
 * @method \GuzzleHttp\Promise\Promise listAssociatedResourcesAsync(array $args = [])
 * @method \Aws\Result listAttributeGroups(array $args = [])
 * @method \GuzzleHttp\Promise\Promise listAttributeGroupsAsync(array $args = [])
 * @method \Aws\Result listTagsForResource(array $args = [])
 * @method \GuzzleHttp\Promise\Promise listTagsForResourceAsync(array $args = [])
 * @method \Aws\Result syncResource(array $args = [])
 * @method \GuzzleHttp\Promise\Promise syncResourceAsync(array $args = [])
 * @method \Aws\Result tagResource(array $args = [])
 * @method \GuzzleHttp\Promise\Promise tagResourceAsync(array $args = [])
 * @method \Aws\Result untagResource(array $args = [])
 * @method \GuzzleHttp\Promise\Promise untagResourceAsync(array $args = [])
 * @method \Aws\Result updateApplication(array $args = [])
 * @method \GuzzleHttp\Promise\Promise updateApplicationAsync(array $args = [])
 * @method \Aws\Result updateAttributeGroup(array $args = [])
 * @method \GuzzleHttp\Promise\Promise updateAttributeGroupAsync(array $args = [])
 */
class AppRegistryClient extends AwsClient {}
