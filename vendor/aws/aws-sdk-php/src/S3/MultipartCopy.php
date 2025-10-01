<?php

namespace Aws\S3;

use Aws\Arn\ArnParser;
use Aws\Multipart\AbstractUploadManager;
use Aws\ResultInterface;
use GuzzleHttp\Psr7;

class MultipartCopy extends AbstractUploadManager
{
    use MultipartUploadingTrait;

    /** @var string|array */
    private $source;
    /** @var string */
    private $sourceVersionId;
    /** @var ResultInterface */
    private $sourceMetadata;

    /**
     * Creates a multipart upload for copying an S3 object.
     *
     * The valid configuration options are as follows:
     *
     * - acl: (string) ACL to set on the object being upload. Objects are
     *   private by default.
     * - before_complete: (callable) Callback to invoke before the
     *   `CompleteMultipartUpload` operation. The callback should have a
     *   function signature like `function (Aws\Command $command) {...}`.
     * - before_initiate: (callable) Callback to invoke before the
     *   `CreateMultipartUpload` operation. The callback should have a function
     *   signature like `function (Aws\Command $command) {...}`.
     * - before_upload: (callable) Callback to invoke before `UploadPartCopy`
     *   operations. The callback should have a function signature like
     *   `function (Aws\Command $command) {...}`.
     * - bucket: (string, required) Name of the bucket to which the object is
     *   being uploaded.
     * - concurrency: (int, default=int(5)) Maximum number of concurrent
     *   `UploadPart` operations allowed during the multipart upload.
     * - key: (string, required) Key to use for the object being uploaded.
     * - params: (array) An array of key/value parameters that will be applied
     *   to each of the sub-commands run by the uploader as a base.
     *   Auto-calculated options will override these parameters. If you need
     *   more granularity over parameters to each sub-command, use the before_*
     *   options detailed above to update the commands directly.
     * - part_size: (int, default=int(5242880)) Part size, in bytes, to use when
     *   doing a multipart upload. This must between 5 MB and 5 GB, inclusive.
     * - state: (Aws\Multipart\UploadState) An object that represents the state
     *   of the multipart upload and that is used to resume a previous upload.
     *   When this option is provided, the `bucket`, `key`, and `part_size`
     *   options are ignored.
     * - source_metadata: (Aws\ResultInterface) An object that represents the
     *   result of executing a HeadObject command on the copy source.
     * - display_progress: (boolean) Set true to track status in 1/8th increments
     *   for upload.
     *
     * @param S3ClientInterface $client Client used for the upload.
     * @param string|array $source Location of the data to be copied (in the
     *                       form /<bucket>/<key>).  If the key contains a '?'
     *                       character, instead pass an array of source_key,
     *                       source_bucket, and source_version_id.
     * @param array $config Configuration used to perform the upload.
     */
    public function __construct(
        S3ClientInterface $client,
        $source,
        array $config = []
    ) {
        if (is_array($source)) {
            $this->source = $source;
        } else {
            $this->source = $this->getInputSource($source);
        }

        parent::__construct(
            $client,
            array_change_key_case($config) + ['source_metadata' => null]
        );

        if ($this->displayProgress) {
            $this->getState()->setProgressThresholds(
                $this->sourceMetadata["ContentLength"]
            );
        }
    }

    /**
     * An alias of the self::upload method.
     *
     * @see self::upload
     */
    public function copy()
    {
        return $this->upload();
    }

    protected function loadUploadWorkflowInfo()
    {
        return [
            'command' => [
                'initiate' => 'CreateMultipartUpload',
                'upload' => 'UploadPartCopy',
                'complete' => 'CompleteMultipartUpload',
            ],
            'id' => [
                'bucket' => 'Bucket',
                'key' => 'Key',
                'upload_id' => 'UploadId',
            ],
            'part_num' => 'PartNumber',
        ];
    }

    protected function getUploadCommands(callable $resultHandler)
    {
        $parts = ceil($this->getSourceSize() / $this->determinePartSize());

        for ($partNumber = 1; $partNumber <= $parts; $partNumber++) {
            // If we haven't already uploaded this part, yield a new part.
            if (!$this->state->hasPartBeenUploaded($partNumber)) {
                $command = $this->client->getCommand(
                    $this->info['command']['upload'],
                    $this->createPart($partNumber, $parts) + $this->getState()->getId()
                );
                $command->getHandlerList()->appendSign($resultHandler, 'mup');
                yield $command;
            }
        }
    }

