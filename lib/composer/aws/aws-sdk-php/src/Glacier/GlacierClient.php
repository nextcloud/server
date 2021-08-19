<?php
namespace Aws\Glacier;

use Aws\Api\ApiProvider;
use Aws\Api\DocModel;
use Aws\Api\Service;
use Aws\AwsClient;
use Aws\CommandInterface;
use Aws\Exception\CouldNotCreateChecksumException;
use Aws\HashingStream;
use Aws\Middleware;
use Aws\PhpHash;
use Psr\Http\Message\RequestInterface;

/**
 * This client is used to interact with the **Amazon Glacier** service.
 *
 * @method \Aws\Result abortMultipartUpload(array $args = [])
 * @method \GuzzleHttp\Promise\Promise abortMultipartUploadAsync(array $args = [])
 * @method \Aws\Result abortVaultLock(array $args = [])
 * @method \GuzzleHttp\Promise\Promise abortVaultLockAsync(array $args = [])
 * @method \Aws\Result addTagsToVault(array $args = [])
 * @method \GuzzleHttp\Promise\Promise addTagsToVaultAsync(array $args = [])
 * @method \Aws\Result completeMultipartUpload(array $args = [])
 * @method \GuzzleHttp\Promise\Promise completeMultipartUploadAsync(array $args = [])
 * @method \Aws\Result completeVaultLock(array $args = [])
 * @method \GuzzleHttp\Promise\Promise completeVaultLockAsync(array $args = [])
 * @method \Aws\Result createVault(array $args = [])
 * @method \GuzzleHttp\Promise\Promise createVaultAsync(array $args = [])
 * @method \Aws\Result deleteArchive(array $args = [])
 * @method \GuzzleHttp\Promise\Promise deleteArchiveAsync(array $args = [])
 * @method \Aws\Result deleteVault(array $args = [])
 * @method \GuzzleHttp\Promise\Promise deleteVaultAsync(array $args = [])
 * @method \Aws\Result deleteVaultAccessPolicy(array $args = [])
 * @method \GuzzleHttp\Promise\Promise deleteVaultAccessPolicyAsync(array $args = [])
 * @method \Aws\Result deleteVaultNotifications(array $args = [])
 * @method \GuzzleHttp\Promise\Promise deleteVaultNotificationsAsync(array $args = [])
 * @method \Aws\Result describeJob(array $args = [])
 * @method \GuzzleHttp\Promise\Promise describeJobAsync(array $args = [])
 * @method \Aws\Result describeVault(array $args = [])
 * @method \GuzzleHttp\Promise\Promise describeVaultAsync(array $args = [])
 * @method \Aws\Result getDataRetrievalPolicy(array $args = [])
 * @method \GuzzleHttp\Promise\Promise getDataRetrievalPolicyAsync(array $args = [])
 * @method \Aws\Result getJobOutput(array $args = [])
 * @method \GuzzleHttp\Promise\Promise getJobOutputAsync(array $args = [])
 * @method \Aws\Result getVaultAccessPolicy(array $args = [])
 * @method \GuzzleHttp\Promise\Promise getVaultAccessPolicyAsync(array $args = [])
 * @method \Aws\Result getVaultLock(array $args = [])
 * @method \GuzzleHttp\Promise\Promise getVaultLockAsync(array $args = [])
 * @method \Aws\Result getVaultNotifications(array $args = [])
 * @method \GuzzleHttp\Promise\Promise getVaultNotificationsAsync(array $args = [])
 * @method \Aws\Result initiateJob(array $args = [])
 * @method \GuzzleHttp\Promise\Promise initiateJobAsync(array $args = [])
 * @method \Aws\Result initiateMultipartUpload(array $args = [])
 * @method \GuzzleHttp\Promise\Promise initiateMultipartUploadAsync(array $args = [])
 * @method \Aws\Result initiateVaultLock(array $args = [])
 * @method \GuzzleHttp\Promise\Promise initiateVaultLockAsync(array $args = [])
 * @method \Aws\Result listJobs(array $args = [])
 * @method \GuzzleHttp\Promise\Promise listJobsAsync(array $args = [])
 * @method \Aws\Result listMultipartUploads(array $args = [])
 * @method \GuzzleHttp\Promise\Promise listMultipartUploadsAsync(array $args = [])
 * @method \Aws\Result listParts(array $args = [])
 * @method \GuzzleHttp\Promise\Promise listPartsAsync(array $args = [])
 * @method \Aws\Result listProvisionedCapacity(array $args = [])
 * @method \GuzzleHttp\Promise\Promise listProvisionedCapacityAsync(array $args = [])
 * @method \Aws\Result listTagsForVault(array $args = [])
 * @method \GuzzleHttp\Promise\Promise listTagsForVaultAsync(array $args = [])
 * @method \Aws\Result listVaults(array $args = [])
 * @method \GuzzleHttp\Promise\Promise listVaultsAsync(array $args = [])
 * @method \Aws\Result purchaseProvisionedCapacity(array $args = [])
 * @method \GuzzleHttp\Promise\Promise purchaseProvisionedCapacityAsync(array $args = [])
 * @method \Aws\Result removeTagsFromVault(array $args = [])
 * @method \GuzzleHttp\Promise\Promise removeTagsFromVaultAsync(array $args = [])
 * @method \Aws\Result setDataRetrievalPolicy(array $args = [])
 * @method \GuzzleHttp\Promise\Promise setDataRetrievalPolicyAsync(array $args = [])
 * @method \Aws\Result setVaultAccessPolicy(array $args = [])
 * @method \GuzzleHttp\Promise\Promise setVaultAccessPolicyAsync(array $args = [])
 * @method \Aws\Result setVaultNotifications(array $args = [])
 * @method \GuzzleHttp\Promise\Promise setVaultNotificationsAsync(array $args = [])
 * @method \Aws\Result uploadArchive(array $args = [])
 * @method \GuzzleHttp\Promise\Promise uploadArchiveAsync(array $args = [])
 * @method \Aws\Result uploadMultipartPart(array $args = [])
 * @method \GuzzleHttp\Promise\Promise uploadMultipartPartAsync(array $args = [])
 */
