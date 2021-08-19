<?php

/**
 * LICENSE: The MIT License (the "License")
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 * https://github.com/azure/azure-storage-php/LICENSE
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 *
 * PHP version 5
 *
 * @category  Microsoft
 * @package   MicrosoftAzure\Storage\Blob
 * @author    Azure Storage PHP SDK <dmsh@microsoft.com>
 * @copyright 2016 Microsoft Corporation
 * @license   https://github.com/azure/azure-storage-php/LICENSE
 * @link      https://github.com/azure/azure-storage-php
 */

namespace MicrosoftAzure\Storage\Blob;

use GuzzleHttp\Psr7;
use MicrosoftAzure\Storage\Blob\Internal\IBlob;
use MicrosoftAzure\Storage\Blob\Internal\BlobResources as Resources;
use MicrosoftAzure\Storage\Blob\Models\AppendBlockOptions;
use MicrosoftAzure\Storage\Blob\Models\AppendBlockResult;
use MicrosoftAzure\Storage\Blob\Models\BlobServiceOptions;
use MicrosoftAzure\Storage\Blob\Models\BlobType;
use MicrosoftAzure\Storage\Blob\Models\Block;
use MicrosoftAzure\Storage\Blob\Models\BlockList;
use MicrosoftAzure\Storage\Blob\Models\BreakLeaseResult;
use MicrosoftAzure\Storage\Blob\Models\CommitBlobBlocksOptions;
use MicrosoftAzure\Storage\Blob\Models\CopyBlobFromURLOptions;
use MicrosoftAzure\Storage\Blob\Models\CopyBlobOptions;
use MicrosoftAzure\Storage\Blob\Models\CopyBlobResult;
use MicrosoftAzure\Storage\Blob\Models\CreateBlobBlockOptions;
use MicrosoftAzure\Storage\Blob\Models\CreateBlobOptions;
use MicrosoftAzure\Storage\Blob\Models\CreateBlobPagesOptions;
use MicrosoftAzure\Storage\Blob\Models\CreateBlobPagesResult;
use MicrosoftAzure\Storage\Blob\Models\CreateBlobSnapshotOptions;
use MicrosoftAzure\Storage\Blob\Models\CreateBlobSnapshotResult;
use MicrosoftAzure\Storage\Blob\Models\CreateContainerOptions;
use MicrosoftAzure\Storage\Blob\Models\CreatePageBlobOptions;
use MicrosoftAzure\Storage\Blob\Models\UndeleteBlobOptions;
use MicrosoftAzure\Storage\Blob\Models\DeleteBlobOptions;
use MicrosoftAzure\Storage\Blob\Models\GetBlobMetadataOptions;
use MicrosoftAzure\Storage\Blob\Models\GetBlobMetadataResult;
use MicrosoftAzure\Storage\Blob\Models\GetBlobOptions;
use MicrosoftAzure\Storage\Blob\Models\GetBlobPropertiesOptions;
use MicrosoftAzure\Storage\Blob\Models\GetBlobPropertiesResult;
use MicrosoftAzure\Storage\Blob\Models\GetBlobResult;
use MicrosoftAzure\Storage\Blob\Models\GetContainerACLResult;
use MicrosoftAzure\Storage\Blob\Models\GetContainerPropertiesResult;
use MicrosoftAzure\Storage\Blob\Models\LeaseMode;
use MicrosoftAzure\Storage\Blob\Models\LeaseResult;
use MicrosoftAzure\Storage\Blob\Models\ListBlobBlocksOptions;
use MicrosoftAzure\Storage\Blob\Models\ListBlobBlocksResult;
use MicrosoftAzure\Storage\Blob\Models\ListBlobsOptions;
use MicrosoftAzure\Storage\Blob\Models\ListBlobsResult;
use MicrosoftAzure\Storage\Blob\Models\ListContainersOptions;
use MicrosoftAzure\Storage\Blob\Models\ListContainersResult;
use MicrosoftAzure\Storage\Blob\Models\ListPageBlobRangesDiffResult;
use MicrosoftAzure\Storage\Blob\Models\ListPageBlobRangesOptions;
use MicrosoftAzure\Storage\Blob\Models\ListPageBlobRangesResult;
use MicrosoftAzure\Storage\Blob\Models\PageWriteOption;
use MicrosoftAzure\Storage\Blob\Models\PutBlobResult;
use MicrosoftAzure\Storage\Blob\Models\PutBlockResult;
use MicrosoftAzure\Storage\Blob\Models\SetBlobMetadataResult;
use MicrosoftAzure\Storage\Blob\Models\SetBlobPropertiesOptions;
use MicrosoftAzure\Storage\Blob\Models\SetBlobPropertiesResult;
use MicrosoftAzure\Storage\Blob\Models\SetBlobTierOptions;
use MicrosoftAzure\Storage\Common\Internal\Authentication\SharedAccessSignatureAuthScheme;
use MicrosoftAzure\Storage\Common\Internal\Authentication\SharedKeyAuthScheme;
use MicrosoftAzure\Storage\Common\Internal\Authentication\TokenAuthScheme;
use MicrosoftAzure\Storage\Common\Internal\Http\HttpFormatter;
use MicrosoftAzure\Storage\Common\Internal\Middlewares\CommonRequestMiddleware;
use MicrosoftAzure\Storage\Common\Internal\Serialization\XmlSerializer;
use MicrosoftAzure\Storage\Common\Internal\ServiceRestProxy;
use MicrosoftAzure\Storage\Common\Internal\ServiceRestTrait;
use MicrosoftAzure\Storage\Common\Internal\StorageServiceSettings;
use MicrosoftAzure\Storage\Common\Internal\Utilities;
use MicrosoftAzure\Storage\Common\Internal\Validate;
use MicrosoftAzure\Storage\Common\LocationMode;
use MicrosoftAzure\Storage\Common\Models\Range;
use MicrosoftAzure\Storage\Common\SharedAccessSignatureHelper;
use Psr\Http\Message\StreamInterface;

/**
 * This class constructs HTTP requests and receive HTTP responses for blob
 * service layer.
 *
 * @category  Microsoft
 * @package   MicrosoftAzure\Storage\Blob
 * @author    Azure Storage PHP SDK <dmsh@microsoft.com>
 * @copyright 2016 Microsoft Corporation
 * @license   https://github.com/azure/azure-storage-php/LICENSE
 * @link      https://github.com/azure/azure-storage-php
 */
class BlobRestProxy extends ServiceRestProxy implements IBlob
{
    use ServiceRestTrait;

    private $singleBlobUploadThresholdInBytes = Resources::MB_IN_BYTES_32;
    private $blockSize = Resources::MB_IN_BYTES_4;

    /**
     * Builds a blob service object, it accepts the following
     * options:
     *
     * - http: (array) the underlying guzzle options. refer to
     *   http://docs.guzzlephp.org/en/latest/request-options.html for detailed available options
     * - middlewares: (mixed) the middleware should be either an instance of a sub-class that
     *   implements {@see MicrosoftAzure\Storage\Common\Middlewares\IMiddleware}, or a
     *   `callable` that follows the Guzzle middleware implementation convention
     *
     * Please refer to
     *   https://azure.microsoft.com/en-us/documentation/articles/storage-configure-connection-string
     * for how to construct a connection string with storage account name/key, or with a shared
     * access signature (SAS Token).
     *
     * @param string $connectionString The configuration connection string.
     * @param array  $options          Array of options to pass to the service
     * @return BlobRestProxy
     */
    public static function createBlobService(
        $connectionString,
        array $options = []
    ) {
        $settings = StorageServiceSettings::createFromConnectionString(
            $connectionString
        );

        $primaryUri = Utilities::tryAddUrlScheme(
            $settings->getBlobEndpointUri()
        );

        $secondaryUri = Utilities::tryAddUrlScheme(
            $settings->getBlobSecondaryEndpointUri()
        );

        $blobWrapper = new BlobRestProxy(
            $primaryUri,
            $secondaryUri,
            $settings->getName(),
            $options
        );

        // Getting authentication scheme
        if ($settings->hasSasToken()) {
            $authScheme = new SharedAccessSignatureAuthScheme(
                $settings->getSasToken()
            );
        } else {
            $authScheme = new SharedKeyAuthScheme(
                $settings->getName(),
                $settings->getKey()
            );
        }

        // Adding common request middleware
        $commonRequestMiddleware = new CommonRequestMiddleware(
            $authScheme,
            Resources::STORAGE_API_LATEST_VERSION,
            Resources::BLOB_SDK_VERSION
        );
        $blobWrapper->pushMiddleware($commonRequestMiddleware);

        return $blobWrapper;
    }

    /**
     * Builds a blob service object, it accepts the following
     * options:
     *
     * - http: (array) the underlying guzzle options. refer to
     *   http://docs.guzzlephp.org/en/latest/request-options.html for detailed available options
     * - middlewares: (mixed) the middleware should be either an instance of a sub-class that
     *   implements {@see MicrosoftAzure\Storage\Common\Middlewares\IMiddleware}, or a
     *   `callable` that follows the Guzzle middleware implementation convention
     *
     * Please refer to
     * https://docs.microsoft.com/en-us/azure/storage/common/storage-auth-aad
     * for authenticate access to Azure blobs and queues using Azure Active Directory.
     *
     * @param string $token            The bearer token passed as reference.
     * @param string $connectionString The configuration connection string.
     * @param array  $options          Array of options to pass to the service
     *
     * @return BlobRestProxy
     */
    public static function createBlobServiceWithTokenCredential(
        &$token,
        $connectionString,
        array $options = []
    ) {
        $settings = StorageServiceSettings::createFromConnectionStringForTokenCredential(
            $connectionString
        );

        $primaryUri = Utilities::tryAddUrlScheme(
            $settings->getBlobEndpointUri()
        );

        $secondaryUri = Utilities::tryAddUrlScheme(
            $settings->getBlobSecondaryEndpointUri()
        );

        $blobWrapper = new BlobRestProxy(
            $primaryUri,
            $secondaryUri,
            $settings->getName(),
            $options
        );

        // Getting authentication scheme
        $authScheme = new TokenAuthScheme(
            $token
        );

        // Adding common request middleware
        $commonRequestMiddleware = new CommonRequestMiddleware(
            $authScheme,
            Resources::STORAGE_API_LATEST_VERSION,
            Resources::BLOB_SDK_VERSION
        );
        $blobWrapper->pushMiddleware($commonRequestMiddleware);

        return $blobWrapper;
    }

    /**
     * Builds an anonymous access object with given primary service
     * endpoint. The service endpoint should contain a scheme and a
     * host, e.g.:
     *     http://mystorageaccount.blob.core.windows.net
     *
     * @param  string $primaryServiceEndpoint   Primary service endpoint.
     * @param  array  $options                  Optional request options.
     *
     * @return BlobRestProxy
     */
    public static function createContainerAnonymousAccess(
        $primaryServiceEndpoint,
        array $options = []
    ) {
        Validate::canCastAsString($primaryServiceEndpoint, '$primaryServiceEndpoint');

        $secondaryServiceEndpoint = Utilities::tryGetSecondaryEndpointFromPrimaryEndpoint(
            $primaryServiceEndpoint
        );

        $blobWrapper = new BlobRestProxy(
            $primaryServiceEndpoint,
            $secondaryServiceEndpoint,
            Utilities::tryParseAccountNameFromUrl($primaryServiceEndpoint),
            $options
        );

        $blobWrapper->pushMiddleware(new CommonRequestMiddleware(
            null,
            Resources::STORAGE_API_LATEST_VERSION,
            Resources::BLOB_SDK_VERSION
        ));

        return $blobWrapper;
    }

    /**
     * Get the value for SingleBlobUploadThresholdInBytes
     *
     * @return int
     */
    public function getSingleBlobUploadThresholdInBytes()
    {
        return $this->singleBlobUploadThresholdInBytes;
    }

    /**
     * Get the value for blockSize
     *
     * @return int
     */
    public function getBlockSize()
    {
        return $this->blockSize;
    }

    /**
     * Set the value for SingleBlobUploadThresholdInBytes, Max 256MB
     *
     * @param int $val The max size to send as a single blob block
     *
     * @return void
     */
    public function setSingleBlobUploadThresholdInBytes($val)
    {
        if ($val > Resources::MB_IN_BYTES_256) {
            // What should the proper action here be?
            $val = Resources::MB_IN_BYTES_256;
        } elseif ($val < 1) {
            // another spot that could use looking at
            $val = Resources::MB_IN_BYTES_32;
        }
        $this->singleBlobUploadThresholdInBytes = $val;
        //If block size is larger than singleBlobUploadThresholdInBytes, honor
        //threshold.
        $this->blockSize = $val > $this->blockSize ? $this->blockSize : $val;
    }

    /**
     * Set the value for block size, Max 100MB
     *
     * @param int $val The max size for each block to be sent.
     *
     * @return void
     */
    public function setBlockSize($val)
    {
        if ($val > Resources::MB_IN_BYTES_100) {
            // What should the proper action here be?
            $val = Resources::MB_IN_BYTES_100;
        } elseif ($val < 1) {
            // another spot that could use looking at
            $val = Resources::MB_IN_BYTES_4;
        }
        //If block size is larger than singleBlobUploadThresholdInBytes, honor
        //threshold.
        $val = $val > $this->singleBlobUploadThresholdInBytes ?
            $this->singleBlobUploadThresholdInBytes : $val;
        $this->blockSize = $val;
    }

    /**
     * Get the block size of multiple upload block size using the provided
     * content
     *
     * @param  StreamInterface $content The content of the blocks.
     *
     * @return int
     */
    private function getMultipleUploadBlockSizeUsingContent($content)
    {
        //Default value is 100 MB.
        $result = Resources::MB_IN_BYTES_100;
        //PHP must be ran in 64bit environment so content->getSize() could
        //return a guaranteed accurate size.
        if (Utilities::is64BitPHP()) {
            //Content must be seekable to determine the size.
            if ($content->isSeekable()) {
                $size = $content->getSize();
                //When threshold is lower than 100MB, assume maximum number of
                //block is used for the block blob, if the blockSize is still
                //smaller than the assumed size, it means assumed size should
                //be hornored, otherwise the blocks count will exceed maximum
                //value allowed.
                if ($this->blockSize < $result) {
                    $assumedSize = ceil((float)$size /
                        (float)(Resources::MAX_BLOB_BLOCKS));
                    if ($this->blockSize <= $assumedSize) {
                        $result = $assumedSize;
                    } else {
                        $result = $this->blockSize;
                    }
                }
            }
        } else {
            // If not, we could only honor user's setting to determine
            // chunk size.
            $result = $this->blockSize;
        }
        return $result;
    }

    /**
     * Gets the copy blob source name with specified parameters.
     *
     * @param string                 $containerName The name of the container.
     * @param string                 $blobName      The name of the blob.
     * @param Models\CopyBlobOptions $options       The optional parameters.
     *
     * @return string
     */
    private function getCopyBlobSourceName(
        $containerName,
        $blobName,
        Models\CopyBlobOptions $options
    ) {
        $sourceName = $this->getBlobUrl($containerName, $blobName);

        if (!is_null($options->getSourceSnapshot())) {
            $sourceName .= '?snapshot=' . $options->getSourceSnapshot();
        }

        return $sourceName;
    }

    /**
     * Creates URI path for blob or container.
     *
     * @param string $container The container name.
     * @param string $blob      The blob name.
     *
     * @return string
     */
    private function createPath($container, $blob = '')
    {
        if (empty($blob) && ($blob != '0')) {
            return empty($container) ? '/' : $container;
        }
        $encodedBlob = urlencode($blob);
        // Unencode the forward slashes to match what the server expects.
        $encodedBlob = str_replace('%2F', '/', $encodedBlob);
        // Unencode the backward slashes to match what the server expects.
        $encodedBlob = str_replace('%5C', '/', $encodedBlob);
        // Re-encode the spaces (encoded as space) to the % encoding.
        $encodedBlob = str_replace('+', '%20', $encodedBlob);
        // Empty container means accessing default container
        if (empty($container)) {
            return $encodedBlob;
        }
        return '/' . $container . '/' . $encodedBlob;
    }

    /**
     * Creates full URI to the given blob.
     *
     * @param string $container The container name.
     * @param string $blob      The blob name.
     *
     * @return string
     */
    public function getBlobUrl($container, $blob)
    {
        $encodedBlob = $this->createPath($container, $blob);
        $uri = $this->getPsrPrimaryUri();
        $exPath = $uri->getPath();

        if ($exPath != '') {
            //Remove the duplicated slash in the path.
            $encodedBlob = str_replace('//', '/', $exPath . $encodedBlob);
        }

        return (string) $uri->withPath($encodedBlob);
    }

