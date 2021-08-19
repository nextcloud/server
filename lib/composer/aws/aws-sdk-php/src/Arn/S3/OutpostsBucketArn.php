<?php
namespace Aws\Arn\S3;

use Aws\Arn\Arn;
use Aws\Arn\Exception\InvalidArnException;
use Aws\Arn\ResourceTypeAndIdTrait;

/**
 * This class represents an S3 Outposts bucket ARN, which is in the
 * following format:
 *
 * @internal
 */
class OutpostsBucketArn extends Arn implements
    BucketArnInterface,
    OutpostsArnInterface
{
    use ResourceTypeAndIdTrait;

    /**
     * Parses a string into an associative array of components that represent
     * a OutpostsBucketArn
     *
     * @param $string
     * @return array
     */
    public static function parse($string)
    {
        $data = parent::parse($string);
        $data = self::parseResourceTypeAndId($data);
        return self::parseOutpostData($data);
    }

    public function getBucketName()
    {
        return $this->data['bucket_name'];
    }

    public function getOutpostId()
    {
        return $this->data['outpost_id'];
    }

    private static function parseOutpostData(array $data)
    {
        $resourceData = preg_split("/[\/:]/", $data['resource_id'], 3);

        $data['outpost_id'] = isset($resourceData[0])
            ? $resourceData[0]
            : null;
        $data['bucket_label'] = isset($resourceData[1])
            ? $resourceData[1]
            : null;
        $data['bucket_name'] = isset($resourceData[2])
            ? $resourceData[2]
            : null;

        return $data;
    }

    /**
     *
     * @param array $data
     */
    public static function validate(array $data)
    {
        Arn::validate($data);

        if (($data['service'] !== 's3-outposts')) {
            throw new InvalidArnException("The 3rd component of an S3 Outposts"
                . " bucket ARN represents the service and must be 's3-outposts'.");
        }

        self::validateRegion($data, 'S3 Outposts bucket ARN');
        self::validateAccountId($data, 'S3 Outposts bucket ARN');

        if (($data['resource_type'] !== 'outpost')) {
            throw new InvalidArnException("The 6th component of an S3 Outposts"
                . " bucket ARN represents the resource type and must be"
                . " 'outpost'.");
        }

        if (!self::isValidHostLabel($data['outpost_id'])) {
            throw new InvalidArnException("The 7th component of an S3 Outposts"
                . " bucket ARN is required, represents the outpost ID, and"
                . " must be a valid host label.");
        }

        if ($data['bucket_label'] !== 'bucket') {
            throw new InvalidArnException("The 8th component of an S3 Outposts"
                . " bucket ARN must be 'bucket'");
        }

        if (empty($data['bucket_name'])) {
            throw new InvalidArnException("The 9th component of an S3 Outposts"
                . " bucket ARN represents the bucket name and must not be empty.");
        }
    }
}
