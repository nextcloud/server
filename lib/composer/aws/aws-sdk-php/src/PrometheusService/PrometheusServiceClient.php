<?php
namespace Aws\PrometheusService;

use Aws\AwsClient;

/**
 * This client is used to interact with the **Amazon Prometheus Service** service.
 * @method \Aws\Result createWorkspace(array $args = [])
 * @method \GuzzleHttp\Promise\Promise createWorkspaceAsync(array $args = [])
 * @method \Aws\Result deleteWorkspace(array $args = [])
 * @method \GuzzleHttp\Promise\Promise deleteWorkspaceAsync(array $args = [])
 * @method \Aws\Result describeWorkspace(array $args = [])
 * @method \GuzzleHttp\Promise\Promise describeWorkspaceAsync(array $args = [])
 * @method \Aws\Result listWorkspaces(array $args = [])
 * @method \GuzzleHttp\Promise\Promise listWorkspacesAsync(array $args = [])
 * @method \Aws\Result updateWorkspaceAlias(array $args = [])
 * @method \GuzzleHttp\Promise\Promise updateWorkspaceAliasAsync(array $args = [])
 */
class PrometheusServiceClient extends AwsClient {}
