<?php
namespace Aws\LexModelsV2;

use Aws\AwsClient;
use Aws\CommandInterface;
use Psr\Http\Message\RequestInterface;

/**
 * This client is used to interact with the **Amazon Lex Model Building V2** service.
 * @method \Aws\Result buildBotLocale(array $args = [])
 * @method \GuzzleHttp\Promise\Promise buildBotLocaleAsync(array $args = [])
 * @method \Aws\Result createBot(array $args = [])
 * @method \GuzzleHttp\Promise\Promise createBotAsync(array $args = [])
 * @method \Aws\Result createBotAlias(array $args = [])
 * @method \GuzzleHttp\Promise\Promise createBotAliasAsync(array $args = [])
 * @method \Aws\Result createBotLocale(array $args = [])
 * @method \GuzzleHttp\Promise\Promise createBotLocaleAsync(array $args = [])
 * @method \Aws\Result createBotVersion(array $args = [])
 * @method \GuzzleHttp\Promise\Promise createBotVersionAsync(array $args = [])
 * @method \Aws\Result createExport(array $args = [])
 * @method \GuzzleHttp\Promise\Promise createExportAsync(array $args = [])
 * @method \Aws\Result createIntent(array $args = [])
 * @method \GuzzleHttp\Promise\Promise createIntentAsync(array $args = [])
 * @method \Aws\Result createResourcePolicy(array $args = [])
 * @method \GuzzleHttp\Promise\Promise createResourcePolicyAsync(array $args = [])
 * @method \Aws\Result createResourcePolicyStatement(array $args = [])
 * @method \GuzzleHttp\Promise\Promise createResourcePolicyStatementAsync(array $args = [])
 * @method \Aws\Result createSlot(array $args = [])
 * @method \GuzzleHttp\Promise\Promise createSlotAsync(array $args = [])
 * @method \Aws\Result createSlotType(array $args = [])
 * @method \GuzzleHttp\Promise\Promise createSlotTypeAsync(array $args = [])
 * @method \Aws\Result createUploadUrl(array $args = [])
 * @method \GuzzleHttp\Promise\Promise createUploadUrlAsync(array $args = [])
 * @method \Aws\Result deleteBot(array $args = [])
 * @method \GuzzleHttp\Promise\Promise deleteBotAsync(array $args = [])
 * @method \Aws\Result deleteBotAlias(array $args = [])
 * @method \GuzzleHttp\Promise\Promise deleteBotAliasAsync(array $args = [])
 * @method \Aws\Result deleteBotLocale(array $args = [])
 * @method \GuzzleHttp\Promise\Promise deleteBotLocaleAsync(array $args = [])
 * @method \Aws\Result deleteBotVersion(array $args = [])
 * @method \GuzzleHttp\Promise\Promise deleteBotVersionAsync(array $args = [])
 * @method \Aws\Result deleteExport(array $args = [])
 * @method \GuzzleHttp\Promise\Promise deleteExportAsync(array $args = [])
 * @method \Aws\Result deleteImport(array $args = [])
 * @method \GuzzleHttp\Promise\Promise deleteImportAsync(array $args = [])
 * @method \Aws\Result deleteIntent(array $args = [])
 * @method \GuzzleHttp\Promise\Promise deleteIntentAsync(array $args = [])
 * @method \Aws\Result deleteResourcePolicy(array $args = [])
 * @method \GuzzleHttp\Promise\Promise deleteResourcePolicyAsync(array $args = [])
 * @method \Aws\Result deleteResourcePolicyStatement(array $args = [])
 * @method \GuzzleHttp\Promise\Promise deleteResourcePolicyStatementAsync(array $args = [])
 * @method \Aws\Result deleteSlot(array $args = [])
 * @method \GuzzleHttp\Promise\Promise deleteSlotAsync(array $args = [])
 * @method \Aws\Result deleteSlotType(array $args = [])
 * @method \GuzzleHttp\Promise\Promise deleteSlotTypeAsync(array $args = [])
 * @method \Aws\Result describeBot(array $args = [])
 * @method \GuzzleHttp\Promise\Promise describeBotAsync(array $args = [])
 * @method \Aws\Result describeBotAlias(array $args = [])
 * @method \GuzzleHttp\Promise\Promise describeBotAliasAsync(array $args = [])
 * @method \Aws\Result describeBotLocale(array $args = [])
 * @method \GuzzleHttp\Promise\Promise describeBotLocaleAsync(array $args = [])
 * @method \Aws\Result describeBotVersion(array $args = [])
 * @method \GuzzleHttp\Promise\Promise describeBotVersionAsync(array $args = [])
 * @method \Aws\Result describeExport(array $args = [])
 * @method \GuzzleHttp\Promise\Promise describeExportAsync(array $args = [])
 * @method \Aws\Result describeImport(array $args = [])
 * @method \GuzzleHttp\Promise\Promise describeImportAsync(array $args = [])
 * @method \Aws\Result describeIntent(array $args = [])
 * @method \GuzzleHttp\Promise\Promise describeIntentAsync(array $args = [])
 * @method \Aws\Result describeResourcePolicy(array $args = [])
 * @method \GuzzleHttp\Promise\Promise describeResourcePolicyAsync(array $args = [])
 * @method \Aws\Result describeSlot(array $args = [])
 * @method \GuzzleHttp\Promise\Promise describeSlotAsync(array $args = [])
 * @method \Aws\Result describeSlotType(array $args = [])
 * @method \GuzzleHttp\Promise\Promise describeSlotTypeAsync(array $args = [])
 * @method \Aws\Result listBotAliases(array $args = [])
 * @method \GuzzleHttp\Promise\Promise listBotAliasesAsync(array $args = [])
 * @method \Aws\Result listBotLocales(array $args = [])
 * @method \GuzzleHttp\Promise\Promise listBotLocalesAsync(array $args = [])
 * @method \Aws\Result listBotVersions(array $args = [])
 * @method \GuzzleHttp\Promise\Promise listBotVersionsAsync(array $args = [])
 * @method \Aws\Result listBots(array $args = [])
 * @method \GuzzleHttp\Promise\Promise listBotsAsync(array $args = [])
 * @method \Aws\Result listBuiltInIntents(array $args = [])
 * @method \GuzzleHttp\Promise\Promise listBuiltInIntentsAsync(array $args = [])
 * @method \Aws\Result listBuiltInSlotTypes(array $args = [])
 * @method \GuzzleHttp\Promise\Promise listBuiltInSlotTypesAsync(array $args = [])
 * @method \Aws\Result listExports(array $args = [])
 * @method \GuzzleHttp\Promise\Promise listExportsAsync(array $args = [])
 * @method \Aws\Result listImports(array $args = [])
 * @method \GuzzleHttp\Promise\Promise listImportsAsync(array $args = [])
 * @method \Aws\Result listIntents(array $args = [])
 * @method \GuzzleHttp\Promise\Promise listIntentsAsync(array $args = [])
 * @method \Aws\Result listSlotTypes(array $args = [])
 * @method \GuzzleHttp\Promise\Promise listSlotTypesAsync(array $args = [])
 * @method \Aws\Result listSlots(array $args = [])
 * @method \GuzzleHttp\Promise\Promise listSlotsAsync(array $args = [])
 * @method \Aws\Result listTagsForResource(array $args = [])
 * @method \GuzzleHttp\Promise\Promise listTagsForResourceAsync(array $args = [])
 * @method \Aws\Result startImport(array $args = [])
 * @method \GuzzleHttp\Promise\Promise startImportAsync(array $args = [])
 * @method \Aws\Result tagResource(array $args = [])
 * @method \GuzzleHttp\Promise\Promise tagResourceAsync(array $args = [])
 * @method \Aws\Result untagResource(array $args = [])
 * @method \GuzzleHttp\Promise\Promise untagResourceAsync(array $args = [])
 * @method \Aws\Result updateBot(array $args = [])
 * @method \GuzzleHttp\Promise\Promise updateBotAsync(array $args = [])
 * @method \Aws\Result updateBotAlias(array $args = [])
 * @method \GuzzleHttp\Promise\Promise updateBotAliasAsync(array $args = [])
 * @method \Aws\Result updateBotLocale(array $args = [])
 * @method \GuzzleHttp\Promise\Promise updateBotLocaleAsync(array $args = [])
 * @method \Aws\Result updateExport(array $args = [])
 * @method \GuzzleHttp\Promise\Promise updateExportAsync(array $args = [])
 * @method \Aws\Result updateIntent(array $args = [])
 * @method \GuzzleHttp\Promise\Promise updateIntentAsync(array $args = [])
 * @method \Aws\Result updateResourcePolicy(array $args = [])
 * @method \GuzzleHttp\Promise\Promise updateResourcePolicyAsync(array $args = [])
 * @method \Aws\Result updateSlot(array $args = [])
 * @method \GuzzleHttp\Promise\Promise updateSlotAsync(array $args = [])
 * @method \Aws\Result updateSlotType(array $args = [])
 * @method \GuzzleHttp\Promise\Promise updateSlotTypeAsync(array $args = [])
 */
class LexModelsV2Client extends AwsClient {
    public function __construct(array $args)
    {
        parent::__construct($args);

        // Setup middleware.
        $stack = $this->getHandlerList();
        $stack->appendBuild($this->updateContentType(), 'models.lex.v2.updateContentType');
    }

    /**
     * Creates a middleware that updates the Content-Type header when it is present;
     * this is necessary because the service protocol is rest-json which by default
     * sets the content-type to 'application/json', but interacting with the service
     * requires it to be set to x-amz-json-1.1
     *
     * @return callable
     */
    private function updateContentType()
    {
        return function (callable $handler) {
            return function (
                CommandInterface $command,
                RequestInterface $request = null
            ) use ($handler) {
                $contentType = $request->getHeader('Content-Type');
                if (!empty($contentType) && $contentType[0] == 'application/json') {
                    return $handler($command, $request->withHeader(
                        'Content-Type',
                        'application/x-amz-json-1.1'
                    ));
                }
                return $handler($command, $request);
            };
        };
    }
}
