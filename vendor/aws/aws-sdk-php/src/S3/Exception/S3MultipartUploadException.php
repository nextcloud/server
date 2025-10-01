<?php
namespace Aws\S3\Exception;

use Aws\CommandInterface;
use Aws\Exception\AwsException;
use Aws\Multipart\UploadState;

class S3MultipartUploadException extends \Aws\Exception\MultipartUploadException
{
    /** @var string Bucket of the transfer object */
    private $bucket;
    /** @var string Key of the transfer object */
    private $key;
    /** @var string Source file name of the transfer object */
    private $filename;

    /**
     * @param UploadState      $state Upload state at time of the exception.
     * @param \Exception|array $prev  Exception being thrown. Could be an array of
     *                                AwsExceptions being thrown when uploading parts
     *                                for one object, or an instance of AwsException
     *                                for a specific Multipart error being thrown in
     *                                the MultipartUpload process.
     */
    public function __construct(UploadState $state, $prev = null) {
        if (is_array($prev) && $error = $prev[key($prev)]) {
            $this->collectPathInfo($error->getCommand());
        } elseif ($prev instanceof AwsException) {
            $this->collectPathInfo($prev->getCommand());
        }
        parent::__construct($state, $prev);
    }

    /**
     * Get the Bucket information of the transfer object
     *
     * @return string|null Returns null when 'Bucket' information
     *                     is unavailable.
     */
    public function getBucket()
    {
        return $this->bucket;
    }

    /**
     * Get the Key information of the transfer object
     *
     * @return string|null Returns null when 'Key' information
     *                     is unavailable.
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * Get the source file name of the transfer object
     *
     * @return string|null Returns null when metadata of the stream
     *                     wrapped in 'Body' parameter is unavailable.
     */
    public function getSourceFileName()
    {
        return $this->filename;
    }

    /**
     * Collect file path information when accessible. (Bucket, Key)
     *
     * @param CommandInterface $cmd
     */
    private function collectPathInfo(CommandInterface $cmd)
    {
        if (empty($this->bucket) && isset($cmd['Bucket'])) {
            $this->bucket = $cmd['Bucket'];
        }
        if (empty($this->key) && isset($cmd['Key'])) {
            $this->key = $cmd['Key'];
        }
        if (empty($this->filename) && isset($cmd['Body'])) {
            $this->filename = $cmd['Body']->getMetadata('uri');
        }
    }
}
