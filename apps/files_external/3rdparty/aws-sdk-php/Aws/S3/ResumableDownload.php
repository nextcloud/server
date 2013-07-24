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

namespace Aws\S3;

use Aws\Common\Exception\RuntimeException;
use Aws\Common\Exception\UnexpectedValueException;
use Guzzle\Http\EntityBody;
use Guzzle\Http\ReadLimitEntityBody;
use Guzzle\Http\EntityBodyInterface;
use Guzzle\Service\Resource\Model;

/**
 * Allows you to resume the download of a partially downloaded object.
 *
 * Downloads objects from Amazon S3 in using "Range" downloads. This allows a partially downloaded object to be resumed
 * so that only the remaining portion of the object is downloaded.
 */
class ResumableDownload
{
    /** @var S3Client The S3 client to use to download objects and issue HEAD requests */
    protected $client;

    /** @var \Guzzle\Service\Resource\Model Model object returned when the initial HeadObject operation was called */
    protected $meta;

    /** @var array Array of parameters to pass to a GetObject operation */
    protected $params;

    /** @var \Guzzle\Http\EntityBody Where the object will be downloaded */
    protected $target;

    /**
     * @param S3Client                            $client Client to use when executing requests
     * @param string                              $bucket Bucket that holds the object
     * @param string                              $key    Key of the object
     * @param string|resource|EntityBodyInterface $target Where the object should be downloaded to. Pass a string to
     *                                                    save the object to a file, pass a resource returned by
     *                                                    fopen() to save the object to a stream resource, or pass a
     *                                                    Guzzle EntityBody object to save the contents to an
     *                                                    EntityBody.
     * @param array                               $params Any additional GetObject or HeadObject parameters to use
     *                                                    with each command issued by the client. (e.g. pass "Version"
     *                                                    to download a specific version of an object)
     * @throws RuntimeException if the target variable points to a file that cannot be opened
     */
    public function __construct(S3Client $client, $bucket, $key, $target, array $params = array())
    {
        $this->params = $params;
        $this->client = $client;
        $this->params['Bucket'] = $bucket;
        $this->params['Key'] = $key;

        // If a string is passed, then assume that the download should stream to a file on disk
        if (is_string($target)) {
            if (!($target = fopen($target, 'a+'))) {
                throw new RuntimeException("Unable to open {$target} for writing");
            }
            // Always append to the file
            fseek($target, 0, SEEK_END);
        }

        // Get the metadata and Content-MD5 of the object
        $this->target = EntityBody::factory($target);
    }

    /**
     * Get the bucket of the download
     *
     * @return string
     */
    public function getBucket()
    {
        return $this->params['Bucket'];
    }

    /**
     * Get the key of the download
     *
     * @return string
     */
    public function getKey()
    {
        return $this->params['Key'];
    }

    /**
     * Get the file to which the contents are downloaded
     *
     * @return string
     */
    public function getFilename()
    {
        return $this->target->getUri();
    }

    /**
     * Download the remainder of the object from Amazon S3
     *
     * Performs a message integrity check if possible
     *
     * @return Model
     */
    public function __invoke()
    {
        $command = $this->client->getCommand('HeadObject', $this->params);
        $this->meta = $command->execute();

        if ($this->target->ftell() >= $this->meta['ContentLength']) {
            return false;
        }

        $this->meta['ContentMD5'] = (string) $command->getResponse()->getHeader('Content-MD5');

        // Use a ReadLimitEntityBody so that rewinding the stream after an error does not cause the file pointer
        // to enter an inconsistent state with the data being downloaded
        $this->params['SaveAs'] = new ReadLimitEntityBody(
            $this->target,
            $this->meta['ContentLength'],
            $this->target->ftell()
        );

        $result = $this->getRemaining();
        $this->checkIntegrity();

        return $result;
    }

    /**
     * Send the command to get the remainder of the object
     *
     * @return Model
     */
    protected function getRemaining()
    {
        $current = $this->target->ftell();
        $targetByte = $this->meta['ContentLength'] - 1;
        $this->params['Range'] = "bytes={$current}-{$targetByte}";

        // Set the starting offset so that the body is never seeked to before this point in the event of a retry
        $this->params['SaveAs']->setOffset($current);
        $command = $this->client->getCommand('GetObject', $this->params);

        return $command->execute();
    }

    /**
     * Performs an MD5 message integrity check if possible
     *
     * @throws UnexpectedValueException if the message does not validate
     */
    protected function checkIntegrity()
    {
        if ($this->target->isReadable() && $expected = $this->meta['ContentMD5']) {
            $actual = $this->target->getContentMd5();
            if ($actual != $expected) {
                throw new UnexpectedValueException(
                    "Message integrity check failed. Expected {$expected} but got {$actual}."
                );
            }
        }
    }
}
