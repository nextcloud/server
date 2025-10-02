<?php
namespace Aws\S3;

use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Promise\PromisorInterface;
use GuzzleHttp\Psr7;
use Psr\Http\Message\StreamInterface;

/**
 * Uploads an object to S3, using a PutObject command or a multipart upload as
 * appropriate.
 */
class ObjectUploader implements PromisorInterface
{
    const DEFAULT_MULTIPART_THRESHOLD = 16777216;

    private $client;
    private $bucket;
    private $key;
    private $body;
    private $acl;
    private $options;
    private static $defaults = [
        'before_upload' => null,
        'concurrency'   => 3,
        'mup_threshold' => self::DEFAULT_MULTIPART_THRESHOLD,
        'params'        => [],
        'part_size'     => null,
    ];
    private $addContentMD5;

    /**
     * @param S3ClientInterface $client         The S3 Client used to execute
     *                                          the upload command(s).
     * @param string            $bucket         Bucket to upload the object, or
     *                                          an S3 access point ARN.
     * @param string            $key            Key of the object.
     * @param mixed             $body           Object data to upload. Can be a
     *                                          StreamInterface, PHP stream
     *                                          resource, or a string of data to
     *                                          upload.
     * @param string            $acl            ACL to apply to the copy
     *                                          (default: private).
     * @param array             $options        Options used to configure the
     *                                          copy process. Options passed in
     *                                          through 'params' are added to
     *                                          the sub command(s).
     */
    public function __construct(
        S3ClientInterface $client,
        $bucket,
        $key,
        $body,
        $acl = 'private',
        array $options = []
    ) {
        $this->client = $client;
        $this->bucket = $bucket;
        $this->key = $key;
        $this->body = Psr7\Utils::streamFor($body);
        $this->acl = $acl;
        $this->options = $options + self::$defaults;
        // Handle "add_content_md5" option.
        $this->addContentMD5 = isset($options['add_content_md5'])
            && $options['add_content_md5'] === true;
    }

    /**
     * @return PromiseInterface
     */
    public function promise(): PromiseInterface
    {
        /** @var int $mup_threshold */
        $mup_threshold = $this->options['mup_threshold'];
        if ($this->requiresMultipart($this->body, $mup_threshold)) {
            // Perform a multipart upload.
            return (new MultipartUploader($this->client, $this->body, [
                    'bucket' => $this->bucket,
                    'key'    => $this->key,
                    'acl'    => $this->acl
                ] + $this->options))->promise();
        }

        // Perform a regular PutObject operation.
        $command = $this->client->getCommand('PutObject', [
                'Bucket' => $this->bucket,
                'Key'    => $this->key,
                'Body'   => $this->body,
                'ACL'    => $this->acl,
                'AddContentMD5' => $this->addContentMD5
            ] + $this->options['params']);
        if (is_callable($this->options['before_upload'])) {
            $this->options['before_upload']($command);
        }
        return $this->client->executeAsync($command);
    }

    public function upload()
    {
        return $this->promise()->wait();
    }

    /**
     * Determines if the body should be uploaded using PutObject or the
     * Multipart Upload System. It also modifies the passed-in $body as needed
     * to support the upload.
     *
     * @param StreamInterface $body      Stream representing the body.
     * @param integer             $threshold Minimum bytes before using Multipart.
     *
     * @return bool
     */
    private function requiresMultipart(StreamInterface &$body, $threshold)
    {
        // If body size known, compare to threshold to determine if Multipart.
        if ($body->getSize() !== null) {
            return $body->getSize() >= $threshold;
        }

        /**
         * Handle the situation where the body size is unknown.
         * Read up to 5MB into a buffer to determine how to upload the body.
         * @var StreamInterface $buffer
         */
        $buffer = Psr7\Utils::streamFor();
        Psr7\Utils::copyToStream($body, $buffer, MultipartUploader::PART_MIN_SIZE);

        // If body < 5MB, use PutObject with the buffer.
        if ($buffer->getSize() < MultipartUploader::PART_MIN_SIZE) {
            $buffer->seek(0);
            $body = $buffer;
            return false;
        }

        // If body >= 5 MB, then use multipart. [YES]
        if ($body->isSeekable() && $body->getMetadata('uri') !== 'php://input') {
            // If the body is seekable, just rewind the body.
            $body->seek(0);
        } else {
            // If the body is non-seekable, stitch the rewind the buffer and
            // the partially read body together into one stream. This avoids
            // unnecessary disc usage and does not require seeking on the
            // original stream.
            $buffer->seek(0);
            $body = new Psr7\AppendStream([$buffer, $body]);
        }

        return true;
    }
}
