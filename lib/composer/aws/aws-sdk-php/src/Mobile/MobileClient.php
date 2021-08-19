<?php
namespace Aws\Mobile;

use Aws\AwsClient;

/**
 * This client is used to interact with the **AWS Mobile** service.
 * @method \Aws\Result createProject(array $args = [])
 * @method \GuzzleHttp\Promise\Promise createProjectAsync(array $args = [])
 * @method \Aws\Result deleteProject(array $args = [])
 * @method \GuzzleHttp\Promise\Promise deleteProjectAsync(array $args = [])
 * @method \Aws\Result describeBundle(array $args = [])
 * @method \GuzzleHttp\Promise\Promise describeBundleAsync(array $args = [])
 * @method \Aws\Result describeProject(array $args = [])
 * @method \GuzzleHttp\Promise\Promise describeProjectAsync(array $args = [])
 * @method \Aws\Result exportBundle(array $args = [])
 * @method \GuzzleHttp\Promise\Promise exportBundleAsync(array $args = [])
 * @method \Aws\Result exportProject(array $args = [])
 * @method \GuzzleHttp\Promise\Promise exportProjectAsync(array $args = [])
 * @method \Aws\Result listBundles(array $args = [])
 * @method \GuzzleHttp\Promise\Promise listBundlesAsync(array $args = [])
 * @method \Aws\Result listProjects(array $args = [])
 * @method \GuzzleHttp\Promise\Promise listProjectsAsync(array $args = [])
 * @method \Aws\Result updateProject(array $args = [])
 * @method \GuzzleHttp\Promise\Promise updateProjectAsync(array $args = [])
 */
class MobileClient extends AwsClient {}
