<?php
namespace Aws\S3;

use Aws\HashingStream;
use Aws\Multipart\AbstractUploader;
use Aws\PhpHash;
use Aws\ResultInterface;
use GuzzleHttp\Psr7;
use Psr\Http\Message\StreamInterface as Stream;
use Aws\S3\Exception\S3MultipartUploadException;

/**
 * Encapsulates the execution of a multipart upload to S3 or Glacier.
 */
class MultipartUploader extends AbstractUploader
{
    use MultipartUploadingTrait;

    const PART_MIN_SIZE = 5242880;
    const PART_MAX_SIZE = 5368709120;
    const PART_MAX_NUM = 10000;

    /**
     * Creates a multipart upload for an S3 object.
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
     * - before_upload: (callable) Callback to invoke before any `UploadPart`
     *   operations. The callback should have a function signature like
     *   `function (Aws\Command $command) {...}`.
     * - bucket: (string, required) Name of the bucket to which the object is
     *   being uploaded, or an S3 access point ARN.
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
     * - prepare_data_source: (callable) Callback to invoke before starting the
     *   multipart upload workflow. The callback should have a function
     *   signature like `function () {...}`.
     * - state: (Aws\Multipart\UploadState) An object that represents the state
     *   of the multipart upload and that is used to resume a previous upload.
     *   When this option is provided, the `bucket`, `key`, and `part_size`
     *   options are ignored.
     *
     * @param S3ClientInterface $client Client used for the upload.
     * @param mixed             $source Source of the data to upload.
     * @param array             $config Configuration used to perform the upload.
     */
    public function __construct(
        S3ClientInterface $client,
        $source,
        array $config = []
    ) {
        parent::__construct($client, $source, array_change_key_case($config) + [
            'bucket' => null,
            'key'    => null,
            'exception_class' => S3MultipartUploadException::class,
        ]);
    }

    protected function loadUploadWorkflowInfo()
    {
        return [
            'command' => [
                'initiate' => 'CreateMultipartUpload',
                'upload'   => 'UploadPart',
                'complete' => 'CompleteMultipartUpload',
            ],
            'id' => [
                'bucket'    => 'Bucket',
                'key'       => 'Key',
                'upload_id' => 'UploadId',
            ],
            'part_num' => 'PartNumber',
        ];
    }

    protected function createPart($seekable, $number)
    {
        // Initialize the array of part data that will be returned.
        $data = [];

        // Apply custom params to UploadPart data
        $config = $this->getConfig();
        $params = isset($config['params']) ? $config['params'] : [];
        foreach ($params as $k => $v) {
            $data[$k] = $v;
        }

        $data['PartNumber'] = $number;

        // Read from the source to create the body stream.
        if ($seekable) {
            // Case 1: Source is seekable, use lazy stream to defer work.
            $body = $this->limitPartStream(
                new Psr7\LazyOpenStream($this->source->getMetadata('uri'), 'r')
            );
        } else {
            // Case 2: Stream is not seekable; must store in temp stream.
            $source = $this->limitPartStream($this->source);
            $source = $this->decorateWithHashes($source, $data);
            $body = Psr7\Utils::streamFor();
            Psr7\Utils::copyToStream($source, $body);
        }

        $contentLength = $body->getSize();

        // Do not create a part if the body size is zero.
        if ($contentLength === 0) {
            return false;
        }

        $body->seek(0);
        $data['Body'] = $body;
        $data['ContentLength'] = $contentLength;

        return $data;
    }

    protected function extractETag(ResultInterface $result)
    {
        return $result['ETag'];
    }

    protected function getSourceMimeType()
    {
        if ($uri = $this->source->getMetadata('uri')) {
            return Psr7\MimeType::fromFilename($uri)
                ?: 'application/octet-stream';
        }
    }

    protected function getSourceSize()
    {
        return $this->source->getSize();
    }

    /**
     * Decorates a stream with a sha256 linear hashing stream.
     *
     * @param Stream $stream Stream to decorate.
     * @param array  $data   Part data to augment with the hash result.
     *
     * @return Stream
     */
    private function decorateWithHashes(Stream $stream, array &$data)
    {
        // Decorate source with a hashing stream
        $hash = new PhpHash('sha256');
        return new HashingStream($stream, $hash, function ($result) use (&$data) {
            $data['ContentSHA256'] = bin2hex($result);
        });
    }
}
