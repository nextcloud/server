<?php
namespace Aws\Arn;

use Aws\Arn\S3\AccessPointArn as S3AccessPointArn;
use Aws\Arn\ObjectLambdaAccessPointArn;
use Aws\Arn\S3\OutpostsBucketArn;
use Aws\Arn\S3\RegionalBucketArn;
use Aws\Arn\S3\OutpostsAccessPointArn;

/**
 * This class provides functionality to parse ARN strings and return a
 * corresponding ARN object. ARN-parsing logic may be subject to change in the
 * future, so this should not be relied upon for external customer usage.
 *
 * @internal
 */
class ArnParser
{
    /**
     * @param $string
     * @return bool
     */
    public static function isArn($string)
    {
        return strpos($string, 'arn:') === 0;
    }

    /**
     * Parses a string and returns an instance of ArnInterface. Returns a
     * specific type of Arn object if it has a specific class representation
     * or a generic Arn object if not.
     *
     * @param $string
     * @return ArnInterface
     */
    public static function parse($string)
    {
        $data = Arn::parse($string);
        if ($data['service'] === 's3-object-lambda') {
            return new ObjectLambdaAccessPointArn($string);
        }
        $resource = self::explodeResourceComponent($data['resource']);
        if ($resource[0] === 'outpost') {
            if (isset($resource[2]) && $resource[2] === 'bucket') {
                return new OutpostsBucketArn($string);
            }
            if (isset($resource[2]) && $resource[2] === 'accesspoint') {
                return new OutpostsAccessPointArn($string);
            }
        }
        if ($resource[0] === 'accesspoint') {
            if ($data['service'] === 's3') {
                return new S3AccessPointArn($string);
            }
            return new AccessPointArn($string);
        }

        return new Arn($data);
    }

    private static function explodeResourceComponent($resource)
    {
        return preg_split("/[\/:]/", $resource);
    }
}