class GlacierClient extends AwsClient
{
    public function __construct(array $args)
    {
        parent::__construct($args);

        // Setup middleware.
        $stack = $this->getHandlerList();
        $stack->appendBuild($this->getApiVersionMiddleware(), 'glacier.api_version');
        $stack->appendBuild($this->getChecksumsMiddleware(), 'glacier.checksum');
        $stack->appendBuild(
            Middleware::contentType(['UploadArchive', 'UploadPart']),
            'glacier.content_type'
        );
        $stack->appendInit(
            Middleware::sourceFile($this->getApi(), 'body', 'sourceFile'),
            'glacier.source_file'
        );
    }

    /**
     * {@inheritdoc}
     *
     * Sets the default accountId to "-" for all operations.
     */
    public function getCommand($name, array $args = [])
    {
        return parent::getCommand($name, $args + ['accountId' => '-']);
    }

    /**
     * Creates a middleware that updates a command with the content and tree
     * hash headers for upload operations.
     *
     * @return callable
     * @throws CouldNotCreateChecksumException if the body is not seekable.
     */
    private function getChecksumsMiddleware()
    {
        return function (callable $handler) {
            return function (
                CommandInterface $command,
                RequestInterface $request = null
            ) use ($handler) {
                // Accept "ContentSHA256" with a lowercase "c" to match other Glacier params.
                if (!$command['ContentSHA256'] && $command['contentSHA256']) {
                    $command['ContentSHA256'] = $command['contentSHA256'];
                    unset($command['contentSHA256']);
                }

                // If uploading, then make sure checksums are added.
                $name = $command->getName();
                if (($name === 'UploadArchive' || $name === 'UploadMultipartPart')
                    && (!$command['checksum'] || !$command['ContentSHA256'])
                ) {
                    $body = $request->getBody();
                    if (!$body->isSeekable()) {
                        throw new CouldNotCreateChecksumException('sha256');
                    }

                    // Add a tree hash if not provided.
                    if (!$command['checksum']) {
                        $body = new HashingStream(
                            $body, new TreeHash(),
                            function ($result) use (&$request) {
                                $request = $request->withHeader(
                                    'x-amz-sha256-tree-hash',
                                    bin2hex($result)
                                );
                            }
                        );
                    }

                    // Add a linear content hash if not provided.
                    if (!$command['ContentSHA256']) {
                        $body = new HashingStream(
                            $body, new PhpHash('sha256'),
                            function ($result) use ($command) {
                                $command['ContentSHA256'] = bin2hex($result);
                            }
                        );
                    }

                    // Read the stream in order to calculate the hashes.
                    while (!$body->eof()) {
                        $body->read(1048576);
                    }
                    $body->seek(0);
                }

                // Set the content hash header if a value is in the command.
                if ($command['ContentSHA256']) {
                    $request = $request->withHeader(
                        'x-amz-content-sha256',
                        $command['ContentSHA256']
                    );
                }

                return $handler($command, $request);
            };
        };
    }

