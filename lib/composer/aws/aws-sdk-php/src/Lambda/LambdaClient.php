<?php
namespace Aws\Lambda;

use Aws\AwsClient;
use Aws\CommandInterface;
use Aws\Middleware;

/**
 * This client is used to interact with AWS Lambda
 *
 * @method \Aws\Result addLayerVersionPermission(array $args = [])
 * @method \GuzzleHttp\Promise\Promise addLayerVersionPermissionAsync(array $args = [])
 * @method \Aws\Result addPermission(array $args = [])
 * @method \GuzzleHttp\Promise\Promise addPermissionAsync(array $args = [])
 * @method \Aws\Result createAlias(array $args = [])
 * @method \GuzzleHttp\Promise\Promise createAliasAsync(array $args = [])
 * @method \Aws\Result createCodeSigningConfig(array $args = [])
 * @method \GuzzleHttp\Promise\Promise createCodeSigningConfigAsync(array $args = [])
 * @method \Aws\Result createEventSourceMapping(array $args = [])
 * @method \GuzzleHttp\Promise\Promise createEventSourceMappingAsync(array $args = [])
 * @method \Aws\Result createFunction(array $args = [])
 * @method \GuzzleHttp\Promise\Promise createFunctionAsync(array $args = [])
 * @method \Aws\Result deleteAlias(array $args = [])
 * @method \GuzzleHttp\Promise\Promise deleteAliasAsync(array $args = [])
 * @method \Aws\Result deleteCodeSigningConfig(array $args = [])
 * @method \GuzzleHttp\Promise\Promise deleteCodeSigningConfigAsync(array $args = [])
 * @method \Aws\Result deleteEventSourceMapping(array $args = [])
 * @method \GuzzleHttp\Promise\Promise deleteEventSourceMappingAsync(array $args = [])
 * @method \Aws\Result deleteFunction(array $args = [])
 * @method \GuzzleHttp\Promise\Promise deleteFunctionAsync(array $args = [])
 * @method \Aws\Result deleteFunctionCodeSigningConfig(array $args = [])
 * @method \GuzzleHttp\Promise\Promise deleteFunctionCodeSigningConfigAsync(array $args = [])
 * @method \Aws\Result deleteFunctionConcurrency(array $args = [])
 * @method \GuzzleHttp\Promise\Promise deleteFunctionConcurrencyAsync(array $args = [])
 * @method \Aws\Result deleteFunctionEventInvokeConfig(array $args = [])
 * @method \GuzzleHttp\Promise\Promise deleteFunctionEventInvokeConfigAsync(array $args = [])
 * @method \Aws\Result deleteLayerVersion(array $args = [])
 * @method \GuzzleHttp\Promise\Promise deleteLayerVersionAsync(array $args = [])
 * @method \Aws\Result deleteProvisionedConcurrencyConfig(array $args = [])
 * @method \GuzzleHttp\Promise\Promise deleteProvisionedConcurrencyConfigAsync(array $args = [])
 * @method \Aws\Result getAccountSettings(array $args = [])
 * @method \GuzzleHttp\Promise\Promise getAccountSettingsAsync(array $args = [])
 * @method \Aws\Result getAlias(array $args = [])
 * @method \GuzzleHttp\Promise\Promise getAliasAsync(array $args = [])
 * @method \Aws\Result getCodeSigningConfig(array $args = [])
 * @method \GuzzleHttp\Promise\Promise getCodeSigningConfigAsync(array $args = [])
 * @method \Aws\Result getEventSourceMapping(array $args = [])
 * @method \GuzzleHttp\Promise\Promise getEventSourceMappingAsync(array $args = [])
 * @method \Aws\Result getFunction(array $args = [])
 * @method \GuzzleHttp\Promise\Promise getFunctionAsync(array $args = [])
 * @method \Aws\Result getFunctionCodeSigningConfig(array $args = [])
 * @method \GuzzleHttp\Promise\Promise getFunctionCodeSigningConfigAsync(array $args = [])
 * @method \Aws\Result getFunctionConcurrency(array $args = [])
 * @method \GuzzleHttp\Promise\Promise getFunctionConcurrencyAsync(array $args = [])
 * @method \Aws\Result getFunctionConfiguration(array $args = [])
 * @method \GuzzleHttp\Promise\Promise getFunctionConfigurationAsync(array $args = [])
 * @method \Aws\Result getFunctionEventInvokeConfig(array $args = [])
 * @method \GuzzleHttp\Promise\Promise getFunctionEventInvokeConfigAsync(array $args = [])
 * @method \Aws\Result getLayerVersion(array $args = [])
 * @method \GuzzleHttp\Promise\Promise getLayerVersionAsync(array $args = [])
 * @method \Aws\Result getLayerVersionByArn(array $args = [])
 * @method \GuzzleHttp\Promise\Promise getLayerVersionByArnAsync(array $args = [])
 * @method \Aws\Result getLayerVersionPolicy(array $args = [])
 * @method \GuzzleHttp\Promise\Promise getLayerVersionPolicyAsync(array $args = [])
 * @method \Aws\Result getPolicy(array $args = [])
 * @method \GuzzleHttp\Promise\Promise getPolicyAsync(array $args = [])
 * @method \Aws\Result getProvisionedConcurrencyConfig(array $args = [])
 * @method \GuzzleHttp\Promise\Promise getProvisionedConcurrencyConfigAsync(array $args = [])
 * @method \Aws\Result invoke(array $args = [])
 * @method \GuzzleHttp\Promise\Promise invokeAsync(array $args = [])
 * @method \Aws\Result invokeAsync(array $args = [])
 * @method \GuzzleHttp\Promise\Promise invokeAsyncAsync(array $args = [])
 * @method \Aws\Result listAliases(array $args = [])
 * @method \GuzzleHttp\Promise\Promise listAliasesAsync(array $args = [])
 * @method \Aws\Result listCodeSigningConfigs(array $args = [])
 * @method \GuzzleHttp\Promise\Promise listCodeSigningConfigsAsync(array $args = [])
 * @method \Aws\Result listEventSourceMappings(array $args = [])
 * @method \GuzzleHttp\Promise\Promise listEventSourceMappingsAsync(array $args = [])
 * @method \Aws\Result listFunctionEventInvokeConfigs(array $args = [])
 * @method \GuzzleHttp\Promise\Promise listFunctionEventInvokeConfigsAsync(array $args = [])
 * @method \Aws\Result listFunctions(array $args = [])
 * @method \GuzzleHttp\Promise\Promise listFunctionsAsync(array $args = [])
 * @method \Aws\Result listFunctionsByCodeSigningConfig(array $args = [])
 * @method \GuzzleHttp\Promise\Promise listFunctionsByCodeSigningConfigAsync(array $args = [])
 * @method \Aws\Result listLayerVersions(array $args = [])
 * @method \GuzzleHttp\Promise\Promise listLayerVersionsAsync(array $args = [])
 * @method \Aws\Result listLayers(array $args = [])
 * @method \GuzzleHttp\Promise\Promise listLayersAsync(array $args = [])
 * @method \Aws\Result listProvisionedConcurrencyConfigs(array $args = [])
 * @method \GuzzleHttp\Promise\Promise listProvisionedConcurrencyConfigsAsync(array $args = [])
 * @method \Aws\Result listTags(array $args = [])
 * @method \GuzzleHttp\Promise\Promise listTagsAsync(array $args = [])
 * @method \Aws\Result listVersionsByFunction(array $args = [])
 * @method \GuzzleHttp\Promise\Promise listVersionsByFunctionAsync(array $args = [])
 * @method \Aws\Result publishLayerVersion(array $args = [])
 * @method \GuzzleHttp\Promise\Promise publishLayerVersionAsync(array $args = [])
 * @method \Aws\Result publishVersion(array $args = [])
 * @method \GuzzleHttp\Promise\Promise publishVersionAsync(array $args = [])
 * @method \Aws\Result putFunctionCodeSigningConfig(array $args = [])
 * @method \GuzzleHttp\Promise\Promise putFunctionCodeSigningConfigAsync(array $args = [])
 * @method \Aws\Result putFunctionConcurrency(array $args = [])
 * @method \GuzzleHttp\Promise\Promise putFunctionConcurrencyAsync(array $args = [])
 * @method \Aws\Result putFunctionEventInvokeConfig(array $args = [])
 * @method \GuzzleHttp\Promise\Promise putFunctionEventInvokeConfigAsync(array $args = [])
 * @method \Aws\Result putProvisionedConcurrencyConfig(array $args = [])
 * @method \GuzzleHttp\Promise\Promise putProvisionedConcurrencyConfigAsync(array $args = [])
 * @method \Aws\Result removeLayerVersionPermission(array $args = [])
 * @method \GuzzleHttp\Promise\Promise removeLayerVersionPermissionAsync(array $args = [])
 * @method \Aws\Result removePermission(array $args = [])
 * @method \GuzzleHttp\Promise\Promise removePermissionAsync(array $args = [])
 * @method \Aws\Result tagResource(array $args = [])
 * @method \GuzzleHttp\Promise\Promise tagResourceAsync(array $args = [])
 * @method \Aws\Result untagResource(array $args = [])
 * @method \GuzzleHttp\Promise\Promise untagResourceAsync(array $args = [])
 * @method \Aws\Result updateAlias(array $args = [])
 * @method \GuzzleHttp\Promise\Promise updateAliasAsync(array $args = [])
 * @method \Aws\Result updateCodeSigningConfig(array $args = [])
 * @method \GuzzleHttp\Promise\Promise updateCodeSigningConfigAsync(array $args = [])
 * @method \Aws\Result updateEventSourceMapping(array $args = [])
 * @method \GuzzleHttp\Promise\Promise updateEventSourceMappingAsync(array $args = [])
 * @method \Aws\Result updateFunctionCode(array $args = [])
 * @method \GuzzleHttp\Promise\Promise updateFunctionCodeAsync(array $args = [])
 * @method \Aws\Result updateFunctionConfiguration(array $args = [])
 * @method \GuzzleHttp\Promise\Promise updateFunctionConfigurationAsync(array $args = [])
 * @method \Aws\Result updateFunctionEventInvokeConfig(array $args = [])
 * @method \GuzzleHttp\Promise\Promise updateFunctionEventInvokeConfigAsync(array $args = [])
 */
class LambdaClient extends AwsClient
{
    /**
     * {@inheritdoc}
     */
    public function __construct(array $args)
    {
        parent::__construct($args);
        $list = $this->getHandlerList();
        if (extension_loaded('curl')) {
            $list->appendInit($this->getDefaultCurlOptionsMiddleware());
        }
    }

    /**
     * Provides a middleware that sets default Curl options for the command
     *
     * @return callable
     */
    public function getDefaultCurlOptionsMiddleware()
    {
        return Middleware::mapCommand(function (CommandInterface $cmd) {
            $defaultCurlOptions = [
                CURLOPT_TCP_KEEPALIVE => 1,
            ];
            if (!isset($cmd['@http']['curl'])) {
                $cmd['@http']['curl'] = $defaultCurlOptions;
            } else {
                $cmd['@http']['curl'] += $defaultCurlOptions;
            }
            return $cmd;
        });
    }
}
