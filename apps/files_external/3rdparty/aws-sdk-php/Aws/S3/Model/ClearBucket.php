<?php
/**
 * Copyright 2010-2013 Amazon.com, Inc. or its affiliates. All Rights Reserved.
 *
 * Licensed under the Apache License, Version 2.0 (the "License").
 * You may not use this file except in compliance with the License.
 * A copy of the License is located at
 *
 * http://aws.amazon.com/apache2.0
 *
 * or in the "license" file accompanying this file. This file is distributed
 * on an "AS IS" BASIS, WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either
 * express or implied. See the License for the specific language governing
 * permissions and limitations under the License.
 */

namespace Aws\S3\Model;

use Aws\Common\Client\AwsClientInterface;
use Aws\Common\Iterator\AwsResourceIterator;
use Guzzle\Common\AbstractHasDispatcher;
use Guzzle\Batch\FlushingBatch;
use Guzzle\Batch\ExceptionBufferingBatch;
use Guzzle\Batch\NotifyingBatch;
use Guzzle\Common\Exception\ExceptionCollection;

/**
 * Class used to clear the contents of a bucket or the results of an iterator
 */
class ClearBucket extends AbstractHasDispatcher
{
    /**
     * @var string Event emitted when a batch request has completed
     */
    const AFTER_DELETE = 'clear_bucket.after_delete';

    /**
     * @var string Event emitted before the bucket is cleared
     */
    const BEFORE_CLEAR = 'clear_bucket.before_clear';

    /**
     * @var string Event emitted after the bucket is cleared
     */
    const AFTER_CLEAR = 'clear_bucket.after_clear';

    /**
     * @var AwsClientInterface Client used to execute the requests
     */
    protected $client;

    /**
     * @var AbstractS3ResourceIterator Iterator used to yield keys
     */
    protected $iterator;

    /**
     * @var string MFA used with each request
     */
    protected $mfa;

    /**
     * @param AwsClientInterface $client Client used to execute requests
     * @param string             $bucket Name of the bucket to clear
     */
    public function __construct(AwsClientInterface $client, $bucket)
    {
        $this->client = $client;
        $this->bucket = $bucket;
    }

    /**
     * {@inheritdoc}
     */
    public static function getAllEvents()
    {
        return array(self::AFTER_DELETE, self::BEFORE_CLEAR, self::AFTER_CLEAR);
    }

    /**
     * Set the bucket that is to be cleared
     *
     * @param string $bucket Name of the bucket to clear
     *
     * @return self
     */
    public function setBucket($bucket)
    {
        $this->bucket = $bucket;

        return $this;
    }

    /**
     * Get the iterator used to yield the keys to be deleted. A default iterator
     * will be created and returned if no iterator has been explicitly set.
     *
     * @return \Iterator
     */
    public function getIterator()
    {
        if (!$this->iterator) {
            $this->iterator = $this->client->getIterator('ListObjectVersions', array(
                'Bucket' => $this->bucket
            ));
        }

        return $this->iterator;
    }

    /**
     * Sets a different iterator to use than the default iterator. This can be helpful when you wish to delete
     * only specific keys from a bucket (e.g. keys that match a certain prefix or delimiter, or perhaps keys that
     * pass through a filtered, decorated iterator).
     *
     * @param \Iterator $iterator Iterator used to yield the keys to be deleted
     *
     * @return self
     */
    public function setIterator(\Iterator $iterator)
    {
        $this->iterator = $iterator;

        return $this;
    }

    /**
     * Set the MFA token to send with each request
     *
     * @param string $mfa MFA token to send with each request. The value is the concatenation of the authentication
     *                    device's serial number, a space, and the value displayed on your authentication device.
     *
     * @return self
     */
    public function setMfa($mfa)
    {
        $this->mfa = $mfa;

        return $this;
    }

    /**
     * Clear the bucket
     *
     * @return int Returns the number of deleted keys
     * @throws ExceptionCollection
     */
    public function clear()
    {
        $that = $this;
        $batch = DeleteObjectsBatch::factory($this->client, $this->bucket, $this->mfa);
        $batch = new NotifyingBatch($batch, function ($items) use ($that) {
            $that->dispatch(ClearBucket::AFTER_DELETE, array('keys' => $items));
        });
        $batch = new FlushingBatch(new ExceptionBufferingBatch($batch), 1000);

        // Let any listeners know that the bucket is about to be cleared
        $this->dispatch(self::BEFORE_CLEAR, array(
            'iterator' => $this->getIterator(),
            'batch'    => $batch,
            'mfa'      => $this->mfa
        ));

        $deleted = 0;
        foreach ($this->getIterator() as $object) {
            if (isset($object['VersionId'])) {
                $versionId = $object['VersionId'] == 'null' ? null : $object['VersionId'];
            } else {
                $versionId = null;
            }
            $batch->addKey($object['Key'], $versionId);
            $deleted++;
        }
        $batch->flush();

        // If any errors were encountered, then throw an ExceptionCollection
        if (count($batch->getExceptions())) {
            $e = new ExceptionCollection();
            foreach ($batch->getExceptions() as $exception) {
                $e->add($exception->getPrevious());
            }
            throw $e;
        }

        // Let any listeners know that the bucket was cleared
        $this->dispatch(self::AFTER_CLEAR, array('deleted' => $deleted));

        return $deleted;
    }
}
