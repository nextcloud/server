<?php
namespace Aws\S3;

use Aws\CommandInterface;
use Aws\Multipart\UploadState;
use Aws\ResultInterface;

trait MultipartUploadingTrait
{
    /**
     * Creates an UploadState object for a multipart upload by querying the
     * service for the specified upload's information.
     *
     * @param S3ClientInterface $client   S3Client used for the upload.
     * @param string            $bucket   Bucket for the multipart upload.
     * @param string            $key      Object key for the multipart upload.
     * @param string            $uploadId Upload ID for the multipart upload.
     *
     * @return UploadState
     */
    public static function getStateFromService(
        S3ClientInterface $client,
        $bucket,
        $key,
        $uploadId
    ) {
        $state = new UploadState([
            'Bucket'   => $bucket,
            'Key'      => $key,
            'UploadId' => $uploadId,
        ]);

        foreach ($client->getPaginator('ListParts', $state->getId()) as $result) {
            // Get the part size from the first part in the first result.
            if (!$state->getPartSize()) {
                $state->setPartSize($result->search('Parts[0].Size'));
            }
            // Mark all the parts returned by ListParts as uploaded.
            foreach ($result['Parts'] as $part) {
                $state->markPartAsUploaded($part['PartNumber'], [
                    'PartNumber' => $part['PartNumber'],
                    'ETag'       => $part['ETag']
                ]);
            }
        }

        $state->setStatus(UploadState::INITIATED);

        return $state;
    }

    protected function handleResult(CommandInterface $command, ResultInterface $result)
    {
        $this->getState()->markPartAsUploaded($command['PartNumber'], [
            'PartNumber' => $command['PartNumber'],
            'ETag'       => $this->extractETag($result),
        ]);
    }

    abstract protected function extractETag(ResultInterface $result);

    protected function getCompleteParams()
    {
        $config = $this->getConfig();
        $params = isset($config['params']) ? $config['params'] : [];

        $params['MultipartUpload'] = [
            'Parts' => $this->getState()->getUploadedParts()
        ];

        return $params;
    }

    protected function determinePartSize()
    {
        // Make sure the part size is set.
        $partSize = $this->getConfig()['part_size'] ?: MultipartUploader::PART_MIN_SIZE;

        // Adjust the part size to be larger for known, x-large uploads.
        if ($sourceSize = $this->getSourceSize()) {
            $partSize = (int) max(
                $partSize,
                ceil($sourceSize / MultipartUploader::PART_MAX_NUM)
            );
        }

        // Ensure that the part size follows the rules: 5 MB <= size <= 5 GB.
        if ($partSize < MultipartUploader::PART_MIN_SIZE || $partSize > MultipartUploader::PART_MAX_SIZE) {
            throw new \InvalidArgumentException('The part size must be no less '
                . 'than 5 MB and no greater than 5 GB.');
        }

        return $partSize;
    }

    protected function getInitiateParams()
    {
        $config = $this->getConfig();
        $params = isset($config['params']) ? $config['params'] : [];

        if (isset($config['acl'])) {
            $params['ACL'] = $config['acl'];
        }

        // Set the ContentType if not already present
        if (empty($params['ContentType']) && $type = $this->getSourceMimeType()) {
            $params['ContentType'] = $type;
        }

        return $params;
    }

    /**
     * @return UploadState
     */
    abstract protected function getState();

    /**
     * @return array
     */
    abstract protected function getConfig();

    /**
     * @return int
     */
    abstract protected function getSourceSize();

    /**
     * @return string|null
     */
    abstract protected function getSourceMimeType();
}
