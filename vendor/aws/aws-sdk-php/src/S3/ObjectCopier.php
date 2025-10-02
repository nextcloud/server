<?php
namespace Aws\S3;

use Aws\Arn\ArnParser;
use Aws\Arn\S3\AccessPointArn;
use Aws\Exception\MultipartUploadException;
use Aws\Result;
use Aws\S3\Exception\S3Exception;
use GuzzleHttp\Promise\Coroutine;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Promise\PromisorInterface;
use InvalidArgumentException;

/**
 * Copies objects from one S3 location to another, utilizing a multipart copy
 * when appropriate.
 */
class ObjectCopier implements PromisorInterface
{
    const DEFAULT_MULTIPART_THRESHOLD = MultipartUploader::PART_MAX_SIZE;

    private $client;
    private $source;
    private $destination;
    private $acl;
    private $options;

    private static $defaults = [
        'before_lookup' => null,
        'before_upload' => null,
        'concurrency'   => 5,
        'mup_threshold' => self::DEFAULT_MULTIPART_THRESHOLD,
        'params'        => [],
        'part_size'     => null,
        'version_id'    => null,
    ];

    /**
     * @param S3ClientInterface $client         The S3 Client used to execute
     *                                          the copy command(s).
     * @param array             $source         The object to copy, specified as
     *                                          an array with a 'Bucket' and
     *                                          'Key' keys. Provide a
     *                                          'VersionID' key to copy a
     *                                          specified version of an object.
     * @param array             $destination    The bucket and key to which to
     *                                          copy the $source, specified as
     *                                          an array with a 'Bucket' and
     *                                          'Key' keys.
     * @param string            $acl            ACL to apply to the copy
     *                                          (default: private).
     * @param array             $options        Options used to configure the
     *                                          copy process. Options passed in
     *                                          through 'params' are added to
     *                                          the sub commands.
     *
     * @throws InvalidArgumentException
     */
    public function __construct(
        S3ClientInterface $client,
        array $source,
        array $destination,
        $acl = 'private',
        array $options = []
    ) {
        $this->validateLocation($source);
        $this->validateLocation($destination);

        $this->client = $client;
        $this->source = $source;
        $this->destination = $destination;
        $this->acl = $acl;
        $this->options = $options + self::$defaults;
    }

    /**
     * Perform the configured copy asynchronously. Returns a promise that is
     * fulfilled with the result of the CompleteMultipartUpload or CopyObject
     * operation or rejected with an exception.
     *
     * @return Coroutine
     */
    public function promise(): PromiseInterface
    {
        return Coroutine::of(function () {
            $headObjectCommand = $this->client->getCommand(
                'HeadObject',
                $this->options['params'] + $this->source
            );
            if (is_callable($this->options['before_lookup'])) {
                $this->options['before_lookup']($headObjectCommand);
            }
            $objectStats = (yield $this->client->executeAsync(
                $headObjectCommand
            ));

            if ($objectStats['ContentLength'] > $this->options['mup_threshold']) {
                $mup = new MultipartCopy(
                    $this->client,
                    $this->getSourcePath(),
                    ['source_metadata' => $objectStats, 'acl' => $this->acl]
                        + $this->destination
                        + $this->options
                );

                yield $mup->promise();
            } else {
                $defaults = [
                    'ACL' => $this->acl,
                    'MetadataDirective' => 'COPY',
                    'CopySource' => $this->getSourcePath(),
                ];

                $params = array_diff_key($this->options, self::$defaults)
                    + $this->destination + $defaults + $this->options['params'];

                yield $this->client->executeAsync(
                    $this->client->getCommand('CopyObject', $params)
                );
            }
        });
    }

    /**
     * Perform the configured copy synchronously. Returns the result of the
     * CompleteMultipartUpload or CopyObject operation.
     *
     * @return Result
     *
     * @throws S3Exception
     * @throws MultipartUploadException
     */
    public function copy()
    {
        return $this->promise()->wait();
    }

    private function validateLocation(array $location)
    {
        if (empty($location['Bucket']) || empty($location['Key'])) {
            throw new \InvalidArgumentException('Locations provided to an'
                . ' Aws\S3\ObjectCopier must have a non-empty Bucket and Key');
        }
    }

    private function getSourcePath()
    {
        $path = "/{$this->source['Bucket']}/";
        if (ArnParser::isArn($this->source['Bucket'])) {
            try {
                new AccessPointArn($this->source['Bucket']);
                $path = "{$this->source['Bucket']}/object/";
            } catch (\Exception $e) {
                throw new \InvalidArgumentException(
                    'Provided ARN was a not a valid S3 access point ARN ('
                    . $e->getMessage() . ')',
                    0,
                    $e
                );
            }
        }

        $sourcePath = $path . rawurlencode($this->source['Key']);
        if (isset($this->source['VersionId'])) {
            $sourcePath .= "?versionId={$this->source['VersionId']}";
        }

        return $sourcePath;
    }
}