    private function createPart($partNumber, $partsCount)
    {
        $data = [];

        // Apply custom params to UploadPartCopy data
        $config = $this->getConfig();
        $params = isset($config['params']) ? $config['params'] : [];
        foreach ($params as $k => $v) {
            $data[$k] = $v;
        }
        // The source parameter here is usually a string, but can be overloaded as an array
        // if the key contains a '?' character to specify where the query parameters start
        if (is_array($this->source)) {
            $key = str_replace('%2F', '/', rawurlencode($this->source['source_key']));
            $bucket = $this->source['source_bucket'];
        } else {
            list($bucket, $key) = explode('/', ltrim($this->source, '/'), 2);
            $key = implode(
                '/',
                array_map(
                    'urlencode',
                    explode('/', rawurldecode($key))
                )
            );
        }

        $uri = ArnParser::isArn($bucket) ? '' : '/';
        $uri .= $bucket . '/' . $key;
        $data['CopySource'] = $uri;
        $data['PartNumber'] = $partNumber;
        if (!empty($this->sourceVersionId)) {
            $data['CopySource'] .= "?versionId=" . $this->sourceVersionId;
        }

        $defaultPartSize = $this->determinePartSize();
        $startByte = $defaultPartSize * ($partNumber - 1);
        $data['ContentLength'] = $partNumber < $partsCount
            ? $defaultPartSize
            : $this->getSourceSize() - ($defaultPartSize * ($partsCount - 1));
        $endByte = $startByte + $data['ContentLength'] - 1;
        $data['CopySourceRange'] = "bytes=$startByte-$endByte";

        return $data;
    }

    protected function extractETag(ResultInterface $result)
    {
        return $result->search('CopyPartResult.ETag');
    }

    protected function getSourceMimeType()
    {
        return $this->getSourceMetadata()['ContentType'];
    }

    protected function getSourceSize()
    {
        return $this->getSourceMetadata()['ContentLength'];
    }

    private function getSourceMetadata()
    {
        if (empty($this->sourceMetadata)) {
            $this->sourceMetadata = $this->fetchSourceMetadata();
        }

        return $this->sourceMetadata;
    }

    private function fetchSourceMetadata()
    {
        if ($this->config['source_metadata'] instanceof ResultInterface) {
            return $this->config['source_metadata'];
        }
        //if the source variable was overloaded with an array, use the inputs for key and bucket
        if (is_array($this->source)) {
            $headParams = [
                'Key' => $this->source['source_key'],
                'Bucket' => $this->source['source_bucket']
            ];
            if (isset($this->source['source_version_id'])) {
                $this->sourceVersionId = $this->source['source_version_id'];
                $headParams['VersionId'] = $this->sourceVersionId;
            }
        //otherwise, use the default source parsing behavior
        } else {
            list($bucket, $key) = explode('/', ltrim($this->source, '/'), 2);
            $headParams = [
                'Bucket' => $bucket,
                'Key' => $key,
            ];
            if (strpos($key, '?')) {
                list($key, $query) = explode('?', $key, 2);
                $headParams['Key'] = $key;
                $query = Psr7\Query::parse($query, false);
                if (isset($query['versionId'])) {
                    $this->sourceVersionId = $query['versionId'];
                    $headParams['VersionId'] = $this->sourceVersionId;
                }
            }
        }
        return $this->client->headObject($headParams);
    }

    /**
     * Get the url decoded input source, starting with a slash if it is not an
     * ARN to standardize the source location syntax.
     *
     * @param string $inputSource The source that was passed to the constructor
     * @return string The source, starting with a slash if it's not an arn
     */
    private function getInputSource($inputSource)
    {
        $sourceBuilder = ArnParser::isArn($inputSource) ? '' : '/';
        $sourceBuilder .= ltrim(rawurldecode($inputSource), '/');
        return $sourceBuilder;
    }
}
