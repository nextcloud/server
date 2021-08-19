<?php
namespace Aws\IoT1ClickProjects;

use Aws\AwsClient;

/**
 * This client is used to interact with the **AWS IoT 1-Click Projects Service** service.
 * @method \Aws\Result associateDeviceWithPlacement(array $args = [])
 * @method \GuzzleHttp\Promise\Promise associateDeviceWithPlacementAsync(array $args = [])
 * @method \Aws\Result createPlacement(array $args = [])
 * @method \GuzzleHttp\Promise\Promise createPlacementAsync(array $args = [])
 * @method \Aws\Result createProject(array $args = [])
 * @method \GuzzleHttp\Promise\Promise createProjectAsync(array $args = [])
 * @method \Aws\Result deletePlacement(array $args = [])
 * @method \GuzzleHttp\Promise\Promise deletePlacementAsync(array $args = [])
 * @method \Aws\Result deleteProject(array $args = [])
 * @method \GuzzleHttp\Promise\Promise deleteProjectAsync(array $args = [])
 * @method \Aws\Result describePlacement(array $args = [])
 * @method \GuzzleHttp\Promise\Promise describePlacementAsync(array $args = [])
 * @method \Aws\Result describeProject(array $args = [])
 * @method \GuzzleHttp\Promise\Promise describeProjectAsync(array $args = [])
 * @method \Aws\Result disassociateDeviceFromPlacement(array $args = [])
 * @method \GuzzleHttp\Promise\Promise disassociateDeviceFromPlacementAsync(array $args = [])
 * @method \Aws\Result getDevicesInPlacement(array $args = [])
 * @method \GuzzleHttp\Promise\Promise getDevicesInPlacementAsync(array $args = [])
 * @method \Aws\Result listPlacements(array $args = [])
 * @method \GuzzleHttp\Promise\Promise listPlacementsAsync(array $args = [])
 * @method \Aws\Result listProjects(array $args = [])
 * @method \GuzzleHttp\Promise\Promise listProjectsAsync(array $args = [])
 * @method \Aws\Result listTagsForResource(array $args = [])
 * @method \GuzzleHttp\Promise\Promise listTagsForResourceAsync(array $args = [])
 * @method \Aws\Result tagResource(array $args = [])
 * @method \GuzzleHttp\Promise\Promise tagResourceAsync(array $args = [])
 * @method \Aws\Result untagResource(array $args = [])
 * @method \GuzzleHttp\Promise\Promise untagResourceAsync(array $args = [])
 * @method \Aws\Result updatePlacement(array $args = [])
 * @method \GuzzleHttp\Promise\Promise updatePlacementAsync(array $args = [])
 * @method \Aws\Result updateProject(array $args = [])
 * @method \GuzzleHttp\Promise\Promise updateProjectAsync(array $args = [])
 */
class IoT1ClickProjectsClient extends AwsClient {}
