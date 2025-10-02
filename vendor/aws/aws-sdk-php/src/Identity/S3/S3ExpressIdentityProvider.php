<?php
namespace Aws\Identity\S3;

use Aws;
use Aws\LruArrayCache;
use GuzzleHttp\Promise;

class S3ExpressIdentityProvider
{
    private $cache;
    private $region;
    private $config;
    private $s3Client;

    public function __construct($clientRegion, array $config = [])
    {
        $this->cache = new LruArrayCache(100);
        $this->region = $clientRegion;
        $this->config = $config;
    }

    public function __invoke($command)
    {
        $s3Client = $this->getS3Client();
        $bucket = $command['Bucket'];
        if ($identity = $this->cache->get($bucket)) {
            if (!$identity->isExpired()) {
                return Promise\Create::promiseFor($identity);
            }
        }
        $response = $s3Client->createSession(['Bucket' => $bucket]);
        $identity = new Aws\Identity\S3\S3ExpressIdentity(
            $response['Credentials']['AccessKeyId'],
            $response['Credentials']['SecretAccessKey'],
            $response['Credentials']['SessionToken'],
            $response['Credentials']['Expiration']->getTimestamp()
        );
        $this->cache->set($bucket, $identity);
        return Promise\Create::promiseFor($identity);
    }

    private function getS3Client()
    {
        if (is_null($this->s3Client)) {
            $this->s3Client = $this->config['client']
                ?? new Aws\S3\S3Client([
                    'region' => $this->region,
                    'disable_express_session_auth' => true
                ]);
        }
        return $this->s3Client;
    }
}