    /**
     * Helper method to create promise for getContainerProperties API call.
     *
     * @param string                    $container The container name.
     * @param Models\BlobServiceOptions $options   The optional parameters.
     * @param string                    $operation The operation string. Should be
     * 'metadata' to get metadata.
     *
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    private function getContainerPropertiesAsyncImpl(
        $container,
        Models\BlobServiceOptions $options = null,
        $operation = null
    ) {
        Validate::canCastAsString($container, 'container');

        $method      = Resources::HTTP_GET;
        $headers     = array();
        $queryParams = array();
        $postParams  = array();
        $path        = $this->createPath($container);

        if (is_null($options)) {
            $options = new BlobServiceOptions();
        }

        $this->addOptionalQueryParam(
            $queryParams,
            Resources::QP_REST_TYPE,
            'container'
        );
        $this->addOptionalQueryParam(
            $queryParams,
            Resources::QP_COMP,
            $operation
        );

        $this->addOptionalHeader(
            $headers,
            Resources::X_MS_LEASE_ID,
            $options->getLeaseId()
        );
        $this->addOptionalAccessConditionHeader(
            $headers,
            $options->getAccessConditions()
        );

        return $this->sendAsync(
            $method,
            $headers,
            $queryParams,
            $postParams,
            $path,
            Resources::STATUS_OK,
            Resources::EMPTY_STRING,
            $options
        )->then(function ($response) {
            $responseHeaders = HttpFormatter::formatHeaders($response->getHeaders());
            return GetContainerPropertiesResult::create($responseHeaders);
        }, null);
    }

    /**
     * Adds optional create blob headers.
     *
     * @param CreateBlobOptions $options The optional parameters.
     * @param array             $headers The HTTP request headers.
     *
     * @return array
     */
    private function addCreateBlobOptionalHeaders(
        CreateBlobOptions $options,
        array $headers
    ) {
        $headers = $this->addOptionalAccessConditionHeader(
            $headers,
            $options->getAccessConditions()
        );

        $this->addOptionalHeader(
            $headers,
            Resources::X_MS_LEASE_ID,
            $options->getLeaseId()
        );

        $headers = $this->addMetadataHeaders(
            $headers,
            $options->getMetadata()
        );

        $contentType = $options->getContentType();
        if (is_null($contentType)) {
            $contentType = Resources::BINARY_FILE_TYPE;
        }

        $this->addOptionalHeader(
            $headers,
            Resources::X_MS_BLOB_CONTENT_TYPE,
            $contentType
        );
        $this->addOptionalHeader(
            $headers,
            Resources::X_MS_BLOB_CONTENT_ENCODING,
            $options->getContentEncoding()
        );
        $this->addOptionalHeader(
            $headers,
            Resources::X_MS_BLOB_CONTENT_LANGUAGE,
            $options->getContentLanguage()
        );
        $this->addOptionalHeader(
            $headers,
            Resources::X_MS_BLOB_CONTENT_MD5,
            $options->getContentMD5()
        );
        $this->addOptionalHeader(
            $headers,
            Resources::X_MS_BLOB_CACHE_CONTROL,
            $options->getCacheControl()
        );
        $this->addOptionalHeader(
            $headers,
            Resources::X_MS_BLOB_CONTENT_DISPOSITION,
            $options->getContentDisposition()
        );
        $this->addOptionalHeader(
            $headers,
            Resources::CONTENT_TYPE,
            Resources::URL_ENCODED_CONTENT_TYPE
        );

        return $headers;
    }

    /**
     * Adds Range header to the headers array.
     *
     * @param array   $headers The HTTP request headers.
     * @param integer $start   The start byte.
     * @param integer $end     The end byte.
     *
     * @return array
     */
    private function addOptionalRangeHeader(array $headers, $start, $end)
    {
        if (!is_null($start) || !is_null($end)) {
            $range      = $start . '-' . $end;
            $rangeValue = 'bytes=' . $range;
            $this->addOptionalHeader($headers, Resources::RANGE, $rangeValue);
        }

        return $headers;
    }

    /**
     * Get the expected status code of a given lease action.
     *
     * @param  string $leaseAction The given lease action
     * @return string
     * @throws \Exception
     */
    private static function getStatusCodeOfLeaseAction($leaseAction)
    {
        switch ($leaseAction) {
            case LeaseMode::ACQUIRE_ACTION:
                $statusCode = Resources::STATUS_CREATED;
                break;
            case LeaseMode::RENEW_ACTION:
                $statusCode = Resources::STATUS_OK;
                break;
            case LeaseMode::RELEASE_ACTION:
                $statusCode = Resources::STATUS_OK;
                break;
            case LeaseMode::BREAK_ACTION:
                $statusCode = Resources::STATUS_ACCEPTED;
                break;
            default:
                throw new \Exception(Resources::NOT_IMPLEMENTED_MSG);
        }

        return $statusCode;
    }

    /**
     * Creates promise that does the actual work for leasing a blob.
     *
     * @param string                    $leaseAction        Lease action string.
     * @param string                    $container          Container name.
     * @param string                    $blob               Blob to lease name.
     * @param string                    $proposedLeaseId    Proposed lease id.
     * @param int                       $leaseDuration      Lease duration, in seconds.
     * @param string                    $leaseId            Existing lease id.
     * @param int                       $breakPeriod        Break period, in seconds.
     * @param string                    $expectedStatusCode Expected status code.
     * @param Models\BlobServiceOptions $options            Optional parameters.
     * @param Models\AccessCondition    $accessCondition    Access conditions.
     *
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    private function putLeaseAsyncImpl(
        $leaseAction,
        $container,
        $blob,
        $proposedLeaseId,
        $leaseDuration,
        $leaseId,
        $breakPeriod,
        $expectedStatusCode,
        Models\BlobServiceOptions $options,
        Models\AccessCondition $accessCondition = null
    ) {
        Validate::canCastAsString($blob, 'blob');
        Validate::canCastAsString($container, 'container');
        Validate::notNullOrEmpty($container, 'container');

        $method      = Resources::HTTP_PUT;
        $headers     = array();
        $queryParams = array();
        $postParams  = array();

        if (empty($blob)) {
            $path = $this->createPath($container);
            $this->addOptionalQueryParam(
                $queryParams,
                Resources::QP_REST_TYPE,
                'container'
            );
        } else {
            $path = $this->createPath($container, $blob);
        }
        $this->addOptionalQueryParam($queryParams, Resources::QP_COMP, 'lease');
        $this->addOptionalQueryParam(
            $queryParams,
            Resources::QP_TIMEOUT,
            $options->getTimeout()
        );

        $this->addOptionalHeader($headers, Resources::X_MS_LEASE_ID, $leaseId);
        $this->addOptionalHeader(
            $headers,
            Resources::X_MS_LEASE_ACTION,
            $leaseAction
        );
        $this->addOptionalHeader(
            $headers,
            Resources::X_MS_LEASE_BREAK_PERIOD,
            $breakPeriod
        );
        $this->addOptionalHeader(
            $headers,
            Resources::X_MS_LEASE_DURATION,
            $leaseDuration
        );
        $this->addOptionalHeader(
            $headers,
            Resources::X_MS_PROPOSED_LEASE_ID,
            $proposedLeaseId
        );
        $this->addOptionalAccessConditionHeader($headers, $accessCondition);

        if (!is_null($options)) {
            $options = new BlobServiceOptions();
        }

        $options->setLocationMode(LocationMode::PRIMARY_ONLY);

        return $this->sendAsync(
            $method,
            $headers,
            $queryParams,
            $postParams,
            $path,
            $expectedStatusCode,
            Resources::EMPTY_STRING,
            $options
        );
    }

    /**
     * Creates promise that does actual work for create and clear blob pages.
     *
     * @param string                 $action    Either clear or create.
     * @param string                 $container The container name.
     * @param string                 $blob      The blob name.
     * @param Range                  $range     The page ranges.
     * @param string                 $content   The content string.
     * @param CreateBlobPagesOptions $options   The optional parameters.
     *
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    private function updatePageBlobPagesAsyncImpl(
        $action,
        $container,
        $blob,
        Range $range,
        $content,
        CreateBlobPagesOptions $options = null
    ) {
        Validate::canCastAsString($blob, 'blob');
        Validate::notNullOrEmpty($blob, 'blob');
        Validate::canCastAsString($container, 'container');
        Validate::canCastAsString($content, 'content');
        Validate::isTrue(
            $range instanceof Range,
            sprintf(
                Resources::INVALID_PARAM_MSG,
                'range',
                get_class(new Range(0))
            )
        );
        $body = Psr7\stream_for($content);

        $method      = Resources::HTTP_PUT;
        $headers     = array();
        $queryParams = array();
        $postParams  = array();
        $path        = $this->createPath($container, $blob);

        if (is_null($options)) {
            $options = new CreateBlobPagesOptions();
        }

        $headers = $this->addOptionalRangeHeader(
            $headers,
            $range->getStart(),
            $range->getEnd()
        );

        $headers = $this->addOptionalAccessConditionHeader(
            $headers,
            $options->getAccessConditions()
        );

        $this->addOptionalHeader(
            $headers,
            Resources::X_MS_LEASE_ID,
            $options->getLeaseId()
        );
        $this->addOptionalHeader(
            $headers,
            Resources::CONTENT_MD5,
            $options->getContentMD5()
        );
        $this->addOptionalHeader(
            $headers,
            Resources::X_MS_PAGE_WRITE,
            $action
        );
        $this->addOptionalHeader(
            $headers,
            Resources::CONTENT_TYPE,
            Resources::URL_ENCODED_CONTENT_TYPE
        );
        $this->addOptionalQueryParam($queryParams, Resources::QP_COMP, 'page');

        $options->setLocationMode(LocationMode::PRIMARY_ONLY);

        return $this->sendAsync(
            $method,
            $headers,
            $queryParams,
            $postParams,
            $path,
            Resources::STATUS_CREATED,
            $body,
            $options
        )->then(function ($response) {
            return CreateBlobPagesResult::create(
                HttpFormatter::formatHeaders($response->getHeaders())
            );
        }, null);
    }

    /**
     * Lists all of the containers in the given storage account.
     *
     * @param ListContainersOptions $options The optional parameters.
     *
     * @return ListContainersResult
     *
     * @see http://msdn.microsoft.com/en-us/library/windowsazure/dd179352.aspx
     */
    public function listContainers(ListContainersOptions $options = null)
    {
        return $this->listContainersAsync($options)->wait();
    }

    /**
     * Create a promise for lists all of the containers in the given
     * storage account.
     *
     * @param  ListContainersOptions $options The optional parameters.
     *
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function listContainersAsync(
        ListContainersOptions $options = null
    ) {
        $method      = Resources::HTTP_GET;
        $headers     = array();
        $queryParams = array();
        $postParams  = array();
        $path        = Resources::EMPTY_STRING;

        if (is_null($options)) {
            $options = new ListContainersOptions();
        }

        $this->addOptionalQueryParam(
            $queryParams,
            Resources::QP_COMP,
            'list'
        );
        $this->addOptionalQueryParam(
            $queryParams,
            Resources::QP_PREFIX_LOWERCASE,
            $options->getPrefix()
        );
        $this->addOptionalQueryParam(
            $queryParams,
            Resources::QP_MARKER_LOWERCASE,
            $options->getNextMarker()
        );
        $this->addOptionalQueryParam(
            $queryParams,
            Resources::QP_MAX_RESULTS_LOWERCASE,
            $options->getMaxResults()
        );
        $isInclude = $options->getIncludeMetadata();
        $isInclude = $isInclude ? 'metadata' : null;
        $this->addOptionalQueryParam(
            $queryParams,
            Resources::QP_INCLUDE,
            $isInclude
        );

        $dataSerializer = $this->dataSerializer;

        return $this->sendAsync(
            $method,
            $headers,
            $queryParams,
            $postParams,
            $path,
            Resources::STATUS_OK,
            Resources::EMPTY_STRING,
            $options
        )->then(function ($response) use ($dataSerializer) {
            $parsed = $this->dataSerializer->unserialize($response->getBody());
            return ListContainersResult::create(
                $parsed,
                Utilities::getLocationFromHeaders($response->getHeaders())
            );
        });
    }

    /**
     * Creates a new container in the given storage account.
     *
     * @param string                        $container The container name.
     * @param Models\CreateContainerOptions $options   The optional parameters.
     *
     * @return void
     *
     * @see http://msdn.microsoft.com/en-us/library/windowsazure/dd179468.aspx
     */
    public function createContainer(
        $container,
        Models\CreateContainerOptions $options = null
    ) {
        $this->createContainerAsync($container, $options)->wait();
    }

    /**
     * Creates a new container in the given storage account.
     *
     * @param string                        $container The container name.
     * @param Models\CreateContainerOptions $options   The optional parameters.
     *
     * @return \GuzzleHttp\Promise\PromiseInterface
     *
     * @see http://msdn.microsoft.com/en-us/library/windowsazure/dd179468.aspx
     */
    public function createContainerAsync(
        $container,
        Models\CreateContainerOptions $options = null
    ) {
        Validate::canCastAsString($container, 'container');
        Validate::notNullOrEmpty($container, 'container');

        $method      = Resources::HTTP_PUT;
        $postParams  = array();
        $queryParams = array(Resources::QP_REST_TYPE => 'container');
        $path        = $this->createPath($container);

        if (is_null($options)) {
            $options = new CreateContainerOptions();
        }

        $metadata = $options->getMetadata();
        $headers  = $this->generateMetadataHeaders($metadata);
        $this->addOptionalHeader(
            $headers,
            Resources::X_MS_BLOB_PUBLIC_ACCESS,
            $options->getPublicAccess()
        );

        $options->setLocationMode(LocationMode::PRIMARY_ONLY);

        return $this->sendAsync(
            $method,
            $headers,
            $queryParams,
            $postParams,
            $path,
            Resources::STATUS_CREATED,
            Resources::EMPTY_STRING,
            $options
        );
    }

    /**
     * Deletes a container in the given storage account.
     *
     * @param string                        $container The container name.
     * @param Models\BlobServiceOptions     $options   The optional parameters.
     *
     * @return void
     *
     * @see http://msdn.microsoft.com/en-us/library/windowsazure/dd179408.aspx
     */
    public function deleteContainer(
        $container,
        Models\BlobServiceOptions $options = null
    ) {
        $this->deleteContainerAsync($container, $options)->wait();
    }

    /**
     * Create a promise for deleting a container.
     *
     * @param  string                             $container name of the container
     * @param  Models\BlobServiceOptions|null     $options   optional parameters
     *
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function deleteContainerAsync(
        $container,
        Models\BlobServiceOptions $options = null
    ) {
        Validate::canCastAsString($container, 'container');
        Validate::notNullOrEmpty($container, 'container');

        $method      = Resources::HTTP_DELETE;
        $headers     = array();
        $postParams  = array();
        $queryParams = array();
        $path        = $this->createPath($container);

        if (is_null($options)) {
            $options = new BlobServiceOptions();
        }

        $this->addOptionalHeader(
            $headers,
            Resources::X_MS_LEASE_ID,
            $options->getLeaseId()
        );
        $headers = $this->addOptionalAccessConditionHeader(
            $headers,
            $options->getAccessConditions()
        );

        $this->addOptionalQueryParam(
            $queryParams,
            Resources::QP_REST_TYPE,
            'container'
        );

        $options->setLocationMode(LocationMode::PRIMARY_ONLY);

        return $this->sendAsync(
            $method,
            $headers,
            $queryParams,
            $postParams,
            $path,
            Resources::STATUS_ACCEPTED,
            Resources::EMPTY_STRING,
            $options
        );
    }

    /**
     * Returns all properties and metadata on the container.
     *
     * @param string                    $container name
     * @param Models\BlobServiceOptions $options   optional parameters
     *
     * @return Models\GetContainerPropertiesResult
     *
     * @see http://msdn.microsoft.com/en-us/library/windowsazure/dd179370.aspx
     */
    public function getContainerProperties(
        $container,
        Models\BlobServiceOptions $options = null
    ) {
        return $this->getContainerPropertiesAsync($container, $options)->wait();
    }

    /**
     * Create promise to return all properties and metadata on the container.
     *
     * @param string                    $container name
     * @param Models\BlobServiceOptions $options   optional parameters
     *
     * @return \GuzzleHttp\Promise\PromiseInterface
     *
     * @see http://msdn.microsoft.com/en-us/library/windowsazure/dd179370.aspx
     */
    public function getContainerPropertiesAsync(
        $container,
        Models\BlobServiceOptions $options = null
    ) {
        return $this->getContainerPropertiesAsyncImpl($container, $options);
    }

    /**
     * Returns only user-defined metadata for the specified container.
     *
     * @param string                    $container name
     * @param Models\BlobServiceOptions $options   optional parameters
     *
     * @return Models\GetContainerPropertiesResult
     *
     * @see http://msdn.microsoft.com/en-us/library/windowsazure/ee691976.aspx
     */
    public function getContainerMetadata(
        $container,
        Models\BlobServiceOptions $options = null
    ) {
        return $this->getContainerMetadataAsync($container, $options)->wait();
    }

    /**
     * Create promise to return only user-defined metadata for the specified
     * container.
     *
     * @param string                    $container name
     * @param Models\BlobServiceOptions $options   optional parameters
     *
     * @return \GuzzleHttp\Promise\PromiseInterface
     *
     * @see http://msdn.microsoft.com/en-us/library/windowsazure/ee691976.aspx
     */
    public function getContainerMetadataAsync(
        $container,
        Models\BlobServiceOptions $options = null
    ) {
        return $this->getContainerPropertiesAsyncImpl($container, $options, 'metadata');
    }