    /**
     * Creates a middleware that adds the API version header for all requests.
     *
     * @return callable
     */
    private function getApiVersionMiddleware()
    {
        return function (callable $handler) {
            return function (
                CommandInterface $command,
                RequestInterface $request = null
            ) use ($handler) {
                return $handler($command, $request->withHeader(
                    'x-amz-glacier-version',
                    $this->getApi()->getMetadata('apiVersion')
                ));
            };
        };
    }

    /**
     * @internal
     * @codeCoverageIgnore
     */
    public static function applyDocFilters(array $api, array $docs)
    {
        // Add the SourceFile parameter.
        $docs['shapes']['SourceFile']['base'] = 'The path to a file on disk to use instead of the body parameter.';
        $api['shapes']['SourceFile'] = ['type' => 'string'];
        $api['shapes']['UploadArchiveInput']['members']['sourceFile'] = ['shape' => 'SourceFile'];
        $api['shapes']['UploadMultipartPartInput']['members']['sourceFile'] = ['shape' => 'SourceFile'];

        // Add the ContentSHA256 parameter.
        $docs['shapes']['ContentSHA256']['base'] = 'A SHA256 hash of the content of the request body';
        $api['shapes']['ContentSHA256'] = ['type' => 'string'];
        $api['shapes']['UploadArchiveInput']['members']['contentSHA256'] = ['shape' => 'ContentSHA256'];
        $api['shapes']['UploadMultipartPartInput']['members']['contentSHA256'] = ['shape' => 'ContentSHA256'];

        // Add information about "checksum" and "ContentSHA256" being optional.
        $optional = '<div class="alert alert-info">The SDK will compute this value '
            . 'for you on your behalf if it is not supplied.</div>';
        $docs['shapes']['checksum']['append'] = $optional;
        $docs['shapes']['ContentSHA256']['append'] = $optional;

        // Make "accountId" optional for all operations.
        foreach ($api['operations'] as $operation) {
            $inputShape =& $api['shapes'][$operation['input']['shape']];
            $accountIdIndex = array_search('accountId', $inputShape['required']);
            unset($inputShape['required'][$accountIdIndex]);
        }
        // Add information about the default value for "accountId".
        $optional = '<div class="alert alert-info">The SDK will set this value to "-" by default.</div>';
        foreach ($docs['shapes']['string']['refs'] as $name => &$ref) {
            if (strpos($name, 'accountId')) {
                $ref .= $optional;
            }
        }

        return [
            new Service($api, ApiProvider::defaultProvider()),
            new DocModel($docs)
        ];
    }
}
