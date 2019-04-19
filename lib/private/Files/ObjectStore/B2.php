<?php
/**
 * @copyright Copyright (c) 2019 Biagio Carrella <biagio@biagiocarrella.it>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OC\Files\ObjectStore;

use obregonco\B2\Client;
use obregonco\B2\Bucket;
use OCP\Files\ObjectStore\IObjectStore;

class B2 implements IObjectStore
{
    /** @var string */
    private $bucketName;
    /** @var string */
    private $accountId;
    /** @var string */
    private $keyId;
    /** @var string */
    private $applicationKey;
    /** @var Client|null */
    private $client = null;
    /** @var bool  */
    private $autoCreate = false;
    /** @var Bucket|null */
    private $bucket = null;

    /**
     * @param array $parameters
     */
    public function __construct($parameters)
    {
        $this->bucketName = $parameters['bucket_name'];
        $this->accountId = $parameters['account_id'];
        $this->keyId = $parameters['key_id'];
        $this->applicationKey = $parameters['application_key'];
        if (isset($parameters['autocreate'])) {
            $this->autoCreate = $parameters['autocreate'];
        }
    }

    /**
     * @return Client
     */
    private function getClient()
    {
        if (!$this->client) {
            $this->client = new Client($this->accountId, 
            [
                'keyId' => $this->keyId, // optional if you want to use master key (account Id)
                'applicationKey' => $this->applicationKey,
                'version' => 2, // By default will use version 1
            ],
            [
                'largeFileLimit' => (4*1024*1024*1024), // Lower limit for using large files upload support. Default: 3GB
                'cacheParentDir' => \OC::$server->getConfig()->getSystemValue('datadirectory'),
            ]
            );

            if ($this->autoCreate) {
                $bucketExists = false;

                $buckets = $this->client->listBuckets();
                foreach ($buckets as $bucket) {
                    if ($bucket->getName() === $this->bucketName) {
                        $bucketExists = true;
                    }
                }

                if (!$bucketExists) {
                    $this->bucket = $this->client->createBucket([
                        'BucketName' => $this->bucketName,
                        'BucketType' => Bucket::TYPE_PRIVATE,
                        'KeepLastVersionOnly' => true
                    ]);
                }

            }
        }
        return $this->client;
    }

    /**
     * @return string the container or bucket name where objects are stored
     */
    public function getStorageId()
    {
        return $this->bucketName;
    }

    /**
     * @param string $urn the unified resource name used to identify the object
     * @return resource stream with the read data
     * @throws \Exception when something goes wrong, message will be logged
     */
    public function readObject($urn)
    {
        $fileContent = $this->getClient()->download([
            'BucketName' => $this->bucketName,
            'FileName' => $urn,
        ]);

        $stream = fopen('php://temp','r+');
        fwrite($stream, $fileContent);
        rewind($stream);

        return $stream;
    }

    /**
     * @param string $urn the unified resource name used to identify the object
     * @param resource $stream stream with the data to write
     * @throws \Exception when something goes wrong, message will be logged
     */
    public function writeObject($urn, $stream)
    {
        $file = $this->getClient()->upload([
            'BucketName' => $this->bucketName,
            'FileName' => $urn,
            'Body' => stream_get_contents($stream)
        ]);
    }

    /**
     * @param string $urn the unified resource name used to identify the object
     * @return void
     * @throws \Exception when something goes wrong, message will be logged
     */
    public function deleteObject($urn)
    {
        $fileDelete = $this->client->deleteFile([
            'BucketName' => $this->bucketName,
            'FileName' => $urn,
        ]);
    }

    public function objectExists($urn)
    {
        return $this->getClient()->fileExists([
            'BucketName' => $this->bucketName,
            'FileName' => $urn,
        ]);
    }
}