    /**
     * Gets the access control list (ACL) and any container-level access policies
     * for the container.
     *
     * @param string                    $container The container name.
     * @param Models\BlobServiceOptions $options   The optional parameters.
     *
     * @return Models\GetContainerACLResult
     *
     * @see http://msdn.microsoft.com/en-us/library/windowsazure/dd179469.aspx
     */
    public function getContainerAcl(
        $container,
        Models\BlobServiceOptions $options = null
    ) {
        return $this->getContainerAclAsync($container, $options)->wait();
    }

    /**
     * Creates the promise to get the access control list (ACL) and any
     * container-level access policies for the container.
     *
     * @param string                    $container The container name.
     * @param Models\BlobServiceOptions $options   The optional parameters.
     *
     * @return \GuzzleHttp\Promise\PromiseInterface
     *
     * @see http://msdn.microsoft.com/en-us/library/windowsazure/dd179469.aspx
     */
    public function getContainerAclAsync(
        $container,
        Models\BlobServiceOptions $options = null
    ) {
        Validate::canCastAsString($container, 'container');

        $method      = Resources::HTTP_GET;
        $headers     = array();
        $postParams  = array();
        $queryParams = array();
        $path        = $this->createPath($container);

        if (is_null($options)) {
            $options = new BlobServiceOptions();
        }

        $this->addOptionalQueryParam(
            $queryParams,
            Resources::QP_REST_TYPE,
            'container'
        );
        $this->addOptionalQueryParam(
            $queryParams,
            Resources::QP_COMP,
            'acl'
        );

        $this->addOptionalHeader(
            $headers,
            Resources::X_MS_LEASE_ID,
            $options->getLeaseId()
        );
        $this->addOptionalAccessConditionHeader(
            $headers,
            $options->getAccessConditions()
        );

        $dataSerializer = $this->dataSerializer;

        $promise = $this->sendAsync(
            $method,
            $headers,
            $queryParams,
            $postParams,
            $path,
            Resources::STATUS_OK,
            Resources::EMPTY_STRING,
            $options
        );

        return $promise->then(function ($response) use ($dataSerializer) {
            $responseHeaders = HttpFormatter::formatHeaders($response->getHeaders());

            $access = Utilities::tryGetValue(
                $responseHeaders,
                Resources::X_MS_BLOB_PUBLIC_ACCESS
            );
            $etag = Utilities::tryGetValue($responseHeaders, Resources::ETAG);
            $modified = Utilities::tryGetValue(
                $responseHeaders,
                Resources::LAST_MODIFIED
            );
            $modifiedDate = Utilities::convertToDateTime($modified);
            $parsed       = $dataSerializer->unserialize($response->getBody());

            return GetContainerAclResult::create(
                $access,
                $etag,
                $modifiedDate,
                $parsed
            );
        }, null);
    }

    /**
     * Sets the ACL and any container-level access policies for the container.
     *
     * @param string                    $container name
     * @param Models\ContainerACL       $acl       access control list for container
     * @param Models\BlobServiceOptions $options   optional parameters
     *
     * @return void
     *
     * @see http://msdn.microsoft.com/en-us/library/windowsazure/dd179391.aspx
     */
    public function setContainerAcl(
        $container,
        Models\ContainerACL $acl,
        Models\BlobServiceOptions $options = null
    ) {
        $this->setContainerAclAsync($container, $acl, $options)->wait();
    }

    /**
     * Creates promise to set the ACL and any container-level access policies
     * for the container.
     *
     * @param string                    $container name
     * @param Models\ContainerACL       $acl       access control list for container
     * @param Models\BlobServiceOptions $options   optional parameters
     *
     * @return \GuzzleHttp\Promise\PromiseInterface
     *
     * @see http://msdn.microsoft.com/en-us/library/windowsazure/dd179391.aspx
     */
    public function setContainerAclAsync(
        $container,
        Models\ContainerACL $acl,
        Models\BlobServiceOptions $options = null
    ) {
        Validate::canCastAsString($container, 'container');
        Validate::notNullOrEmpty($acl, 'acl');

        $method      = Resources::HTTP_PUT;
        $headers     = array();
        $postParams  = array();
        $queryParams = array();
        $path        = $this->createPath($container);
        $body        = $acl->toXml($this->dataSerializer);

        if (is_null($options)) {
            $options = new BlobServiceOptions();
        }

        $this->addOptionalQueryParam(
            $queryParams,
            Resources::QP_REST_TYPE,
            'container'
        );
        $this->addOptionalQueryParam(
            $queryParams,
            Resources::QP_COMP,
            'acl'
        );
        $this->addOptionalHeader(
            $headers,
            Resources::X_MS_BLOB_PUBLIC_ACCESS,
            $acl->getPublicAccess()
        );
        $this->addOptionalHeader(
            $headers,
            Resources::CONTENT_TYPE,
            Resources::URL_ENCODED_CONTENT_TYPE
        );
        $this->addOptionalHeader(
            $headers,
            Resources::X_MS_LEASE_ID,
            $options->getLeaseId()
        );
        $this->addOptionalAccessConditionHeader(
            $headers,
            $options->getAccessConditions()
        );

        $options->setLocationMode(LocationMode::PRIMARY_ONLY);

        return $this->sendAsync(
            $method,
            $headers,
            $queryParams,
            $postParams,
            $path,
            Resources::STATUS_OK,
            $body,
            $options
        );
    }

    /**
     * Sets metadata headers on the container.
     *
     * @param string                    $container name
     * @param array                     $metadata  metadata key/value pair.
     * @param Models\BlobServiceOptions $options   optional parameters
     *
     * @return void
     *
     * @see http://msdn.microsoft.com/en-us/library/windowsazure/dd179362.aspx
     */
    public function setContainerMetadata(
        $container,
        array $metadata,
        Models\BlobServiceOptions $options = null
    ) {
        $this->setContainerMetadataAsync($container, $metadata, $options)->wait();
    }

    /**
     * Sets metadata headers on the container.
     *
     * @param string                   $container name
     * @param array                    $metadata  metadata key/value pair.
     * @param Models\BlobServiceOptions $options   optional parameters
     *
     * @return \GuzzleHttp\Promise\PromiseInterface
     *
     * @see http://msdn.microsoft.com/en-us/library/windowsazure/dd179362.aspx
     */
    public function setContainerMetadataAsync(
        $container,
        array $metadata,
        Models\BlobServiceOptions $options = null
    ) {
        Validate::canCastAsString($container, 'container');
        Utilities::validateMetadata($metadata);

        $method      = Resources::HTTP_PUT;
        $headers     = $this->generateMetadataHeaders($metadata);
        $postParams  = array();
        $queryParams = array();
        $path        = $this->createPath($container);

        if (is_null($options)) {
            $options = new BlobServiceOptions();
        }

        $this->addOptionalQueryParam(
            $queryParams,
            Resources::QP_REST_TYPE,
            'container'
        );
        $this->addOptionalQueryParam(
            $queryParams,
            Resources::QP_COMP,
            'metadata'
        );

        $this->addOptionalHeader(
            $headers,
            Resources::X_MS_LEASE_ID,
            $options->getLeaseId()
        );

        $headers = $this->addOptionalAccessConditionHeader(
            $headers,
            $options->getAccessConditions()
        );

        $options->setLocationMode(LocationMode::PRIMARY_ONLY);

        return $this->sendAsync(
            $method,
            $headers,
            $queryParams,
            $postParams,
            $path,
            Resources::STATUS_OK,
            Resources::EMPTY_STRING,
            $options
        );
    }

    /**
     * Sets blob tier on the blob.
     *
     * @param string                    $container name
     * @param string                    $blob      name of the blob
     * @param Models\SetBlobTierOptions $options   optional parameters
     *
     * @return void
     *
     * @see https://docs.microsoft.com/en-us/rest/api/storageservices/set-blob-tier
     */
    public function setBlobTier(
        $container,
        $blob,
        Models\SetBlobTierOptions $options = null
    ) {
        $this->setBlobTierAsync($container, $blob, $options)->wait();
    }

    /**
     * Sets blob tier on the blob.
     *
     * @param string                    $container name
     * @param string                    $blob      name of the blob
     * @param Models\SetBlobTierOptions $options   optional parameters
     *
     * @return \GuzzleHttp\Promise\PromiseInterface
     *
     * @see https://docs.microsoft.com/en-us/rest/api/storageservices/set-blob-tier
     */
    public function setBlobTierAsync(
        $container,
        $blob,
        Models\SetBlobTierOptions $options = null
    )
    {
        Validate::canCastAsString($container, 'container');
        Validate::canCastAsString($blob, 'blob');
        Validate::notNullOrEmpty($blob, 'blob');

        $method      = Resources::HTTP_PUT;
        $headers     = array();
        $postParams  = array();
        $queryParams = array();
        $path        = $this->createPath($container, $blob);

        if (is_null($options)) {
            $options = new SetBlobTierOptions();
        }

        $this->addOptionalQueryParam(
            $queryParams,
            Resources::QP_COMP,
            'tier'
        );

        $this->addOptionalHeader(
            $headers,
            Resources::X_MS_ACCESS_TIER,
            $options->getAccessTier()
        );

        $options->setLocationMode(LocationMode::PRIMARY_ONLY);

        return $this->sendAsync(
            $method,
            $headers,
            $queryParams,
            $postParams,
            $path,
            array(Resources::STATUS_OK, Resources::STATUS_ACCEPTED),
            Resources::EMPTY_STRING,
            $options
        );
    }

    /**
     * Lists all of the blobs in the given container.
     *
     * @param string                  $container The container name.
     * @param Models\ListBlobsOptions $options   The optional parameters.
     *
     * @return Models\ListBlobsResult
     *
     * @see http://msdn.microsoft.com/en-us/library/windowsazure/dd135734.aspx
     */
    public function listBlobs($container, Models\ListBlobsOptions $options = null)
    {
        return $this->listBlobsAsync($container, $options)->wait();
    }

    /**
     * Creates promise to list all of the blobs in the given container.
     *
     * @param string                  $container The container name.
     * @param Models\ListBlobsOptions $options   The optional parameters.
     *
     * @return \GuzzleHttp\Promise\PromiseInterface
     *
     * @see http://msdn.microsoft.com/en-us/library/windowsazure/dd135734.aspx
     */
    public function listBlobsAsync(
        $container,
        Models\ListBlobsOptions $options = null
    ) {
        Validate::notNull($container, 'container');
        Validate::canCastAsString($container, 'container');

        $method      = Resources::HTTP_GET;
        $headers     = array();
        $postParams  = array();
        $queryParams = array();
        $path        = $this->createPath($container);

        if (is_null($options)) {
            $options = new ListBlobsOptions();
        }

        $this->addOptionalQueryParam(
            $queryParams,
            Resources::QP_REST_TYPE,
            'container'
        );
        $this->addOptionalQueryParam(
            $queryParams,
            Resources::QP_COMP,
            'list'
        );
        $this->addOptionalQueryParam(
            $queryParams,
            Resources::QP_PREFIX_LOWERCASE,
            str_replace('\\', '/', $options->getPrefix())
        );
        $this->addOptionalQueryParam(
            $queryParams,
            Resources::QP_MARKER_LOWERCASE,
            $options->getNextMarker()
        );
        $this->addOptionalQueryParam(
            $queryParams,
            Resources::QP_DELIMITER,
            $options->getDelimiter()
        );
        $this->addOptionalQueryParam(
            $queryParams,
            Resources::QP_MAX_RESULTS_LOWERCASE,
            $options->getMaxResults()
        );

        $includeMetadata         = $options->getIncludeMetadata();
        $includeSnapshots        = $options->getIncludeSnapshots();
        $includeUncommittedBlobs = $options->getIncludeUncommittedBlobs();
        $includecopy             = $options->getIncludeCopy();
        $includeDeleted          = $options->getIncludeDeleted();

        $includeValue = static::groupQueryValues(
            array(
                $includeMetadata ? 'metadata' : null,
                $includeSnapshots ? 'snapshots' : null,
                $includeUncommittedBlobs ? 'uncommittedblobs' : null,
                $includecopy ? 'copy' : null,
                $includeDeleted ? 'deleted' : null,
            )
        );

        $this->addOptionalQueryParam(
            $queryParams,
            Resources::QP_INCLUDE,
            $includeValue
        );

        $dataSerializer = $this->dataSerializer;

        return $this->sendAsync(
            $method,
            $headers,
            $queryParams,
            $postParams,
            $path,
            Resources::STATUS_OK,
            Resources::EMPTY_STRING,
            $options
        )->then(function ($response) use ($dataSerializer) {
            $parsed = $dataSerializer->unserialize($response->getBody());
            return ListBlobsResult::create(
                $parsed,
                Utilities::getLocationFromHeaders($response->getHeaders())
            );
        }, null);
    }

    /**
     * Creates a new page blob. Note that calling createPageBlob to create a page
     * blob only initializes the blob.
     * To add content to a page blob, call createBlobPages method.
     *
     * @param string                   $container The container name.
     * @param string                   $blob      The blob name.
     * @param integer                  $length    Specifies the maximum size
     *                                            for the page blob, up to 1 TB.
     *                                            The page blob size must be
     *                                            aligned to a 512-byte
     *                                            boundary.
     * @param Models\CreatePageBlobOptions $options   The optional parameters.
     *
     * @return Models\PutBlobResult
     *
     * @see http://msdn.microsoft.com/en-us/library/windowsazure/dd179451.aspx
     */
    public function createPageBlob(
        $container,
        $blob,
        $length,
        Models\CreatePageBlobOptions $options = null
    ) {
        return $this->createPageBlobAsync(
            $container,
            $blob,
            $length,
            $options
        )->wait();
    }

    /**
     * Creates promise to create a new page blob. Note that calling
     * createPageBlob to create a page blob only initializes the blob.
     * To add content to a page blob, call createBlobPages method.
     *
     * @param string                   $container The container name.
     * @param string                   $blob      The blob name.
     * @param integer                  $length    Specifies the maximum size
     *                                            for the page blob, up to 1 TB.
     *                                            The page blob size must be
     *                                            aligned to a 512-byte
     *                                            boundary.
     * @param Models\CreatePageBlobOptions $options   The optional parameters.
     *
     * @return \GuzzleHttp\Promise\PromiseInterface
     *
     * @see http://msdn.microsoft.com/en-us/library/windowsazure/dd179451.aspx
     */
    public function createPageBlobAsync(
        $container,
        $blob,
        $length,
        Models\CreatePageBlobOptions $options = null
    ) {
        Validate::canCastAsString($container, 'container');
        Validate::canCastAsString($blob, 'blob');
        Validate::notNullOrEmpty($blob, 'blob');
        Validate::isInteger($length, 'length');
        Validate::notNull($length, 'length');

        $method      = Resources::HTTP_PUT;
        $headers     = array();
        $postParams  = array();
        $queryParams = array();
        $path        = $this->createPath($container, $blob);

        if (is_null($options)) {
            $options = new CreatePageBlobOptions();
        }

        $this->addOptionalHeader(
            $headers,
            Resources::X_MS_BLOB_TYPE,
            BlobType::PAGE_BLOB
        );
        $this->addOptionalHeader(
            $headers,
            Resources::X_MS_BLOB_CONTENT_LENGTH,
            $length
        );
        $this->addOptionalHeader(
            $headers,
            Resources::X_MS_BLOB_SEQUENCE_NUMBER,
            $options->getSequenceNumber()
        );
        $this->addOptionalHeader(
            $headers,
            Resources::X_MS_ACCESS_TIER,
            $options->getAccessTier()
        );
        $headers = $this->addCreateBlobOptionalHeaders($options, $headers);

        $options->setLocationMode(LocationMode::PRIMARY_ONLY);

        return $this->sendAsync(
            $method,
            $headers,
            $queryParams,
            $postParams,
            $path,
            Resources::STATUS_CREATED,
            Resources::EMPTY_STRING,
            $options
        )->then(function ($response) {
            return PutBlobResult::create(
                HttpFormatter::formatHeaders($response->getHeaders())
            );
        }, null);
    }

    /**
     * Create a new append blob.
     * If the blob already exists on the service, it will be overwritten.
     *
     * @param string                   $container The container name.
     * @param string                   $blob      The blob name.
     * @param Models\CreateBlobOptions $options   The optional parameters.
     *
     * @return Models\PutBlobResult
     *
     * @see http://msdn.microsoft.com/en-us/library/windowsazure/dd179451.aspx
     */
    public function createAppendBlob(
        $container,
        $blob,
        Models\CreateBlobOptions $options = null
    ) {
        return $this->createAppendBlobAsync(
            $container,
            $blob,
            $options
        )->wait();
    }

