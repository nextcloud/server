<?php
namespace Aws\FSx;

use Aws\AwsClient;

/**
 * This client is used to interact with the **Amazon FSx** service.
 * @method \Aws\Result associateFileSystemAliases(array $args = [])
 * @method \GuzzleHttp\Promise\Promise associateFileSystemAliasesAsync(array $args = [])
 * @method \Aws\Result cancelDataRepositoryTask(array $args = [])
 * @method \GuzzleHttp\Promise\Promise cancelDataRepositoryTaskAsync(array $args = [])
 * @method \Aws\Result copyBackup(array $args = [])
 * @method \GuzzleHttp\Promise\Promise copyBackupAsync(array $args = [])
 * @method \Aws\Result createBackup(array $args = [])
 * @method \GuzzleHttp\Promise\Promise createBackupAsync(array $args = [])
 * @method \Aws\Result createDataRepositoryTask(array $args = [])
 * @method \GuzzleHttp\Promise\Promise createDataRepositoryTaskAsync(array $args = [])
 * @method \Aws\Result createFileSystem(array $args = [])
 * @method \GuzzleHttp\Promise\Promise createFileSystemAsync(array $args = [])
 * @method \Aws\Result createFileSystemFromBackup(array $args = [])
 * @method \GuzzleHttp\Promise\Promise createFileSystemFromBackupAsync(array $args = [])
 * @method \Aws\Result deleteBackup(array $args = [])
 * @method \GuzzleHttp\Promise\Promise deleteBackupAsync(array $args = [])
 * @method \Aws\Result deleteFileSystem(array $args = [])
 * @method \GuzzleHttp\Promise\Promise deleteFileSystemAsync(array $args = [])
 * @method \Aws\Result describeBackups(array $args = [])
 * @method \GuzzleHttp\Promise\Promise describeBackupsAsync(array $args = [])
 * @method \Aws\Result describeDataRepositoryTasks(array $args = [])
 * @method \GuzzleHttp\Promise\Promise describeDataRepositoryTasksAsync(array $args = [])
 * @method \Aws\Result describeFileSystemAliases(array $args = [])
 * @method \GuzzleHttp\Promise\Promise describeFileSystemAliasesAsync(array $args = [])
 * @method \Aws\Result describeFileSystems(array $args = [])
 * @method \GuzzleHttp\Promise\Promise describeFileSystemsAsync(array $args = [])
 * @method \Aws\Result disassociateFileSystemAliases(array $args = [])
 * @method \GuzzleHttp\Promise\Promise disassociateFileSystemAliasesAsync(array $args = [])
 * @method \Aws\Result listTagsForResource(array $args = [])
 * @method \GuzzleHttp\Promise\Promise listTagsForResourceAsync(array $args = [])
 * @method \Aws\Result tagResource(array $args = [])
 * @method \GuzzleHttp\Promise\Promise tagResourceAsync(array $args = [])
 * @method \Aws\Result untagResource(array $args = [])
 * @method \GuzzleHttp\Promise\Promise untagResourceAsync(array $args = [])
 * @method \Aws\Result updateFileSystem(array $args = [])
 * @method \GuzzleHttp\Promise\Promise updateFileSystemAsync(array $args = [])
 */
class FSxClient extends AwsClient {}
