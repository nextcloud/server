<?php
namespace Aws\MigrationHub;

use Aws\AwsClient;

/**
 * This client is used to interact with the **AWS Migration Hub** service.
 * @method \Aws\Result associateCreatedArtifact(array $args = [])
 * @method \GuzzleHttp\Promise\Promise associateCreatedArtifactAsync(array $args = [])
 * @method \Aws\Result associateDiscoveredResource(array $args = [])
 * @method \GuzzleHttp\Promise\Promise associateDiscoveredResourceAsync(array $args = [])
 * @method \Aws\Result createProgressUpdateStream(array $args = [])
 * @method \GuzzleHttp\Promise\Promise createProgressUpdateStreamAsync(array $args = [])
 * @method \Aws\Result deleteProgressUpdateStream(array $args = [])
 * @method \GuzzleHttp\Promise\Promise deleteProgressUpdateStreamAsync(array $args = [])
 * @method \Aws\Result describeApplicationState(array $args = [])
 * @method \GuzzleHttp\Promise\Promise describeApplicationStateAsync(array $args = [])
 * @method \Aws\Result describeMigrationTask(array $args = [])
 * @method \GuzzleHttp\Promise\Promise describeMigrationTaskAsync(array $args = [])
 * @method \Aws\Result disassociateCreatedArtifact(array $args = [])
 * @method \GuzzleHttp\Promise\Promise disassociateCreatedArtifactAsync(array $args = [])
 * @method \Aws\Result disassociateDiscoveredResource(array $args = [])
 * @method \GuzzleHttp\Promise\Promise disassociateDiscoveredResourceAsync(array $args = [])
 * @method \Aws\Result importMigrationTask(array $args = [])
 * @method \GuzzleHttp\Promise\Promise importMigrationTaskAsync(array $args = [])
 * @method \Aws\Result listApplicationStates(array $args = [])
 * @method \GuzzleHttp\Promise\Promise listApplicationStatesAsync(array $args = [])
 * @method \Aws\Result listCreatedArtifacts(array $args = [])
 * @method \GuzzleHttp\Promise\Promise listCreatedArtifactsAsync(array $args = [])
 * @method \Aws\Result listDiscoveredResources(array $args = [])
 * @method \GuzzleHttp\Promise\Promise listDiscoveredResourcesAsync(array $args = [])
 * @method \Aws\Result listMigrationTasks(array $args = [])
 * @method \GuzzleHttp\Promise\Promise listMigrationTasksAsync(array $args = [])
 * @method \Aws\Result listProgressUpdateStreams(array $args = [])
 * @method \GuzzleHttp\Promise\Promise listProgressUpdateStreamsAsync(array $args = [])
 * @method \Aws\Result notifyApplicationState(array $args = [])
 * @method \GuzzleHttp\Promise\Promise notifyApplicationStateAsync(array $args = [])
 * @method \Aws\Result notifyMigrationTaskState(array $args = [])
 * @method \GuzzleHttp\Promise\Promise notifyMigrationTaskStateAsync(array $args = [])
 * @method \Aws\Result putResourceAttributes(array $args = [])
 * @method \GuzzleHttp\Promise\Promise putResourceAttributesAsync(array $args = [])
 */
class MigrationHubClient extends AwsClient {}