    /**
     * Creates promise to create a new append blob.
     * If the blob already exists on the service, it will be overwritten.
     *
     * @param string                   $container The container name.
     * @param string                   $blob      The blob name.
     * @param Models\CreateBlobOptions $options   The optional parameters.
     *
     * @return \GuzzleHttp\Promise\PromiseInterface
     *
     * @see http://msdn.microsoft.com/en-us/library/windowsazure/dd179451.aspx
     */
    public function createAppendBlobAsync(
        $container,
        $blob,
        Models\CreateBlobOptions $options = null
    ) {
        Validate::canCastAsString($container, 'container');
        Validate::notNullOrEmpty($container, 'container');
        Validate::canCastAsString($blob, 'blob');
        Validate::notNullOrEmpty($blob, 'blob');

        $method      = Resources::HTTP_PUT;
        $headers     = array();
        $postParams  = array();
        $queryParams = array();
        $path        = $this->createPath($container, $blob);

        if (is_null($options)) {
            $options = new CreateBlobOptions();
        }

        $this->addOptionalHeader(
            $headers,
            Resources::X_MS_BLOB_TYPE,
            BlobType::APPEND_BLOB
        );
        $headers = $this->addCreateBlobOptionalHeaders($options, $headers);

        $options->setLocationMode(LocationMode::PRIMARY_ONLY);

        return $this->sendAsync(
            $method,
            $headers,
            $queryParams,
            $postParams,
            $path,
            Resources::STATUS_CREATED,
            Resources::EMPTY_STRING,
            $options
        )->then(function ($response) {
            return PutBlobResult::create(
                HttpFormatter::formatHeaders($response->getHeaders())
            );
        }, null);
    }

    /**
     * Creates a new block blob or updates the content of an existing block blob.
     *
     * Updating an existing block blob overwrites any existing metadata on the blob.
     * Partial updates are not supported with createBlockBlob the content of the
     * existing blob is overwritten with the content of the new blob. To perform a
     * partial update of the content of a block blob, use the createBlockList
     * method.
     * Note that the default content type is application/octet-stream.
     *
     * @param string                          $container The name of the container.
     * @param string                          $blob      The name of the blob.
     * @param string|resource|StreamInterface $content   The content of the blob.
     * @param Models\CreateBlockBlobOptions   $options   The optional parameters.
     *
     * @return Models\PutBlobResult
     *
     * @see http://msdn.microsoft.com/en-us/library/windowsazure/dd179451.aspx
     */
    public function createBlockBlob(
        $container,
        $blob,
        $content,
        Models\CreateBlockBlobOptions $options = null
    ) {
        return $this->createBlockBlobAsync(
            $container,
            $blob,
            $content,
            $options
        )->wait();
    }

    /**
     * Creates a promise to create a new block blob or updates the content of
     * an existing block blob.
     *
     * Updating an existing block blob overwrites any existing metadata on the blob.
     * Partial updates are not supported with createBlockBlob the content of the
     * existing blob is overwritten with the content of the new blob. To perform a
     * partial update of the content of a block blob, use the createBlockList
     * method.
     *
     * @param string                          $container The name of the container.
     * @param string                          $blob      The name of the blob.
     * @param string|resource|StreamInterface $content   The content of the blob.
     * @param Models\CreateBlockBlobOptions   $options   The optional parameters.
     *
     * @return \GuzzleHttp\Promise\PromiseInterface
     *
     * @see http://msdn.microsoft.com/en-us/library/windowsazure/dd179451.aspx
     */
    public function createBlockBlobAsync(
        $container,
        $blob,
        $content,
        Models\CreateBlockBlobOptions $options = null
    ) {
        $body = Psr7\stream_for($content);

        //If the size of the stream is not seekable or larger than the single
        //upload threshold then call concurrent upload. Otherwise call putBlob.
        $promise = null;
        if (!Utilities::isStreamLargerThanSizeOrNotSeekable(
            $body,
            $this->singleBlobUploadThresholdInBytes
        )) {
            $promise = $this->createBlockBlobBySingleUploadAsync(
                $container,
                $blob,
                $body,
                $options
            );
        } else {
            // This is for large or failsafe upload
            $promise = $this->createBlockBlobByMultipleUploadAsync(
                $container,
                $blob,
                $body,
                $options
            );
        }

        //return the parsed result, instead of the raw response.
        return $promise;
    }

    /**
     * Create a new page blob and upload the content to the page blob.
     *
     * @param string                          $container The name of the container.
     * @param string                          $blob      The name of the blob.
     * @param int                             $length    The length of the blob.
     * @param string|resource|StreamInterface $content   The content of the blob.
     * @param Models\CreatePageBlobFromContentOptions
     *                                        $options   The optional parameters.
     *
     * @return Models\GetBlobPropertiesResult
     *
     * @see https://docs.microsoft.com/en-us/rest/api/storageservices/fileservices/get-blob-properties
     */
    public function createPageBlobFromContent(
        $container,
        $blob,
        $length,
        $content,
        Models\CreatePageBlobFromContentOptions $options = null
    ) {
        return $this->createPageBlobFromContentAsync(
            $container,
            $blob,
            $length,
            $content,
            $options
        )->wait();
    }

    /**
     * Creates a promise to create a new page blob and upload the content
     * to the page blob.
     *
     * @param string                          $container The name of the container.
     * @param string                          $blob      The name of the blob.
     * @param int                             $length    The length of the blob.
     * @param string|resource|StreamInterface $content   The content of the blob.
     * @param Models\CreatePageBlobFromContentOptions
     *                                        $options   The optional parameters.
     *
     * @return \GuzzleHttp\Promise\PromiseInterface
     *
     * @see https://docs.microsoft.com/en-us/rest/api/storageservices/fileservices/get-blob-properties
     */
    public function createPageBlobFromContentAsync(
        $container,
        $blob,
        $length,
        $content,
        Models\CreatePageBlobFromContentOptions $options = null
    ) {
        $body = Psr7\stream_for($content);
        $self = $this;

        if (is_null($options)) {
            $options = new Models\CreatePageBlobFromContentOptions();
        }

        $createBlobPromise = $this->createPageBlobAsync(
            $container,
            $blob,
            $length,
            $options
        );

        $uploadBlobPromise = $createBlobPromise->then(
            function ($value) use (
                $self,
                $container,
                $blob,
                $body,
                $options
            ) {
                $result = $value;
                return $self->uploadPageBlobAsync(
                    $container,
                    $blob,
                    $body,
                    $options
                );
            },
            null
        );

        return $uploadBlobPromise->then(
            function ($value) use (
                $self,
                $container,
                $blob,
                $options
            ) {
                $getBlobPropertiesOptions = new GetBlobPropertiesOptions();
                $getBlobPropertiesOptions->setLeaseId($options->getLeaseId());

                return $self->getBlobPropertiesAsync(
                    $container,
                    $blob,
                    $getBlobPropertiesOptions
                );
            },
            null
        );
    }

    /**
     * Creates promise to create a new block blob or updates the content of an
     * existing block blob. This only supports contents smaller than single
     * upload threashold.
     *
     * Updating an existing block blob overwrites any existing metadata on
     * the blob.
     *
     * @param string                   $container The name of the container.
     * @param string                   $blob      The name of the blob.
     * @param StreamInterface          $content   The content of the blob.
     * @param Models\CreateBlobOptions $options   The optional parameters.
     *
     * @return \GuzzleHttp\Promise\PromiseInterface
     *
     * @see http://msdn.microsoft.com/en-us/library/windowsazure/dd179451.aspx
     */
    protected function createBlockBlobBySingleUploadAsync(
        $container,
        $blob,
        $content,
        Models\CreateBlobOptions $options = null
    ) {
        Validate::canCastAsString($container, 'container');
        Validate::canCastAsString($blob, 'blob');
        Validate::notNullOrEmpty($blob, 'blob');
        Validate::isTrue(
            $options == null ||
            $options instanceof CreateBlobOptions,
            sprintf(
                Resources::INVALID_PARAM_MSG,
                'options',
                get_class(new CreateBlobOptions())
            )
        );

        $method      = Resources::HTTP_PUT;
        $headers     = array();
        $postParams  = array();
        $queryParams = array();
        $path        = $this->createPath($container, $blob);

        if (is_null($options)) {
            $options = new CreateBlobOptions();
        }

        $headers = $this->addCreateBlobOptionalHeaders($options, $headers);

        $this->addOptionalHeader(
            $headers,
            Resources::X_MS_BLOB_TYPE,
            BlobType::BLOCK_BLOB
        );

        $options->setLocationMode(LocationMode::PRIMARY_ONLY);

        return $this->sendAsync(
            $method,
            $headers,
            $queryParams,
            $postParams,
            $path,
            Resources::STATUS_CREATED,
            $content,
            $options
        )->then(
            function ($response) {
                return PutBlobResult::create(
                    HttpFormatter::formatHeaders($response->getHeaders())
                );
            },
            null
        );
    }

    /**
     * This method creates the blob blocks. This method will send the request
     * concurrently for better performance.
     *
     * @param  string                        $container  Name of the container
     * @param  string                        $blob       Name of the blob
     * @param  StreamInterface               $content    Content's stream
     * @param  Models\CreateBlockBlobOptions $options    Array that contains
     *                                                   all the option
     *
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    protected function createBlockBlobByMultipleUploadAsync(
        $container,
        $blob,
        $content,
        Models\CreateBlockBlobOptions $options = null
    ) {
        Validate::canCastAsString($container, 'container');
        Validate::canCastAsString($blob, 'blob');

        if ($content->isSeekable() && Utilities::is64BitPHP()) {
            Validate::isTrue(
                $content->getSize() <= Resources::MAX_BLOCK_BLOB_SIZE,
                Resources::CONTENT_SIZE_TOO_LARGE
            );
        }

        if (is_null($options)) {
            $options = new Models\CreateBlockBlobOptions();
        }

        $createBlobBlockOptions = CreateBlobBlockOptions::create($options);
        $selfInstance = $this;

        $method = Resources::HTTP_PUT;
        $headers = $this->createBlobBlockHeader($createBlobBlockOptions);
        $postParams = array();
        $path = $this->createPath($container, $blob);
        $useTransactionalMD5 = $options->getUseTransactionalMD5();

        $blockIds = array();
        //Determine the block size according to the content and threshold.
        $blockSize = $this->getMultipleUploadBlockSizeUsingContent($content);
        $counter = 0;
        //create the generator for requests.
        //this generator also constructs the blockId array on the fly.
        $generator = function () use (
            $content,
            &$blockIds,
            $blockSize,
            $createBlobBlockOptions,
            $method,
            $headers,
            $postParams,
            $path,
            $useTransactionalMD5,
            &$counter,
            $selfInstance
        ) {
            //read the content.
            $blockContent = $content->read($blockSize);
            //construct the blockId
            $blockId = base64_encode(
                str_pad($counter++, 6, '0', STR_PAD_LEFT)
            );
            $size = strlen($blockContent);
            if ($size == 0) {
                return null;
            }

            if ($useTransactionalMD5) {
                $contentMD5 = base64_encode(md5($blockContent, true));
                $selfInstance->addOptionalHeader(
                    $headers,
                    Resources::CONTENT_MD5,
                    $contentMD5
                );
            }

            //add the id to array.
            array_push($blockIds, new Block($blockId, 'Uncommitted'));
            $queryParams = $selfInstance->createBlobBlockQueryParams(
                $createBlobBlockOptions,
                $blockId,
                true
            );
            //return the array of requests.
            return $selfInstance->createRequest(
                $method,
                $headers,
                $queryParams,
                $postParams,
                $path,
                LocationMode::PRIMARY_ONLY,
                $blockContent
            );
        };

        //Send the request concurrently.
        //Does not need to evaluate the results. If operation not successful,
        //exception will be thrown.
        $putBlobPromise = $this->sendConcurrentAsync(
            $generator,
            Resources::STATUS_CREATED,
            $options
        );

        $commitBlobPromise = $putBlobPromise->then(
            function ($value) use (
                $selfInstance,
                $container,
                $blob,
                &$blockIds,
                $putBlobPromise,
                $options
            ) {
                return $selfInstance->commitBlobBlocksAsync(
                    $container,
                    $blob,
                    $blockIds,
                    CommitBlobBlocksOptions::create($options)
                );
            },
            null
        );

        return $commitBlobPromise;
    }


    /**
     * This method upload the page blob pages. This method will send the request
     * concurrently for better performance.
     *
     * @param  string                   $container  Name of the container
     * @param  string                   $blob       Name of the blob
     * @param  StreamInterface          $content    Content's stream
     * @param  Models\CreatePageBlobFromContentOptions
     *                                  $options    Array that contains
     *                                              all the option
     *
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    private function uploadPageBlobAsync(
        $container,
        $blob,
        $content,
        Models\CreatePageBlobFromContentOptions $options = null
    ) {
        Validate::canCastAsString($container, 'container');
        Validate::notNullOrEmpty($container, 'container');

        Validate::canCastAsString($blob, 'blob');
        Validate::notNullOrEmpty($blob, 'blob');

        if (is_null($options)) {
            $options = new Models\CreatePageBlobFromContentOptions();
        }

        $method      = Resources::HTTP_PUT;
        $postParams  = array();
        $queryParams = array();
        $path        = $this->createPath($container, $blob);
        $useTransactionalMD5 = $options->getUseTransactionalMD5();

        $this->addOptionalQueryParam($queryParams, Resources::QP_COMP, 'page');
        $this->addOptionalQueryParam(
            $queryParams,
            Resources::QP_TIMEOUT,
            $options->getTimeout()
        );

        $pageSize = Resources::MB_IN_BYTES_4;
        $start = 0;
        $end = -1;

        //create the generator for requests.
        $generator = function () use (
            $content,
            $pageSize,
            $method,
            $postParams,
            $queryParams,
            $path,
            $useTransactionalMD5,
            &$start,
            &$end,
            $options
        ) {
            //read the content.
            do {
                $pageContent = $content->read($pageSize);
                $size = strlen($pageContent);

                if ($size == 0) {
                    return null;
                }

                $end += $size;
                $start = ($end - $size + 1);

                // If all Zero, skip this range
            } while (Utilities::allZero($pageContent));

            $headers = array();
            $headers = $this->addOptionalRangeHeader(
                $headers,
                $start,
                $end
            );
            $headers = $this->addOptionalAccessConditionHeader(
                $headers,
                $options->getAccessConditions()
            );
            $this->addOptionalHeader(
                $headers,
                Resources::X_MS_LEASE_ID,
                $options->getLeaseId()
            );
            $this->addOptionalHeader(
                $headers,
                Resources::X_MS_PAGE_WRITE,
                PageWriteOption::UPDATE_OPTION
            );

            if ($useTransactionalMD5) {
                $contentMD5 = base64_encode(md5($pageContent, true));
                $this->addOptionalHeader(
                    $headers,
                    Resources::CONTENT_MD5,
                    $contentMD5
                );
            }

            //return the array of requests.
            return $this->createRequest(
                $method,
                $headers,
                $queryParams,
                $postParams,
                $path,
                LocationMode::PRIMARY_ONLY,
                $pageContent
            );
        };

        //Send the request concurrently.
        //Does not need to evaluate the results. If operation is not successful,
        //exception will be thrown.
        return $this->sendConcurrentAsync(
            $generator,
            Resources::STATUS_CREATED,
            $options
        );
    }

    /**
     * Clears a range of pages from the blob.
     *
     * @param string                        $container name of the container
     * @param string                        $blob      name of the blob
     * @param Range                         $range     Can be up to the value of
     *                                                 the blob's full size.
     *                                                 Note that ranges must be
     *                                                 aligned to 512 (0-511,
     *                                                 512-1023)
     * @param Models\CreateBlobPagesOptions $options   optional parameters
     *
     * @return Models\CreateBlobPagesResult
     *
     * @see http://msdn.microsoft.com/en-us/library/windowsazure/ee691975.aspx
     */
    public function clearBlobPages(
        $container,
        $blob,
        Range $range,
        Models\CreateBlobPagesOptions $options = null
    ) {
        return $this->clearBlobPagesAsync(
            $container,
            $blob,
            $range,
            $options
        )->wait();
    }

    /**
     * Creates promise to clear a range of pages from the blob.
     *
     * @param string                        $container name of the container
     * @param string                        $blob      name of the blob
     * @param Range                         $range     Can be up to the value of
     *                                                 the blob's full size.
     *                                                 Note that ranges must be
     *                                                 aligned to 512 (0-511,
     *                                                 512-1023)
     * @param Models\CreateBlobPagesOptions $options   optional parameters
     *
     * @return \GuzzleHttp\Promise\PromiseInterface
     *
     * @see http://msdn.microsoft.com/en-us/library/windowsazure/ee691975.aspx
     */
    public function clearBlobPagesAsync(
        $container,
        $blob,
        Range $range,
        Models\CreateBlobPagesOptions $options = null
    ) {
        return $this->updatePageBlobPagesAsyncImpl(
            PageWriteOption::CLEAR_OPTION,
            $container,
            $blob,
            $range,
            Resources::EMPTY_STRING,
            $options
        );
    }

    /**
     * Creates a range of pages to a page blob.
     *
     * @param string                          $container name of the container
     * @param string                          $blob      name of the blob
     * @param Range                           $range     Can be up to 4 MB in
     *                                                   size. Note that ranges
     *                                                   must be aligned to 512
     *                                                   (0-511, 512-1023)
     * @param string|resource|StreamInterface $content   the blob contents.
     * @param Models\CreateBlobPagesOptions   $options   optional parameters
     *
     * @return Models\CreateBlobPagesResult
     *
     * @see http://msdn.microsoft.com/en-us/library/windowsazure/ee691975.aspx
     */
    public function createBlobPages(
        $container,
        $blob,
        Range $range,
        $content,
        Models\CreateBlobPagesOptions $options = null
    ) {
        return $this->createBlobPagesAsync(
            $container,
            $blob,
            $range,
            $content,
            $options
        )->wait();
    }

