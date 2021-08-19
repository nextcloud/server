<?php
namespace Aws\OpsWorksCM;

use Aws\AwsClient;

/**
 * This client is used to interact with the **AWS OpsWorks for Chef Automate** service.
 * @method \Aws\Result associateNode(array $args = [])
 * @method \GuzzleHttp\Promise\Promise associateNodeAsync(array $args = [])
 * @method \Aws\Result createBackup(array $args = [])
 * @method \GuzzleHttp\Promise\Promise createBackupAsync(array $args = [])
 * @method \Aws\Result createServer(array $args = [])
 * @method \GuzzleHttp\Promise\Promise createServerAsync(array $args = [])
 * @method \Aws\Result deleteBackup(array $args = [])
 * @method \GuzzleHttp\Promise\Promise deleteBackupAsync(array $args = [])
 * @method \Aws\Result deleteServer(array $args = [])
 * @method \GuzzleHttp\Promise\Promise deleteServerAsync(array $args = [])
 * @method \Aws\Result describeAccountAttributes(array $args = [])
 * @method \GuzzleHttp\Promise\Promise describeAccountAttributesAsync(array $args = [])
 * @method \Aws\Result describeBackups(array $args = [])
 * @method \GuzzleHttp\Promise\Promise describeBackupsAsync(array $args = [])
 * @method \Aws\Result describeEvents(array $args = [])
 * @method \GuzzleHttp\Promise\Promise describeEventsAsync(array $args = [])
 * @method \Aws\Result describeNodeAssociationStatus(array $args = [])
 * @method \GuzzleHttp\Promise\Promise describeNodeAssociationStatusAsync(array $args = [])
 * @method \Aws\Result describeServers(array $args = [])
 * @method \GuzzleHttp\Promise\Promise describeServersAsync(array $args = [])
 * @method \Aws\Result disassociateNode(array $args = [])
 * @method \GuzzleHttp\Promise\Promise disassociateNodeAsync(array $args = [])
 * @method \Aws\Result exportServerEngineAttribute(array $args = [])
 * @method \GuzzleHttp\Promise\Promise exportServerEngineAttributeAsync(array $args = [])
 * @method \Aws\Result listTagsForResource(array $args = [])
 * @method \GuzzleHttp\Promise\Promise listTagsForResourceAsync(array $args = [])
 * @method \Aws\Result restoreServer(array $args = [])
 * @method \GuzzleHttp\Promise\Promise restoreServerAsync(array $args = [])
 * @method \Aws\Result startMaintenance(array $args = [])
 * @method \GuzzleHttp\Promise\Promise startMaintenanceAsync(array $args = [])
 * @method \Aws\Result tagResource(array $args = [])
 * @method \GuzzleHttp\Promise\Promise tagResourceAsync(array $args = [])
 * @method \Aws\Result untagResource(array $args = [])
 * @method \GuzzleHttp\Promise\Promise untagResourceAsync(array $args = [])
 * @method \Aws\Result updateServer(array $args = [])
 * @method \GuzzleHttp\Promise\Promise updateServerAsync(array $args = [])
 * @method \Aws\Result updateServerEngineAttributes(array $args = [])
 * @method \GuzzleHttp\Promise\Promise updateServerEngineAttributesAsync(array $args = [])
 */
class OpsWorksCMClient extends AwsClient {}
