<?php

namespace OC\Files\ObjectStore;

use OCP\Files\NotFoundException;
use OCP\Files\ObjectStore\IObjectStore;
use Qcloud\Cos\Client;
use Qcloud\Cos\MultipartUpload;

class TencentCloud implements IObjectStore
{
    private static $cosClient;
    private $secretId;
    private $secretKey;
    private $region;
    private $bucket;
    private $createBucket;
    private $partSize;

    public function __construct(array $arguments)
    {
        $this->secretId = $arguments['secretId'];
        $this->secretKey = $arguments['secretKey'];
        $this->region = $arguments['region'];
        $this->bucket = $arguments['bucket'];
        $this->createBucket = $arguments['createBucket'];
        $this->partSize = isset($arguments['partSize']) && $arguments['partSize'] >= MultipartUpload::MIN_PART_SIZE ?
            $arguments['partSize']:16777216;
    }

    private function getCosClient()
    {
        if ( self::$cosClient instanceof Client ) {
            return self::$cosClient;
        }
        self::$cosClient = new Client(
            [
                'region' => $this->region,
                'schema' => 'https',
                'credentials' => [
                    'secretId' => $this->secretId,
                    'secretKey' => $this->secretKey
                ]
            ]
        );
        //report usage data only once
        if (!\OC::$server->getSystemConfig()->getValue('tencentcloud_uin_reported',false)) {
            $this->reportUsageData();
            \OC::$server->getSystemConfig()->setValue('tencentcloud_uin_reported',true);
        }
        //auto create bucket if not exist
        if (!$this->bucketExist()) {
            $this->createBucket();
        }
        return self::$cosClient;
    }

    /**
     * @return bool
     */
    public function bucketExist()
    {
        try {
            $this->getCosClient()->HeadBucket(array(
                'Bucket' => $this->bucket));
            return true;
        }catch (\Exception $e){
            return false;
        }
    }

    /**
     * @throws \Exception
     */
    private function createBucket()
    {
        try {
            self::$cosClient->createBucket(
                [
                    'Bucket' => $this->bucket
                ]
            );
        } catch (\Exception $e) {
            // 409 means already already exists
            if ( $e->getStatusCode() !== 409 ) {
                throw $e;
            }
        }
    }

    /**
     * @return string
     */
    public function getStorageId()
    {
        return 'tencentcloud::cos::' . $this->bucket;
    }

    /**
     * @param string $urn
     * @return resource
     */
    public function readObject($urn)
    {
        $result = $this->getCosClient()->getObject([
                'Bucket' => $this->bucket,
                'Key' => $urn
            ]
        );
        return $result['Body']->detach();
    }

    /**
     * @param string $urn
     * @param resource $stream
     */
    public function writeObject($urn, $stream)
    {
        $tmpFile = \OC::$server->getTempManager()->getTemporaryFile('tencentcloud');
        file_put_contents($tmpFile, $stream);
        $handle = fopen($tmpFile, 'rb');
        $option['PartSize'] = $this->partSize;
        $this->getCosClient()->upload($this->bucket, $urn, $handle, $option);
    }

    /**
     * @param string $urn
     */
    public function deleteObject($urn)
    {
        $this->getCosClient()->deleteObject(
            [
                'Bucket' => $this->bucket,
                'Key' => $urn,
            ]
        );
    }

    public function objectExists($urn)
    {
        return $this->getCosClient()->doesObjectExist($this->bucket, $urn);
    }

    private function reportUsageData()
    {
        try {
            $data = [
                'action'=>'save_config',
                'plugin_type'=>'cos',
                'data'=>[
                    'site_id'  => 'nextcloud_'.\OC::$server->getSystemConfig()->getValue('instanceid'),
                    'site_url' => \OC::$server->getSystemConfig()->getValue('overwrite.cli.url'),
                    'site_app' => 'Nextcloud',
                    'uin'=>$this->getUserUin(),
                    'others'=>\GuzzleHttp\json_encode([
                        'cos_bucket'=>$this->bucket,
                        'cos_region'=>$this->region,
                    ])
                ]
            ];
            (new \GuzzleHttp\Client())->post('https://openapp.qq.com/api/public/index.php/upload', [
                \GuzzleHttp\RequestOptions::JSON => $data
            ]);
        } catch (\Exception $e) {
            return;
        }
    }

    /**
     * get user Uin by  secretId and secretKey
     * @return string
     */
    private function getUserUin()
    {
        try {
            $options = [
                'headers'=>$this->getHeadersWithSignature(),
                'body'=>'{}'
            ];
            $response = (new \GuzzleHttp\Client(['base_uri'=>'https://ms.tencentcloudapi.com']))
                ->post('/', $options)
                ->getBody()
                ->getContents();
            $response = \GuzzleHttp\json_decode($response);
            return $response->Response->UserUin;
        }catch (\Exception $e) {
            return '';
        }
    }

    private function getHeadersWithSignature()
    {
        $headers = array();
        $service = 'ms';
        $timestamp = time();
        $algo = 'TC3-HMAC-SHA256';
        $headers['Host'] = 'ms.tencentcloudapi.com';
        $headers['X-TC-Action'] = 'DescribeUserBaseInfoInstance';
        $headers['X-TC-RequestClient'] = 'SDK_PHP_3.0.187';
        $headers['X-TC-Timestamp'] = $timestamp;
        $headers['X-TC-Version'] = '2018-04-08';
        $headers['Content-Type'] = 'application/json';
        $canonicalHeaders = 'content-type:'.$headers['Content-Type']."\n".
            'host:'.$headers['Host']."\n";
        $canonicalRequest = "POST\n/\n\n".
            $canonicalHeaders."\n".
            "content-type;host\n".
            hash('SHA256', '{}');
        $date = gmdate('Y-m-d', $timestamp);
        $credentialScope = $date.'/'.$service.'/tc3_request';
        $str2sign = $algo."\n".
            $headers['X-TC-Timestamp']."\n".
            $credentialScope."\n".
            hash('SHA256', $canonicalRequest);
        $dateKey = hash_hmac('SHA256', $date, 'TC3'.$this->secretKey, true);
        $serviceKey = hash_hmac('SHA256', $service, $dateKey, true);
        $reqKey = hash_hmac('SHA256', 'tc3_request', $serviceKey, true);
        $signature =  hash_hmac('SHA256', $str2sign, $reqKey);
        $headers['Authorization'] = $algo. ' Credential='.$this->secretId.'/'.$credentialScope.
            ', SignedHeaders=content-type;host, Signature='.$signature;
        return $headers;
    }
}