    /**
     * Creates promise to create a range of pages to a page blob.
     *
     * @param string                          $container name of the container
     * @param string                          $blob      name of the blob
     * @param Range                           $range     Can be up to 4 MB in
     *                                                   size. Note that ranges
     *                                                   must be aligned to 512
     *                                                   (0-511, 512-1023)
     * @param string|resource|StreamInterface $content   the blob contents.
     * @param Models\CreateBlobPagesOptions   $options   optional parameters
     *
     * @return \GuzzleHttp\Promise\PromiseInterface
     *
     * @see http://msdn.microsoft.com/en-us/library/windowsazure/ee691975.aspx
     */
    public function createBlobPagesAsync(
        $container,
        $blob,
        Range $range,
        $content,
        Models\CreateBlobPagesOptions $options = null
    ) {
        $contentStream = Psr7\stream_for($content);
        //because the content is at most 4MB long, can retrieve all the data
        //here at once.
        $body = $contentStream->getContents();

        //if the range is not align to 512, throw exception.
        $chunks = (int)($range->getLength() / 512);
        if ($chunks * 512 != $range->getLength()) {
            throw new \RuntimeException(Resources::ERROR_RANGE_NOT_ALIGN_TO_512);
        }

        return $this->updatePageBlobPagesAsyncImpl(
            PageWriteOption::UPDATE_OPTION,
            $container,
            $blob,
            $range,
            $body,
            $options
        );
    }

    /**
     * Creates a new block to be committed as part of a block blob.
     *
     * @param string                          $container name of the container
     * @param string                          $blob      name of the blob
     * @param string                          $blockId   must be less than or
     *                                                   equal to 64 bytes in
     *                                                   size. For a given blob,
     *                                                   the length of the value
     *                                                   specified for the
     *                                                   blockid parameter must
     *                                                   be the same size for
     *                                                   each block.
     * @param resource|string|StreamInterface $content   the blob block contents
     * @param Models\CreateBlobBlockOptions   $options   optional parameters
     *
     * @return Models\PutBlockResult
     *
     * @see http://msdn.microsoft.com/en-us/library/windowsazure/dd135726.aspx
     */
    public function createBlobBlock(
        $container,
        $blob,
        $blockId,
        $content,
        Models\CreateBlobBlockOptions $options = null
    ) {
        return $this->createBlobBlockAsync(
            $container,
            $blob,
            $blockId,
            $content,
            $options
        )->wait();
    }

    /**
     * Creates a new block to be committed as part of a block blob.
     *
     * @param string                          $container name of the container
     * @param string                          $blob      name of the blob
     * @param string                          $blockId   must be less than or
     *                                                   equal to 64 bytes in
     *                                                   size. For a given blob,
     *                                                   the length of the value
     *                                                   specified for the
     *                                                   blockid parameter must
     *                                                   be the same size for
     *                                                   each block.
     * @param resource|string|StreamInterface $content   the blob block contents
     * @param Models\CreateBlobBlockOptions   $options   optional parameters
     *
     * @return \GuzzleHttp\Promise\PromiseInterface
     *
     * @see http://msdn.microsoft.com/en-us/library/windowsazure/dd135726.aspx
     */
    public function createBlobBlockAsync(
        $container,
        $blob,
        $blockId,
        $content,
        Models\CreateBlobBlockOptions $options = null
    ) {
        Validate::canCastAsString($container, 'container');
        Validate::canCastAsString($blob, 'blob');
        Validate::notNullOrEmpty($blob, 'blob');
        Validate::canCastAsString($blockId, 'blockId');
        Validate::notNullOrEmpty($blockId, 'blockId');

        if (is_null($options)) {
            $options = new CreateBlobBlockOptions();
        }

        $method         = Resources::HTTP_PUT;
        $headers        = $this->createBlobBlockHeader($options);
        $postParams     = array();
        $queryParams    = $this->createBlobBlockQueryParams($options, $blockId);
        $path           = $this->createPath($container, $blob);
        $contentStream  = Psr7\stream_for($content);
        $body           = $contentStream->getContents();

        $options->setLocationMode(LocationMode::PRIMARY_ONLY);

        return $this->sendAsync(
            $method,
            $headers,
            $queryParams,
            $postParams,
            $path,
            Resources::STATUS_CREATED,
            $body,
            $options
        )->then(function ($response) {
            return PutBlockResult::create(
                HttpFormatter::formatHeaders($response->getHeaders())
            );
        });
    }

    /**
     * Commits a new block of data to the end of an existing append blob.
     *
     * @param string                          $container name of the container
     * @param string                          $blob      name of the blob
     * @param resource|string|StreamInterface $content   the blob block contents
     * @param Models\AppendBlockOptions       $options   optional parameters
     *
     * @return Models\AppendBlockResult
     *
     * @see https://docs.microsoft.com/en-us/rest/api/storageservices/fileservices/append-block
     */
    public function appendBlock(
        $container,
        $blob,
        $content,
        Models\AppendBlockOptions $options = null
    ) {
        return $this->appendBlockAsync(
            $container,
            $blob,
            $content,
            $options
        )->wait();
    }


    /**
     * Creates promise to commit a new block of data to the end of an existing append blob.
     *
     * @param string                          $container name of the container
     * @param string                          $blob      name of the blob
     * @param resource|string|StreamInterface $content   the blob block contents
     * @param Models\AppendBlockOptions       $options   optional parameters
     *
     * @return \GuzzleHttp\Promise\PromiseInterface
     *
     * @see https://docs.microsoft.com/en-us/rest/api/storageservices/fileservices/append-block
     */
    public function appendBlockAsync(
        $container,
        $blob,
        $content,
        Models\AppendBlockOptions $options = null
    ) {
        Validate::canCastAsString($container, 'container');
        Validate::notNullOrEmpty($container, 'container');
        Validate::canCastAsString($blob, 'blob');
        Validate::notNullOrEmpty($blob, 'blob');

        if (is_null($options)) {
            $options = new AppendBlockOptions();
        }

        $method         = Resources::HTTP_PUT;
        $headers        = array();
        $postParams     = array();
        $queryParams    = array();
        $path           = $this->createPath($container, $blob);

        $contentStream  = Psr7\stream_for($content);
        $length         = $contentStream->getSize();
        $body           = $contentStream->getContents();

        $this->addOptionalQueryParam(
            $queryParams,
            Resources::QP_COMP,
            'appendblock'
        );

        $headers  = $this->addOptionalAccessConditionHeader(
            $headers,
            $options->getAccessConditions()
        );

        $this->addOptionalHeader(
            $headers,
            Resources::CONTENT_LENGTH,
            $length
        );
        $this->addOptionalHeader(
            $headers,
            Resources::CONTENT_MD5,
            $options->getContentMD5()
        );
        $this->addOptionalHeader(
            $headers,
            Resources::X_MS_BLOB_CONDITION_MAXSIZE,
            $options->getMaxBlobSize()
        );
        $this->addOptionalHeader(
            $headers,
            Resources::X_MS_BLOB_CONDITION_APPENDPOS,
            $options->getAppendPosition()
        );
        $this->addOptionalHeader(
            $headers,
            Resources::X_MS_LEASE_ID,
            $options->getLeaseId()
        );

        $options->setLocationMode(LocationMode::PRIMARY_ONLY);

        return $this->sendAsync(
            $method,
            $headers,
            $queryParams,
            $postParams,
            $path,
            Resources::STATUS_CREATED,
            $body,
            $options
        )->then(function ($response) {
            return AppendBlockResult::create(
                HttpFormatter::formatHeaders($response->getHeaders())
            );
        });
    }

    /**
     * create the header for createBlobBlock(s)
     * @param  Models\CreateBlobBlockOptions $options the option of the request
     *
     * @return array
     */
    protected function createBlobBlockHeader(Models\CreateBlobBlockOptions $options = null)
    {
        $headers = array();
        $this->addOptionalHeader(
            $headers,
            Resources::X_MS_LEASE_ID,
            $options->getLeaseId()
        );
        $this->addOptionalHeader(
            $headers,
            Resources::CONTENT_MD5,
            $options->getContentMD5()
        );
        $this->addOptionalHeader(
            $headers,
            Resources::CONTENT_TYPE,
            Resources::URL_ENCODED_CONTENT_TYPE
        );

        return $headers;
    }

    /**
     * create the query params for createBlobBlock(s)
     * @param  Models\CreateBlobBlockOptions $options      the option of the
     *                                                     request
     * @param  string                        $blockId      the block id of the
     *                                                     block.
     * @param  bool                          $isConcurrent if the query
     *                                                     parameter is for
     *                                                     concurrent upload.
     *
     * @return array  the constructed query parameters.
     */
    protected function createBlobBlockQueryParams(
        Models\CreateBlobBlockOptions $options,
        $blockId,
        $isConcurrent = false
    ) {
        $queryParams = array();
        $this->addOptionalQueryParam(
            $queryParams,
            Resources::QP_COMP,
            'block'
        );
        $this->addOptionalQueryParam(
            $queryParams,
            Resources::QP_BLOCKID,
            $blockId
        );
        if ($isConcurrent) {
            $this->addOptionalQueryParam(
                $queryParams,
                Resources::QP_TIMEOUT,
                $options->getTimeout()
            );
        }

        return $queryParams;
    }

    /**
     * This method writes a blob by specifying the list of block IDs that make up the
     * blob. In order to be written as part of a blob, a block must have been
     * successfully written to the server in a prior createBlobBlock method.
     *
     * You can call Put Block List to update a blob by uploading only those blocks
     * that have changed, then committing the new and existing blocks together.
     * You can do this by specifying whether to commit a block from the committed
     * block list or from the uncommitted block list, or to commit the most recently
     * uploaded version of the block, whichever list it may belong to.
     *
     * @param string                         $container The container name.
     * @param string                         $blob      The blob name.
     * @param Models\BlockList|Block[]       $blockList The block entries.
     * @param Models\CommitBlobBlocksOptions $options   The optional parameters.
     *
     * @return Models\PutBlobResult
     *
     * @see http://msdn.microsoft.com/en-us/library/windowsazure/dd179467.aspx
     */
    public function commitBlobBlocks(
        $container,
        $blob,
        $blockList,
        Models\CommitBlobBlocksOptions $options = null
    ) {
        return $this->commitBlobBlocksAsync(
            $container,
            $blob,
            $blockList,
            $options
        )->wait();
    }

    /**
     * This method writes a blob by specifying the list of block IDs that make up the
     * blob. In order to be written as part of a blob, a block must have been
     * successfully written to the server in a prior createBlobBlock method.
     *
     * You can call Put Block List to update a blob by uploading only those blocks
     * that have changed, then committing the new and existing blocks together.
     * You can do this by specifying whether to commit a block from the committed
     * block list or from the uncommitted block list, or to commit the most recently
     * uploaded version of the block, whichever list it may belong to.
     *
     * @param string                         $container The container name.
     * @param string                         $blob      The blob name.
     * @param Models\BlockList|Block[]       $blockList The block entries.
     * @param Models\CommitBlobBlocksOptions $options   The optional parameters.
     *
     * @return \GuzzleHttp\Promise\PromiseInterface
     *
     * @see http://msdn.microsoft.com/en-us/library/windowsazure/dd179467.aspx
     */
    public function commitBlobBlocksAsync(
        $container,
        $blob,
        $blockList,
        Models\CommitBlobBlocksOptions $options = null
    ) {
        Validate::canCastAsString($container, 'container');
        Validate::canCastAsString($blob, 'blob');
        Validate::notNullOrEmpty($blob, 'blob');
        Validate::isTrue(
            $blockList instanceof BlockList || is_array($blockList),
            sprintf(
                Resources::INVALID_PARAM_MSG,
                'blockList',
                get_class(new BlockList())
            )
        );

        $method      = Resources::HTTP_PUT;
        $postParams  = array();
        $queryParams = array();
        $path        = $this->createPath($container, $blob);
        $isArray     = is_array($blockList);
        $blockList   = $isArray ? BlockList::create($blockList) : $blockList;
        $body        = $blockList->toXml($this->dataSerializer);

        if (is_null($options)) {
            $options = new CommitBlobBlocksOptions();
        }

        $blobContentType            = $options->getContentType();
        $blobContentEncoding        = $options->getContentEncoding();
        $blobContentLanguage        = $options->getContentLanguage();
        $blobContentMD5             = $options->getContentMD5();
        $blobCacheControl           = $options->getCacheControl();
        $blobCcontentDisposition    = $options->getContentDisposition();
        $leaseId                    = $options->getLeaseId();
        $contentType                = Resources::URL_ENCODED_CONTENT_TYPE;

        $metadata = $options->getMetadata();
        $headers  = $this->generateMetadataHeaders($metadata);
        $headers  = $this->addOptionalAccessConditionHeader(
            $headers,
            $options->getAccessConditions()
        );

        $this->addOptionalHeader(
            $headers,
            Resources::X_MS_LEASE_ID,
            $leaseId
        );
        $this->addOptionalHeader(
            $headers,
            Resources::X_MS_BLOB_CACHE_CONTROL,
            $blobCacheControl
        );
        $this->addOptionalHeader(
            $headers,
            Resources::X_MS_BLOB_CONTENT_DISPOSITION,
            $blobCcontentDisposition
        );
        $this->addOptionalHeader(
            $headers,
            Resources::X_MS_BLOB_CONTENT_TYPE,
            $blobContentType
        );
        $this->addOptionalHeader(
            $headers,
            Resources::X_MS_BLOB_CONTENT_ENCODING,
            $blobContentEncoding
        );
        $this->addOptionalHeader(
            $headers,
            Resources::X_MS_BLOB_CONTENT_LANGUAGE,
            $blobContentLanguage
        );
        $this->addOptionalHeader(
            $headers,
            Resources::X_MS_BLOB_CONTENT_MD5,
            $blobContentMD5
        );
        $this->addOptionalHeader(
            $headers,
            Resources::CONTENT_TYPE,
            $contentType
        );

        $this->addOptionalQueryParam(
            $queryParams,
            Resources::QP_COMP,
            'blocklist'
        );

        $options->setLocationMode(LocationMode::PRIMARY_ONLY);

        return $this->sendAsync(
            $method,
            $headers,
            $queryParams,
            $postParams,
            $path,
            Resources::STATUS_CREATED,
            $body,
            $options
        )->then(function ($response) {
            return PutBlobResult::create(
                HttpFormatter::formatHeaders($response->getHeaders())
            );
        }, null);
    }

    /**
     * Retrieves the list of blocks that have been uploaded as part of a block blob.
     *
     * There are two block lists maintained for a blob:
     * 1) Committed Block List: The list of blocks that have been successfully
     *    committed to a given blob with commitBlobBlocks.
     * 2) Uncommitted Block List: The list of blocks that have been uploaded for a
     *    blob using Put Block (REST API), but that have not yet been committed.
     *    These blocks are stored in Windows Azure in association with a blob, but do
     *    not yet form part of the blob.
     *
     * @param string                       $container name of the container
     * @param string                       $blob      name of the blob
     * @param Models\ListBlobBlocksOptions $options   optional parameters
     *
     * @return Models\ListBlobBlocksResult
     *
     * @see http://msdn.microsoft.com/en-us/library/windowsazure/dd179400.aspx
     */
    public function listBlobBlocks(
        $container,
        $blob,
        Models\ListBlobBlocksOptions $options = null
    ) {
        return $this->listBlobBlocksAsync($container, $blob, $options)->wait();
    }

    /**
     * Creates promise to retrieve the list of blocks that have been uploaded as
     * part of a block blob.
     *
     * There are two block lists maintained for a blob:
     * 1) Committed Block List: The list of blocks that have been successfully
     *    committed to a given blob with commitBlobBlocks.
     * 2) Uncommitted Block List: The list of blocks that have been uploaded for a
     *    blob using Put Block (REST API), but that have not yet been committed.
     *    These blocks are stored in Windows Azure in association with a blob, but do
     *    not yet form part of the blob.
     *
     * @param string                       $container name of the container
     * @param string                       $blob      name of the blob
     * @param Models\ListBlobBlocksOptions $options   optional parameters
     *
     * @return \GuzzleHttp\Promise\PromiseInterface
     *
     * @see http://msdn.microsoft.com/en-us/library/windowsazure/dd179400.aspx
     */
    public function listBlobBlocksAsync(
        $container,
        $blob,
        Models\ListBlobBlocksOptions $options = null
    ) {
        Validate::canCastAsString($container, 'container');
        Validate::canCastAsString($blob, 'blob');
        Validate::notNullOrEmpty($blob, 'blob');

        $method      = Resources::HTTP_GET;
        $headers     = array();
        $postParams  = array();
        $queryParams = array();
        $path        = $this->createPath($container, $blob);

        if (is_null($options)) {
            $options = new ListBlobBlocksOptions();
        }

        $this->addOptionalHeader(
            $headers,
            Resources::X_MS_LEASE_ID,
            $options->getLeaseId()
        );

        $this->addOptionalQueryParam(
            $queryParams,
            Resources::QP_BLOCK_LIST_TYPE,
            $options->getBlockListType()
        );
        $this->addOptionalQueryParam(
            $queryParams,
            Resources::QP_SNAPSHOT,
            $options->getSnapshot()
        );
        $this->addOptionalQueryParam(
            $queryParams,
            Resources::QP_COMP,
            'blocklist'
        );

        return $this->sendAsync(
            $method,
            $headers,
            $queryParams,
            $postParams,
            $path,
            Resources::STATUS_OK,
            Resources::EMPTY_STRING,
            $options
        )->then(function ($response) {
            $parsed = $this->dataSerializer->unserialize($response->getBody());

            return ListBlobBlocksResult::create(
                HttpFormatter::formatHeaders($response->getHeaders()),
                $parsed
            );
        }, null);
    }

