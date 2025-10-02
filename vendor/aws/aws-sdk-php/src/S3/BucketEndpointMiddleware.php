<?php
namespace Aws\S3;

use Aws\CommandInterface;
use Psr\Http\Message\RequestInterface;

/**
 * Used to update the host used for S3 requests in the case of using a
 * "bucket endpoint" or CNAME bucket.
 *
 * IMPORTANT: this middleware must be added after the "build" step.
 *
 * @internal
 */
class BucketEndpointMiddleware
{
    private static $exclusions = ['GetBucketLocation' => true];
    private $nextHandler;

    /**
     * Create a middleware wrapper function.
     *
     * @return callable
     */
    public static function wrap()
    {
        return function (callable $handler) {
            return new self($handler);
        };
    }

    public function __construct(callable $nextHandler)
    {
        $this->nextHandler = $nextHandler;
    }

    public function __invoke(CommandInterface $command, RequestInterface $request)
    {
        $nextHandler = $this->nextHandler;
        $bucket = $command['Bucket'];

        if ($bucket && !isset(self::$exclusions[$command->getName()])) {
            $request = $this->modifyRequest($request, $command);
        }

        return $nextHandler($command, $request);
    }

    /**
     * Performs a one-time removal of Bucket from path, then if
     * the bucket name is duplicated in the path, performs additional
     * removal which is dependent on the number of occurrences of the bucket
     * name in a path-like format in the key name.
     *
     * @return string
     */
    private function removeBucketFromPath($path, $bucket, $key)
    {
        $occurrencesInKey = $this->getBucketNameOccurrencesInKey($key, $bucket);
        do {
            $len = strlen($bucket) + 1;
            if (substr($path, 0, $len) === "/{$bucket}") {
                $path = substr($path, $len);
            }
        } while (substr_count($path, "/{$bucket}") > $occurrencesInKey + 1);

        return $path ?: '/';
    }

    private function removeDuplicateBucketFromHost($host, $bucket)
    {
        if (substr_count($host, $bucket) > 1) {
            while (strpos($host, "{$bucket}.{$bucket}") === 0) {
                $hostArr = explode('.', $host);
                array_shift($hostArr);
                $host = implode('.', $hostArr);
            }
        }
        return $host;
    }

    private function getBucketNameOccurrencesInKey($key, $bucket)
    {
        $occurrences = 0;
        if (empty($key)) {
            return $occurrences;
        }

        $segments = explode('/', $key);
        foreach($segments as $segment) {
            if (strpos($segment, $bucket) === 0) {
                $occurrences++;
            }
        }
        return $occurrences;
    }

    private function modifyRequest(
        RequestInterface $request,
        CommandInterface $command
    ) {
        $key = isset($command['Key']) ? $command['Key'] : null;
        $uri = $request->getUri();
        $path = $uri->getPath();
        $host = $uri->getHost();
        $bucket = $command['Bucket'];
        $path = $this->removeBucketFromPath($path, $bucket, $key);
        $host = $this->removeDuplicateBucketFromHost($host, $bucket);

        // Modify the Key to make sure the key is encoded, but slashes are not.
        if ($key) {
            $path = S3Client::encodeKey(rawurldecode($path));
        }

        return $request->withUri(
            $uri->withHost($host)
                ->withPath($path)
        );
    }
}