    /**
     * Returns all properties and metadata on the blob.
     *
     * @param string                          $container name of the container
     * @param string                          $blob      name of the blob
     * @param Models\GetBlobPropertiesOptions $options   optional parameters
     *
     * @return Models\GetBlobPropertiesResult
     *
     * @see http://msdn.microsoft.com/en-us/library/windowsazure/dd179394.aspx
     */
    public function getBlobProperties(
        $container,
        $blob,
        Models\GetBlobPropertiesOptions $options = null
    ) {
        return $this->getBlobPropertiesAsync(
            $container,
            $blob,
            $options
        )->wait();
    }

    /**
     * Creates promise to return all properties and metadata on the blob.
     *
     * @param string                          $container name of the container
     * @param string                          $blob      name of the blob
     * @param Models\GetBlobPropertiesOptions $options   optional parameters
     *
     * @return \GuzzleHttp\Promise\PromiseInterface
     *
     * @see http://msdn.microsoft.com/en-us/library/windowsazure/dd179394.aspx
     */
    public function getBlobPropertiesAsync(
        $container,
        $blob,
        Models\GetBlobPropertiesOptions $options = null
    ) {
        Validate::canCastAsString($container, 'container');
        Validate::canCastAsString($blob, 'blob');
        Validate::notNullOrEmpty($blob, 'blob');

        $method      = Resources::HTTP_HEAD;
        $headers     = array();
        $postParams  = array();
        $queryParams = array();
        $path        = $this->createPath($container, $blob);

        if (is_null($options)) {
            $options = new GetBlobPropertiesOptions();
        }

        $headers = $this->addOptionalAccessConditionHeader(
            $headers,
            $options->getAccessConditions()
        );

        $this->addOptionalHeader(
            $headers,
            Resources::X_MS_LEASE_ID,
            $options->getLeaseId()
        );
        $this->addOptionalQueryParam(
            $queryParams,
            Resources::QP_SNAPSHOT,
            $options->getSnapshot()
        );

        return $this->sendAsync(
            $method,
            $headers,
            $queryParams,
            $postParams,
            $path,
            Resources::STATUS_OK,
            Resources::EMPTY_STRING,
            $options
        )->then(function ($response) {
            $formattedHeaders = HttpFormatter::formatHeaders($response->getHeaders());
            return GetBlobPropertiesResult::create($formattedHeaders);
        }, null);
    }

    /**
     * Returns all properties and metadata on the blob.
     *
     * @param string                        $container name of the container
     * @param string                        $blob      name of the blob
     * @param Models\GetBlobMetadataOptions $options   optional parameters
     *
     * @return Models\GetBlobMetadataResult
     *
     * @see http://msdn.microsoft.com/en-us/library/windowsazure/dd179350.aspx
     */
    public function getBlobMetadata(
        $container,
        $blob,
        Models\GetBlobMetadataOptions $options = null
    ) {
        return $this->getBlobMetadataAsync($container, $blob, $options)->wait();
    }

    /**
     * Creates promise to return all properties and metadata on the blob.
     *
     * @param string                        $container name of the container
     * @param string                        $blob      name of the blob
     * @param Models\GetBlobMetadataOptions $options   optional parameters
     *
     * @return \GuzzleHttp\Promise\PromiseInterface
     *
     * @see http://msdn.microsoft.com/en-us/library/windowsazure/dd179350.aspx
     */
    public function getBlobMetadataAsync(
        $container,
        $blob,
        Models\GetBlobMetadataOptions $options = null
    ) {
        Validate::canCastAsString($container, 'container');
        Validate::canCastAsString($blob, 'blob');
        Validate::notNullOrEmpty($blob, 'blob');

        $method      = Resources::HTTP_HEAD;
        $headers     = array();
        $postParams  = array();
        $queryParams = array();
        $path        = $this->createPath($container, $blob);

        if (is_null($options)) {
            $options = new GetBlobMetadataOptions();
        }

        $headers = $this->addOptionalAccessConditionHeader(
            $headers,
            $options->getAccessConditions()
        );

        $this->addOptionalHeader(
            $headers,
            Resources::X_MS_LEASE_ID,
            $options->getLeaseId()
        );
        $this->addOptionalQueryParam(
            $queryParams,
            Resources::QP_SNAPSHOT,
            $options->getSnapshot()
        );

        $this->addOptionalQueryParam(
            $queryParams,
            Resources::QP_COMP,
            'metadata'
        );

        return $this->sendAsync(
            $method,
            $headers,
            $queryParams,
            $postParams,
            $path,
            Resources::STATUS_OK,
            Resources::EMPTY_STRING,
            $options
        )->then(function ($response) {
            $responseHeaders = HttpFormatter::formatHeaders($response->getHeaders());
            return GetBlobMetadataResult::create($responseHeaders);
        });
    }

    /**
     * Returns a list of active page ranges for a page blob. Active page ranges are
     * those that have been populated with data.
     *
     * @param string                           $container name of the container
     * @param string                           $blob      name of the blob
     * @param Models\ListPageBlobRangesOptions $options   optional parameters
     *
     * @return Models\ListPageBlobRangesResult
     *
     * @see http://msdn.microsoft.com/en-us/library/windowsazure/ee691973.aspx
     */
    public function listPageBlobRanges(
        $container,
        $blob,
        Models\ListPageBlobRangesOptions $options = null
    ) {
        return $this->listPageBlobRangesAsync(
            $container,
            $blob,
            $options
        )->wait();
    }

    /**
     * Creates promise to return a list of active page ranges for a page blob.
     * Active page ranges are those that have been populated with data.
     *
     * @param string                           $container name of the container
     * @param string                           $blob      name of the blob
     * @param Models\ListPageBlobRangesOptions $options   optional parameters
     *
     * @return \GuzzleHttp\Promise\PromiseInterface
     *
     * @see http://msdn.microsoft.com/en-us/library/windowsazure/ee691973.aspx
     */
    public function listPageBlobRangesAsync(
        $container,
        $blob,
        Models\ListPageBlobRangesOptions $options = null
    ) {
        return $this->listPageBlobRangesAsyncImpl($container, $blob, null, $options);
    }

    /**
     * Returns a list of page ranges that have been updated or cleared.
     *
     * Returns a list of page ranges that have been updated or cleared since
     * the snapshot specified by `previousSnapshotTime`. Gets all of the page
     * ranges by default, or only the page ranges over a specific range of
     * bytes if `rangeStart` and `rangeEnd` in the `options` are specified.
     *
     * @param string                           $container             name of the container
     * @param string                           $blob                  name of the blob
     * @param string                           $previousSnapshotTime  previous snapshot time
     *                                                                for comparison which
     *                                                                should be prior to the
     *                                                                snapshot time defined
     *                                                                in `options`
     * @param Models\ListPageBlobRangesOptions $options               optional parameters
     *
     * @return Models\ListPageBlobRangesDiffResult
     *
     * @see https://docs.microsoft.com/en-us/rest/api/storageservices/version-2015-07-08
     */
    public function listPageBlobRangesDiff(
        $container,
        $blob,
        $previousSnapshotTime,
        Models\ListPageBlobRangesOptions $options = null
    ) {
        return $this->listPageBlobRangesDiffAsync(
            $container,
            $blob,
            $previousSnapshotTime,
            $options
        )->wait();
    }

    /**
     * Creates promise to return a list of page ranges that have been updated
     * or cleared.
     *
     * Creates promise to return a list of page ranges that have been updated
     * or cleared since the snapshot specified by `previousSnapshotTime`. Gets
     * all of the page ranges by default, or only the page ranges over a specific
     * range of bytes if `rangeStart` and `rangeEnd` in the `options` are specified.
     *
     * @param string                           $container             name of the container
     * @param string                           $blob                  name of the blob
     * @param string                           $previousSnapshotTime  previous snapshot time
     *                                                                for comparison which
     *                                                                should be prior to the
     *                                                                snapshot time defined
     *                                                                in `options`
     * @param Models\ListPageBlobRangesOptions $options               optional parameters
     *
     * @return \GuzzleHttp\Promise\PromiseInterface
     *
     * @see http://msdn.microsoft.com/en-us/library/windowsazure/ee691973.aspx
     */
    public function listPageBlobRangesDiffAsync(
        $container,
        $blob,
        $previousSnapshotTime,
        Models\ListPageBlobRangesOptions $options = null
    ) {
        return $this->listPageBlobRangesAsyncImpl(
            $container,
            $blob,
            $previousSnapshotTime,
            $options
        );
    }

    /**
     * Creates promise to return a list of page ranges.

     * If `previousSnapshotTime` is specified, the response will include
     * only the pages that differ between the target snapshot or blob and
     * the previous snapshot.
     *
     * @param string                           $container             name of the container
     * @param string                           $blob                  name of the blob
     * @param string                           $previousSnapshotTime  previous snapshot time
     *                                                                for comparison which
     *                                                                should be prior to the
     *                                                                snapshot time defined
     *                                                                in `options`
     * @param Models\ListPageBlobRangesOptions $options               optional parameters
     *
     * @return \GuzzleHttp\Promise\PromiseInterface
     *
     * @see http://msdn.microsoft.com/en-us/library/windowsazure/ee691973.aspx
     */
    private function listPageBlobRangesAsyncImpl(
        $container,
        $blob,
        $previousSnapshotTime = null,
        Models\ListPageBlobRangesOptions $options = null
    ) {
        Validate::canCastAsString($container, 'container');
        Validate::canCastAsString($blob, 'blob');
        Validate::notNullOrEmpty($blob, 'blob');

        $method      = Resources::HTTP_GET;
        $headers     = array();
        $queryParams = array();
        $postParams  = array();
        $path        = $this->createPath($container, $blob);

        if (is_null($options)) {
            $options = new ListPageBlobRangesOptions();
        }

        $headers = $this->addOptionalAccessConditionHeader(
            $headers,
            $options->getAccessConditions()
        );

        $range = $options->getRange();
        if ($range) {
            $headers = $this->addOptionalRangeHeader(
                $headers,
                $range->getStart(),
                $range->getEnd()
            );
        }

        $this->addOptionalHeader(
            $headers,
            Resources::X_MS_LEASE_ID,
            $options->getLeaseId()
        );
        $this->addOptionalQueryParam(
            $queryParams,
            Resources::QP_SNAPSHOT,
            $options->getSnapshot()
        );
        $this->addOptionalQueryParam(
            $queryParams,
            Resources::QP_COMP,
            'pagelist'
        );
        $this->addOptionalQueryParam(
            $queryParams,
            Resources::QP_PRE_SNAPSHOT,
            $previousSnapshotTime
        );

        $dataSerializer = $this->dataSerializer;

        return $this->sendAsync(
            $method,
            $headers,
            $queryParams,
            $postParams,
            $path,
            Resources::STATUS_OK,
            Resources::EMPTY_STRING,
            $options
        )->then(function ($response) use ($dataSerializer, $previousSnapshotTime) {
            $parsed = $dataSerializer->unserialize($response->getBody());
            if (is_null($previousSnapshotTime)) {
                return ListPageBlobRangesResult::create(
                    HttpFormatter::formatHeaders($response->getHeaders()),
                    $parsed
                );
            } else {
                return ListPageBlobRangesDiffResult::create(
                    HttpFormatter::formatHeaders($response->getHeaders()),
                    $parsed
                );
            }
        }, null);
    }

    /**
     * Sets system properties defined for a blob.
     *
     * @param string                          $container name of the container
     * @param string                          $blob      name of the blob
     * @param Models\SetBlobPropertiesOptions $options   optional parameters
     *
     * @return Models\SetBlobPropertiesResult
     *
     * @see http://msdn.microsoft.com/en-us/library/windowsazure/ee691966.aspx
     */
    public function setBlobProperties(
        $container,
        $blob,
        Models\SetBlobPropertiesOptions $options = null
    ) {
        return $this->setBlobPropertiesAsync(
            $container,
            $blob,
            $options
        )->wait();
    }

    /**
     * Creates promise to set system properties defined for a blob.
     *
     * @param string                          $container name of the container
     * @param string                          $blob      name of the blob
     * @param Models\SetBlobPropertiesOptions $options   optional parameters
     *
     * @return \GuzzleHttp\Promise\PromiseInterface
     *
     * @see http://msdn.microsoft.com/en-us/library/windowsazure/ee691966.aspx
     */
    public function setBlobPropertiesAsync(
        $container,
        $blob,
        Models\SetBlobPropertiesOptions $options = null
    ) {
        Validate::canCastAsString($container, 'container');
        Validate::canCastAsString($blob, 'blob');
        Validate::notNullOrEmpty($blob, 'blob');

        $method      = Resources::HTTP_PUT;
        $headers     = array();
        $postParams  = array();
        $queryParams = array();
        $path        = $this->createPath($container, $blob);

        if (is_null($options)) {
            $options = new SetBlobPropertiesOptions();
        }

        $blobContentType            = $options->getContentType();
        $blobContentEncoding        = $options->getContentEncoding();
        $blobContentLanguage        = $options->getContentLanguage();
        $blobContentLength          = $options->getContentLength();
        $blobContentMD5             = $options->getContentMD5();
        $blobCacheControl           = $options->getCacheControl();
        $blobContentDisposition    = $options->getContentDisposition();
        $leaseId             = $options->getLeaseId();
        $sNumberAction       = $options->getSequenceNumberAction();
        $sNumber             = $options->getSequenceNumber();

        $headers = $this->addOptionalAccessConditionHeader(
            $headers,
            $options->getAccessConditions()
        );

        $this->addOptionalHeader(
            $headers,
            Resources::X_MS_LEASE_ID,
            $leaseId
        );
        $this->addOptionalHeader(
            $headers,
            Resources::X_MS_BLOB_CACHE_CONTROL,
            $blobCacheControl
        );
        $this->addOptionalHeader(
            $headers,
            Resources::X_MS_BLOB_CONTENT_DISPOSITION,
            $blobContentDisposition
        );
        $this->addOptionalHeader(
            $headers,
            Resources::X_MS_BLOB_CONTENT_TYPE,
            $blobContentType
        );
        $this->addOptionalHeader(
            $headers,
            Resources::X_MS_BLOB_CONTENT_ENCODING,
            $blobContentEncoding
        );
        $this->addOptionalHeader(
            $headers,
            Resources::X_MS_BLOB_CONTENT_LANGUAGE,
            $blobContentLanguage
        );
        $this->addOptionalHeader(
            $headers,
            Resources::X_MS_BLOB_CONTENT_LENGTH,
            $blobContentLength
        );
        $this->addOptionalHeader(
            $headers,
            Resources::X_MS_BLOB_CONTENT_MD5,
            $blobContentMD5
        );
        $this->addOptionalHeader(
            $headers,
            Resources::X_MS_BLOB_SEQUENCE_NUMBER_ACTION,
            $sNumberAction
        );
        $this->addOptionalHeader(
            $headers,
            Resources::X_MS_BLOB_SEQUENCE_NUMBER,
            $sNumber
        );

        $this->addOptionalQueryParam($queryParams, Resources::QP_COMP, 'properties');

        $options->setLocationMode(LocationMode::PRIMARY_ONLY);

        return $this->sendAsync(
            $method,
            $headers,
            $queryParams,
            $postParams,
            $path,
            Resources::STATUS_OK,
            Resources::EMPTY_STRING,
            $options
        )->then(function ($response) {
            return SetBlobPropertiesResult::create(
                HttpFormatter::formatHeaders($response->getHeaders())
            );
        }, null);
    }

    /**
     * Sets metadata headers on the blob.
     *
     * @param string                        $container name of the container
     * @param string                        $blob      name of the blob
     * @param array                         $metadata  key/value pair representation
     * @param Models\BlobServiceOptions     $options   optional parameters
     *
     * @return Models\SetBlobMetadataResult
     *
     * @see http://msdn.microsoft.com/en-us/library/windowsazure/dd179414.aspx
     */
    public function setBlobMetadata(
        $container,
        $blob,
        array $metadata,
        Models\BlobServiceOptions $options = null
    ) {
        return $this->setBlobMetadataAsync(
            $container,
            $blob,
            $metadata,
            $options
        )->wait();
    }

    /**
     * Creates promise to set metadata headers on the blob.
     *
     * @param string                        $container name of the container
     * @param string                        $blob      name of the blob
     * @param array                         $metadata  key/value pair representation
     * @param Models\BlobServiceOptions     $options   optional parameters
     *
     * @return \GuzzleHttp\Promise\PromiseInterface
     *
     * @see http://msdn.microsoft.com/en-us/library/windowsazure/dd179414.aspx
     */
    public function setBlobMetadataAsync(
        $container,
        $blob,
        array $metadata,
        Models\BlobServiceOptions $options = null
    ) {
        Validate::canCastAsString($container, 'container');
        Validate::canCastAsString($blob, 'blob');
        Validate::notNullOrEmpty($blob, 'blob');
        Utilities::validateMetadata($metadata);

        $method      = Resources::HTTP_PUT;
        $headers     = array();
        $postParams  = array();
        $queryParams = array();
        $path        = $this->createPath($container, $blob);

        if (is_null($options)) {
            $options = new BlobServiceOptions();
        }

        $headers = $this->addOptionalAccessConditionHeader(
            $headers,
            $options->getAccessConditions()
        );
        $headers = $this->addMetadataHeaders($headers, $metadata);

        $this->addOptionalHeader(
            $headers,
            Resources::X_MS_LEASE_ID,
            $options->getLeaseId()
        );
        $this->addOptionalQueryParam(
            $queryParams,
            Resources::QP_COMP,
            'metadata'
        );

        $options->setLocationMode(LocationMode::PRIMARY_ONLY);

        return $this->sendAsync(
            $method,
            $headers,
            $queryParams,
            $postParams,
            $path,
            Resources::STATUS_OK,
            Resources::EMPTY_STRING,
            $options
        )->then(function ($response) {
            return SetBlobMetadataResult::create(
                HttpFormatter::formatHeaders($response->getHeaders())
            );
        }, null);
    }

    /**
     * Downloads a blob to a file, the result contains its metadata and
     * properties. The result will not contain a stream pointing to the
     * content of the file.
     *
     * @param string                $path      The path and name of the file
     * @param string                $container name of the container
     * @param string                $blob      name of the blob
     * @param Models\GetBlobOptions $options   optional parameters
     *
     * @return Models\GetBlobResult
     *
     * @see http://msdn.microsoft.com/en-us/library/windowsazure/dd179440.aspx
     */
    public function saveBlobToFile(
        $path,
        $container,
        $blob,
        Models\GetBlobOptions $options = null
    ) {
        return $this->saveBlobToFileAsync(
            $path,
            $container,
            $blob,
            $options
        )->wait();
    }

    /**
     * Creates promise to download a blob to a file, the result contains its
     * metadata and properties. The result will not contain a stream pointing
     * to the content of the file.
     *
     * @param string                $path      The path and name of the file
     * @param string                $container name of the container
     * @param string                $blob      name of the blob
     * @param Models\GetBlobOptions $options   optional parameters
     *
     * @return \GuzzleHttp\Promise\PromiseInterface
     * @throws \Exception
     * @see http://msdn.microsoft.com/en-us/library/windowsazure/dd179440.aspx
     */
    public function saveBlobToFileAsync(
        $path,
        $container,
        $blob,
        Models\GetBlobOptions $options = null
    ) {
        $resource = fopen($path, 'w+');
        if ($resource == null) {
            throw new \Exception(Resources::ERROR_FILE_COULD_NOT_BE_OPENED);
        }
        return $this->getBlobAsync($container, $blob, $options)->then(
            function ($result) use ($path, $resource) {
                $content = $result->getContentStream();
                while (!feof($content)) {
                    fwrite(
                        $resource,
                        stream_get_contents($content, Resources::MB_IN_BYTES_4)
                    );
                }

                $content = null;
                fclose($resource);

                return $result;
            },
            null
        );
    }

    /**
     * Reads or downloads a blob from the system, including its metadata and
     * properties.
     *
     * @param string                $container name of the container
     * @param string                $blob      name of the blob
     * @param Models\GetBlobOptions $options   optional parameters
     *
     * @return Models\GetBlobResult
     *
     * @see http://msdn.microsoft.com/en-us/library/windowsazure/dd179440.aspx
     */
    public function getBlob(
        $container,
        $blob,
        Models\GetBlobOptions $options = null
    ) {
        return $this->getBlobAsync($container, $blob, $options)->wait();
    }

    /**
     * Creates promise to read or download a blob from the system, including its
     * metadata and properties.
     *
     * @param string                $container name of the container
     * @param string                $blob      name of the blob
     * @param Models\GetBlobOptions $options   optional parameters
     *
     * @return \GuzzleHttp\Promise\PromiseInterface
     *
     * @see http://msdn.microsoft.com/en-us/library/windowsazure/dd179440.aspx
     */
    public function getBlobAsync(
        $container,
        $blob,
        Models\GetBlobOptions $options = null
    ) {
        Validate::canCastAsString($container, 'container');
        Validate::canCastAsString($blob, 'blob');

        $method      = Resources::HTTP_GET;
        $headers     = array();
        $postParams  = array();
        $queryParams = array();
        $path        = $this->createPath($container, $blob);

        if (is_null($options)) {
            $options = new GetBlobOptions();
        }

        $getMD5  = $options->getRangeGetContentMD5();
        $headers = $this->addOptionalAccessConditionHeader(
            $headers,
            $options->getAccessConditions()
        );

        $range = $options->getRange();
        if ($range) {
            $headers = $this->addOptionalRangeHeader(
                $headers,
                $range->getStart(),
                $range->getEnd()
            );
        }

        $this->addOptionalHeader(
            $headers,
            Resources::X_MS_LEASE_ID,
            $options->getLeaseId()
        );
        $this->addOptionalHeader(
            $headers,
            Resources::X_MS_RANGE_GET_CONTENT_MD5,
            $getMD5 ? 'true' : null
        );
        $this->addOptionalQueryParam(
            $queryParams,
            Resources::QP_SNAPSHOT,
            $options->getSnapshot()
        );

        $options->setIsStreaming(true);

        return $this->sendAsync(
            $method,
            $headers,
            $queryParams,
            $postParams,
            $path,
            array(Resources::STATUS_OK, Resources::STATUS_PARTIAL_CONTENT),
            Resources::EMPTY_STRING,
            $options
        )->then(function ($response) {
            $metadata = Utilities::getMetadataArray(
                HttpFormatter::formatHeaders($response->getHeaders())
            );

            return GetBlobResult::create(
                HttpFormatter::formatHeaders($response->getHeaders()),
                $response->getBody(),
                $metadata
            );
        });
    }
    
    /**
     * Undeletes a blob.
     *
     * @param string                      $container name of the container
     * @param string                      $blob      name of the blob
     * @param Models\UndeleteBlobOptions  $options   optional parameters
     *
     * @return void
     *
     * @see https://docs.microsoft.com/en-us/rest/api/storageservices/undelete-blob
     */
    public function undeleteBlob(
        $container,
        $blob,
        Models\UndeleteBlobOptions $options = null
    ) {
        $this->undeleteBlobAsync($container, $blob, $options)->wait();
    }
    
    /**
     * Undeletes a blob.
     *
     * @param string                      $container name of the container
     * @param string                      $blob      name of the blob
     * @param Models\UndeleteBlobOptions  $options   optional parameters
     *
     * @return \GuzzleHttp\Promise\PromiseInterface
     *
     * @see https://docs.microsoft.com/en-us/rest/api/storageservices/undelete-blob
     */
    public function undeleteBlobAsync(
        $container,
        $blob,
        Models\UndeleteBlobOptions $options = null
    ) {
        Validate::canCastAsString($container, 'container');
        Validate::canCastAsString($blob, 'blob');
        Validate::notNullOrEmpty($blob, 'blob');

        $method      = Resources::HTTP_PUT;
        $headers     = array();
        $postParams  = array();
        $queryParams = array();
        $path        = $this->createPath($container, $blob);

        if (is_null($options)) {
            $options = new UndeleteBlobOptions();
        }

        $leaseId = $options->getLeaseId();

        $headers = $this->addOptionalAccessConditionHeader(
            $headers,
            $options->getAccessConditions()
        );

        $this->addOptionalHeader(
            $headers,
            Resources::X_MS_LEASE_ID,
            $leaseId
        );

        $this->addOptionalQueryParam($queryParams, Resources::QP_COMP, 'undelete');

        $options->setLocationMode(LocationMode::PRIMARY_ONLY);

        return $this->sendAsync(
            $method,
            $headers,
            $queryParams,
            $postParams,
            $path,
            Resources::STATUS_OK,
            Resources::EMPTY_STRING,
            $options
        );
    }

    /**
     * Deletes a blob or blob snapshot.
     *
     * Note that if the snapshot entry is specified in the $options then only this
     * blob snapshot is deleted. To delete all blob snapshots, do not set Snapshot
     * and just set getDeleteSnaphotsOnly to true.
     *
     * @param string                   $container name of the container
     * @param string                   $blob      name of the blob
     * @param Models\DeleteBlobOptions $options   optional parameters
     *
     * @return void
     *
     * @see http://msdn.microsoft.com/en-us/library/windowsazure/dd179413.aspx
     */
    public function deleteBlob(
        $container,
        $blob,
        Models\DeleteBlobOptions $options = null
    ) {
        $this->deleteBlobAsync($container, $blob, $options)->wait();
    }

    /**
     * Creates promise to delete a blob or blob snapshot.
     *
     * Note that if the snapshot entry is specified in the $options then only this
     * blob snapshot is deleted. To delete all blob snapshots, do not set Snapshot
     * and just set getDeleteSnaphotsOnly to true.
     *
     * @param string                   $container name of the container
     * @param string                   $blob      name of the blob
     * @param Models\DeleteBlobOptions $options   optional parameters
     *
     * @return \GuzzleHttp\Promise\PromiseInterface
     *
     * @see http://msdn.microsoft.com/en-us/library/windowsazure/dd179413.aspx
     */
    public function deleteBlobAsync(
        $container,
        $blob,
        Models\DeleteBlobOptions $options = null
    ) {
        Validate::canCastAsString($container, 'container');
        Validate::canCastAsString($blob, 'blob');
        Validate::notNullOrEmpty($blob, 'blob');

        $method      = Resources::HTTP_DELETE;
        $headers     = array();
        $postParams  = array();
        $queryParams = array();
        $path        = $this->createPath($container, $blob);

        if (is_null($options)) {
            $options = new DeleteBlobOptions();
        }

        if (is_null($options->getSnapshot())) {
            $delSnapshots = $options->getDeleteSnaphotsOnly() ? 'only' : 'include';
            $this->addOptionalHeader(
                $headers,
                Resources::X_MS_DELETE_SNAPSHOTS,
                $delSnapshots
            );
        } else {
            $this->addOptionalQueryParam(
                $queryParams,
                Resources::QP_SNAPSHOT,
                $options->getSnapshot()
            );
        }

        $headers = $this->addOptionalAccessConditionHeader(
            $headers,
            $options->getAccessConditions()
        );

        $this->addOptionalHeader(
            $headers,
            Resources::X_MS_LEASE_ID,
            $options->getLeaseId()
        );

        $options->setLocationMode(LocationMode::PRIMARY_ONLY);

        return $this->sendAsync(
            $method,
            $headers,
            $queryParams,
            $postParams,
            $path,
            Resources::STATUS_ACCEPTED,
            Resources::EMPTY_STRING,
            $options
        );
    }

    /**
     * Creates a snapshot of a blob.
     *
     * @param string                           $container The name of the container.
     * @param string                           $blob      The name of the blob.
     * @param Models\CreateBlobSnapshotOptions $options   The optional parameters.
     *
     * @return Models\CreateBlobSnapshotResult
     *
     * @see http://msdn.microsoft.com/en-us/library/windowsazure/ee691971.aspx
     */
    public function createBlobSnapshot(
        $container,
        $blob,
        Models\CreateBlobSnapshotOptions $options = null
    ) {
        return $this->createBlobSnapshotAsync(
            $container,
            $blob,
            $options
        )->wait();
    }

    /**
     * Creates promise to create a snapshot of a blob.
     *
     * @param string                           $container The name of the container.
     * @param string                           $blob      The name of the blob.
     * @param Models\CreateBlobSnapshotOptions $options   The optional parameters.
     *
     * @return \GuzzleHttp\Promise\PromiseInterface
     *
     * @see http://msdn.microsoft.com/en-us/library/windowsazure/ee691971.aspx
     */
    public function createBlobSnapshotAsync(
        $container,
        $blob,
        Models\CreateBlobSnapshotOptions $options = null
    ) {
        Validate::canCastAsString($container, 'container');
        Validate::canCastAsString($blob, 'blob');
        Validate::notNullOrEmpty($blob, 'blob');

        $method             = Resources::HTTP_PUT;
        $headers            = array();
        $postParams         = array();
        $queryParams        = array();
        $path               = $this->createPath($container, $blob);

        if (is_null($options)) {
            $options = new CreateBlobSnapshotOptions();
        }

        $queryParams[Resources::QP_COMP] = 'snapshot';

        $headers = $this->addOptionalAccessConditionHeader(
            $headers,
            $options->getAccessConditions()
        );
        $headers = $this->addMetadataHeaders($headers, $options->getMetadata());
        $this->addOptionalHeader(
            $headers,
            Resources::X_MS_LEASE_ID,
            $options->getLeaseId()
        );

        $options->setLocationMode(LocationMode::PRIMARY_ONLY);

        return $this->sendAsync(
            $method,
            $headers,
            $queryParams,
            $postParams,
            $path,
            Resources::STATUS_CREATED,
            Resources::EMPTY_STRING,
            $options
        )->then(function ($response) {
            return CreateBlobSnapshotResult::create(
                HttpFormatter::formatHeaders($response->getHeaders())
            );
        }, null);
    }

    /**
     * Copies a source blob to a destination blob within the same storage account.
     *
     * @param string                 $destinationContainer name of the destination
     * container
     * @param string                 $destinationBlob      name of the destination
     * blob
     * @param string                 $sourceContainer      name of the source
     * container
     * @param string                 $sourceBlob           name of the source
     * blob
     * @param Models\CopyBlobOptions $options              optional parameters
     *
     * @return Models\CopyBlobResult
     *
     * @see http://msdn.microsoft.com/en-us/library/windowsazure/dd894037.aspx
     */
    public function copyBlob(
        $destinationContainer,
        $destinationBlob,
        $sourceContainer,
        $sourceBlob,
        Models\CopyBlobOptions $options = null
    ) {
        return $this->copyBlobAsync(
            $destinationContainer,
            $destinationBlob,
            $sourceContainer,
            $sourceBlob,
            $options
        )->wait();
    }

    /**
     * Creates promise to copy a source blob to a destination blob within the
     * same storage account.
     *
     * @param string                 $destinationContainer name of the destination
     * container
     * @param string                 $destinationBlob      name of the destination
     * blob
     * @param string                 $sourceContainer      name of the source
     * container
     * @param string                 $sourceBlob           name of the source
     * blob
     * @param Models\CopyBlobOptions $options              optional parameters
     *
     * @return \GuzzleHttp\Promise\PromiseInterface
     *
     * @see http://msdn.microsoft.com/en-us/library/windowsazure/dd894037.aspx
     */
    public function copyBlobAsync(
        $destinationContainer,
        $destinationBlob,
        $sourceContainer,
        $sourceBlob,
        Models\CopyBlobOptions $options = null
    ) {
        if (is_null($options)) {
            $options = new CopyBlobOptions();
        }

        $sourceBlobPath = $this->getCopyBlobSourceName(
            $sourceContainer,
            $sourceBlob,
            $options
        );

        return $this->copyBlobFromURLAsync(
            $destinationContainer,
            $destinationBlob,
            $sourceBlobPath,
            $options
        );
    }

    /**
     * Copies from a source URL to a destination blob.
     *
     * @param string                        $destinationContainer name of the
     *                                                            destination
     *                                                            container
     * @param string                        $destinationBlob      name of the
     *                                                            destination
     *                                                            blob
     * @param string                        $sourceURL            URL of the
     *                                                            source
     *                                                            resource
     * @param Models\CopyBlobFromURLOptions $options              optional
     *                                                            parameters
     *
     * @return Models\CopyBlobResult
     *
     * @see http://msdn.microsoft.com/en-us/library/windowsazure/dd894037.aspx
     */
    public function copyBlobFromURL(
        $destinationContainer,
        $destinationBlob,
        $sourceURL,
        Models\CopyBlobFromURLOptions $options = null
    ) {
        return $this->copyBlobFromURLAsync(
            $destinationContainer,
            $destinationBlob,
            $sourceURL,
            $options
        )->wait();
    }

    /**
     * Creates promise to copy from source URL to a destination blob.
     *
     * @param string                        $destinationContainer name of the
     *                                                            destination
     *                                                            container
     * @param string                        $destinationBlob      name of the
     *                                                            destination
     *                                                            blob
     * @param string                        $sourceURL            URL of the
     *                                                            source
     *                                                            resource
     * @param Models\CopyBlobFromURLOptions $options              optional
     *                                                            parameters
     *
     * @return \GuzzleHttp\Promise\PromiseInterface
     *
     * @see http://msdn.microsoft.com/en-us/library/windowsazure/dd894037.aspx
     */
    public function copyBlobFromURLAsync(
        $destinationContainer,
        $destinationBlob,
        $sourceURL,
        Models\CopyBlobFromURLOptions $options = null
    ) {
        $method              = Resources::HTTP_PUT;
        $headers             = array();
        $postParams          = array();
        $queryParams         = array();
        $destinationBlobPath = $this->createPath(
            $destinationContainer,
            $destinationBlob
        );

        if (is_null($options)) {
            $options = new CopyBlobFromURLOptions();
        }

        if ($options->getIsIncrementalCopy()) {
            $this->addOptionalQueryParam(
                $queryParams,
                Resources::QP_COMP,
                'incrementalcopy'
            );
        }

        $headers = $this->addOptionalAccessConditionHeader(
            $headers,
            $options->getAccessConditions()
        );

        $headers = $this->addOptionalSourceAccessConditionHeader(
            $headers,
            $options->getSourceAccessConditions()
        );

        $this->addOptionalHeader(
            $headers,
            Resources::X_MS_COPY_SOURCE,
            $sourceURL
        );

        $headers = $this->addMetadataHeaders($headers, $options->getMetadata());

        $this->addOptionalHeader(
            $headers,
            Resources::X_MS_LEASE_ID,
            $options->getLeaseId()
        );

        $this->addOptionalHeader(
            $headers,
            Resources::X_MS_SOURCE_LEASE_ID,
            $options->getSourceLeaseId()
        );

        $this->addOptionalHeader(
            $headers,
            Resources::X_MS_ACCESS_TIER,
            $options->getAccessTier()
        );

        $options->setLocationMode(LocationMode::PRIMARY_ONLY);

        return $this->sendAsync(
            $method,
            $headers,
            $queryParams,
            $postParams,
            $destinationBlobPath,
            Resources::STATUS_ACCEPTED,
            Resources::EMPTY_STRING,
            $options
        )->then(function ($response) {
            return CopyBlobResult::create(
                HttpFormatter::formatHeaders($response->getHeaders())
            );
        }, null);
    }

    /**
     * Abort a blob copy operation
     *
     * @param string                        $container            name of the container
     * @param string                        $blob                 name of the blob
     * @param string                        $copyId               copy operation identifier.
     * @param Models\BlobServiceOptions     $options              optional parameters
     *
     * @return void
     *
     * @see https://docs.microsoft.com/en-us/rest/api/storageservices/fileservices/abort-copy-blob
     */
    public function abortCopy(
        $container,
        $blob,
        $copyId,
        Models\BlobServiceOptions $options = null
    ) {
        return $this->abortCopyAsync(
            $container,
            $blob,
            $copyId,
            $options
        )->wait();
    }

    /**
     * Creates promise to abort a blob copy operation
     *
     * @param string                        $container            name of the container
     * @param string                        $blob                 name of the blob
     * @param string                        $copyId               copy operation identifier.
     * @param Models\BlobServiceOptions     $options              optional parameters
     *
     * @return \GuzzleHttp\Promise\PromiseInterface
     *
     * @see https://docs.microsoft.com/en-us/rest/api/storageservices/fileservices/abort-copy-blob
     */
    public function abortCopyAsync(
        $container,
        $blob,
        $copyId,
        Models\BlobServiceOptions $options = null
    ) {
        Validate::canCastAsString($container, 'container');
        Validate::canCastAsString($blob, 'blob');
        Validate::canCastAsString($copyId, 'copyId');
        Validate::notNullOrEmpty($container, 'container');
        Validate::notNullOrEmpty($blob, 'blob');
        Validate::notNullOrEmpty($copyId, 'copyId');

        $method              = Resources::HTTP_PUT;
        $headers             = array();
        $postParams          = array();
        $queryParams         = array();
        $destinationBlobPath = $this->createPath(
            $container,
            $blob
        );

        if (is_null($options)) {
            $options = new BlobServiceOptions();
        }

        $this->addOptionalQueryParam(
            $queryParams,
            Resources::QP_TIMEOUT,
            $options->getTimeout()
        );

        $this->addOptionalQueryParam(
            $queryParams,
            Resources::QP_COMP,
            'copy'
        );

        $this->addOptionalQueryParam(
            $queryParams,
            Resources::QP_COPY_ID,
            $copyId
        );

        $this->addOptionalHeader(
            $headers,
            Resources::X_MS_LEASE_ID,
            $options->getLeaseId()
        );

        $this->addOptionalHeader(
            $headers,
            Resources::X_MS_COPY_ACTION,
            'abort'
        );

        return $this->sendAsync(
            $method,
            $headers,
            $queryParams,
            $postParams,
            $destinationBlobPath,
            Resources::STATUS_NO_CONTENT,
            Resources::EMPTY_STRING,
            $options
        );
    }

    /**
     * Establishes an exclusive write lock on a blob. To write to a locked
     * blob, a client must provide a lease ID.
     *
     * @param string                     $container         name of the container
     * @param string                     $blob              name of the blob
     * @param string                     $proposedLeaseId   lease id when acquiring
     * @param int                        $leaseDuration     the lease duration.
     *                                                      A non-infinite
     *                                                      lease can be between
     *                                                      15 and 60 seconds.
     *                                                      Default is never
     *                                                      to expire.
     * @param Models\BlobServiceOptions  $options           optional parameters
     *
     * @return Models\LeaseResult
     *
     * @see http://msdn.microsoft.com/en-us/library/windowsazure/ee691972.aspx
     */
    public function acquireLease(
        $container,
        $blob,
        $proposedLeaseId = null,
        $leaseDuration = null,
        Models\BlobServiceOptions $options = null
    ) {
        return $this->acquireLeaseAsync(
            $container,
            $blob,
            $proposedLeaseId,
            $leaseDuration,
            $options
        )->wait();
    }

    /**
     * Creates promise to establish an exclusive one-minute write lock on a blob.
     * To write to a locked blob, a client must provide a lease ID.
     *
     * @param string                     $container         name of the container
     * @param string                     $blob              name of the blob
     * @param string                     $proposedLeaseId   lease id when acquiring
     * @param int                        $leaseDuration     the lease duration.
     *                                                      A non-infinite
     *                                                      lease can be between
     *                                                      15 and 60 seconds.
     *                                                      Default is never to
     *                                                      expire.
     * @param Models\BlobServiceOptions  $options           optional parameters
     *
     * @return \GuzzleHttp\Promise\PromiseInterface
     *
     * @see http://msdn.microsoft.com/en-us/library/windowsazure/ee691972.aspx
     */
    public function acquireLeaseAsync(
        $container,
        $blob,
        $proposedLeaseId = null,
        $leaseDuration = null,
        Models\BlobServiceOptions $options = null
    ) {
        if ($options === null) {
            $options = new BlobServiceOptions();
        }

        if ($leaseDuration === null) {
            $leaseDuration = -1;
        }

        return $this->putLeaseAsyncImpl(
            LeaseMode::ACQUIRE_ACTION,
            $container,
            $blob,
            $proposedLeaseId,
            $leaseDuration,
            null /* leaseId */,
            null /* breakPeriod */,
            self::getStatusCodeOfLeaseAction(LeaseMode::ACQUIRE_ACTION),
            $options,
            $options->getAccessConditions()
        )->then(function ($response) {
            return LeaseResult::create(
                HttpFormatter::formatHeaders($response->getHeaders())
            );
        }, null);
    }

    /**
     * change an existing lease
     *
     * @param string                    $container         name of the container
     * @param string                    $blob              name of the blob
     * @param string                    $leaseId           lease id when acquiring
     * @param string                    $proposedLeaseId   lease id when acquiring
     * @param Models\BlobServiceOptions $options           optional parameters
     *
     * @return Models\LeaseResult
     *
     * @see https://docs.microsoft.com/en-us/rest/api/storageservices/fileservices/lease-blob
     */
    public function changeLease(
        $container,
        $blob,
        $leaseId,
        $proposedLeaseId,
        Models\BlobServiceOptions $options = null
    ) {
        return $this->changeLeaseAsync(
            $container,
            $blob,
            $leaseId,
            $proposedLeaseId,
            $options
        )->wait();
    }

    /**
     * Creates promise to change an existing lease
     *
     * @param string                    $container         name of the container
     * @param string                    $blob              name of the blob
     * @param string                    $leaseId           lease id when acquiring
     * @param string                    $proposedLeaseId   the proposed lease id
     * @param Models\BlobServiceOptions $options           optional parameters
     *
     * @return \GuzzleHttp\Promise\PromiseInterface
     *
     * @see https://docs.microsoft.com/en-us/rest/api/storageservices/fileservices/lease-blob
     */
    public function changeLeaseAsync(
        $container,
        $blob,
        $leaseId,
        $proposedLeaseId,
        Models\BlobServiceOptions $options = null
    ) {
        return $this->putLeaseAsyncImpl(
            LeaseMode::CHANGE_ACTION,
            $container,
            $blob,
            $proposedLeaseId,
            null /* leaseDuration */,
            $leaseId,
            null /* breakPeriod */,
            self::getStatusCodeOfLeaseAction(LeaseMode::RENEW_ACTION),
            is_null($options) ? new BlobServiceOptions() : $options
        )->then(function ($response) {
            return LeaseResult::create(
                HttpFormatter::formatHeaders($response->getHeaders())
            );
        }, null);
    }

    /**
     * Renews an existing lease
     *
     * @param string                    $container name of the container
     * @param string                    $blob      name of the blob
     * @param string                    $leaseId   lease id when acquiring
     * @param Models\BlobServiceOptions $options   optional parameters
     *
     * @return Models\LeaseResult
     *
     * @see https://docs.microsoft.com/en-us/rest/api/storageservices/fileservices/lease-blob
     */
    public function renewLease(
        $container,
        $blob,
        $leaseId,
        Models\BlobServiceOptions $options = null
    ) {
        return $this->renewLeaseAsync(
            $container,
            $blob,
            $leaseId,
            $options
        )->wait();
    }

    /**
     * Creates promise to renew an existing lease
     *
     * @param string                    $container name of the container
     * @param string                    $blob      name of the blob
     * @param string                    $leaseId   lease id when acquiring
     * @param Models\BlobServiceOptions $options   optional parameters
     *
     * @return \GuzzleHttp\Promise\PromiseInterface
     *
     * @see https://docs.microsoft.com/en-us/rest/api/storageservices/fileservices/lease-blob
     */
    public function renewLeaseAsync(
        $container,
        $blob,
        $leaseId,
        Models\BlobServiceOptions $options = null
    ) {
        return $this->putLeaseAsyncImpl(
            LeaseMode::RENEW_ACTION,
            $container,
            $blob,
            null /* proposedLeaseId */,
            null /* leaseDuration */,
            $leaseId,
            null /* breakPeriod */,
            self::getStatusCodeOfLeaseAction(LeaseMode::RENEW_ACTION),
            is_null($options) ? new BlobServiceOptions() : $options
        )->then(function ($response) {
            return LeaseResult::create(
                HttpFormatter::formatHeaders($response->getHeaders())
            );
        }, null);
    }

    /**
     * Frees the lease if it is no longer needed so that another client may
     * immediately acquire a lease against the blob.
     *
     * @param string                    $container name of the container
     * @param string                    $blob      name of the blob
     * @param string                    $leaseId   lease id when acquiring
     * @param Models\BlobServiceOptions $options   optional parameters
     *
     * @return void
     *
     * @see https://docs.microsoft.com/en-us/rest/api/storageservices/fileservices/lease-blob
     */
    public function releaseLease(
        $container,
        $blob,
        $leaseId,
        Models\BlobServiceOptions $options = null
    ) {
        $this->releaseLeaseAsync($container, $blob, $leaseId, $options)->wait();
    }

    /**
     * Creates promise to free the lease if it is no longer needed so that
     * another client may immediately acquire a lease against the blob.
     *
     * @param string                    $container name of the container
     * @param string                    $blob      name of the blob
     * @param string                    $leaseId   lease id when acquiring
     * @param Models\BlobServiceOptions $options   optional parameters
     *
     * @return \GuzzleHttp\Promise\PromiseInterface
     *
     * @see https://docs.microsoft.com/en-us/rest/api/storageservices/fileservices/lease-blob
     */
    public function releaseLeaseAsync(
        $container,
        $blob,
        $leaseId,
        Models\BlobServiceOptions $options = null
    ) {
        return $this->putLeaseAsyncImpl(
            LeaseMode::RELEASE_ACTION,
            $container,
            $blob,
            null /* proposedLeaseId */,
            null /* leaseDuration */,
            $leaseId,
            null /* breakPeriod */,
            self::getStatusCodeOfLeaseAction(LeaseMode::RELEASE_ACTION),
            is_null($options) ? new BlobServiceOptions() : $options
        );
    }

    /**
     * Ends the lease but ensure that another client cannot acquire a new lease until
     * the current lease period has expired.
     *
     * @param string                    $container     name of the container
     * @param string                    $blob          name of the blob
     * @param int                       $breakPeriod   the proposed duration of seconds that
     *                                                 lease should continue before it it broken,
     *                                                 between 0 and 60 seconds.
     * @param Models\BlobServiceOptions $options   optional parameters
     *
     * @return BreakLeaseResult
     *
     * @see https://docs.microsoft.com/en-us/rest/api/storageservices/fileservices/lease-blob
     */
    public function breakLease(
        $container,
        $blob,
        $breakPeriod = null,
        Models\BlobServiceOptions $options = null
    ) {
        return $this->breakLeaseAsync(
            $container,
            $blob,
            $breakPeriod,
            $options
        )->wait();
    }

    /**
     * Creates promise to end the lease but ensure that another client cannot
     * acquire a new lease until the current lease period has expired.
     *
     * @param string                    $container   name of the container
     * @param string                    $blob        name of the blob
     * @param int                       $breakPeriod break period, in seconds
     * @param Models\BlobServiceOptions $options     optional parameters
     *
     * @return \GuzzleHttp\Promise\PromiseInterface
     *
     * @see https://docs.microsoft.com/en-us/rest/api/storageservices/fileservices/lease-blob
     */
    public function breakLeaseAsync(
        $container,
        $blob,
        $breakPeriod = null,
        Models\BlobServiceOptions $options = null
    ) {
        return $this->putLeaseAsyncImpl(
            LeaseMode::BREAK_ACTION,
            $container,
            $blob,
            null /* proposedLeaseId */,
            null /* leaseDuration */,
            null /* leaseId */,
            $breakPeriod,
            self::getStatusCodeOfLeaseAction(LeaseMode::BREAK_ACTION),
            is_null($options) ? new BlobServiceOptions() : $options
        )->then(function ($response) {
            return BreakLeaseResult::create(
                HttpFormatter::formatHeaders($response->getHeaders())
            );
        }, null);
    }

    /**
     * Adds optional header to headers if set
     *
     * @param array                  $headers         The array of request headers.
     * @param Models\AccessCondition $accessCondition The access condition object.
     *
     * @return array
     */
    public function addOptionalAccessConditionHeader(
        array $headers,
        array $accessConditions = null
    ) {
        if (!empty($accessConditions)) {
            foreach ($accessConditions as $accessCondition) {
                if (!is_null($accessCondition)) {
                    $header = $accessCondition->getHeader();

                    if ($header != Resources::EMPTY_STRING) {
                        $value = $accessCondition->getValue();
                        if ($value instanceof \DateTime) {
                            $value = gmdate(
                                Resources::AZURE_DATE_FORMAT,
                                $value->getTimestamp()
                            );
                        }
                        $headers[$header] = $value;
                    }
                }
            }
        }

        return $headers;
    }

    /**
     * Adds optional header to headers if set
     *
     * @param array $headers         The array of request headers.
     * @param array $accessCondition The access condition object.
     *
     * @return array
     */
    public function addOptionalSourceAccessConditionHeader(
        array $headers,
        array $accessConditions = null
    ) {
        if (!empty($accessConditions)) {
            foreach ($accessConditions as $accessCondition) {
                if (!is_null($accessCondition)) {
                    $header     = $accessCondition->getHeader();
                    $headerName = null;
                    if (!empty($header)) {
                        switch ($header) {
                            case Resources::IF_MATCH:
                                $headerName = Resources::X_MS_SOURCE_IF_MATCH;
                                break;
                            case Resources::IF_UNMODIFIED_SINCE:
                                $headerName = Resources::X_MS_SOURCE_IF_UNMODIFIED_SINCE;
                                break;
                            case Resources::IF_MODIFIED_SINCE:
                                $headerName = Resources::X_MS_SOURCE_IF_MODIFIED_SINCE;
                                break;
                            case Resources::IF_NONE_MATCH:
                                $headerName = Resources::X_MS_SOURCE_IF_NONE_MATCH;
                                break;
                            default:
                                throw new \Exception(Resources::INVALID_ACH_MSG);
                                break;
                        }
                    }
                    $value = $accessCondition->getValue();
                    if ($value instanceof \DateTime) {
                        $value = gmdate(
                            Resources::AZURE_DATE_FORMAT,
                            $value->getTimestamp()
                        );
                    }

                    $this->addOptionalHeader($headers, $headerName, $value);
                }
            }
        }

        return $headers;
    }
}
