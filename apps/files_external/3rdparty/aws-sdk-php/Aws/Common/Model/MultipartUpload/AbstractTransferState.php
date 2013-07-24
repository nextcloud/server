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

namespace Aws\Common\Model\MultipartUpload;

use Aws\Common\Exception\RuntimeException;

/**
 * State of a multipart upload
 */
abstract class AbstractTransferState implements TransferStateInterface
{
    /**
     * @var UploadIdInterface Object holding params used to identity the upload part
     */
    protected $uploadId;

    /**
     * @var array Array of parts where the part number is the index
     */
    protected $parts = array();

    /**
     * @var bool Whether or not the transfer was aborted
     */
    protected $aborted = false;

    /**
     * Construct a new transfer state object
     *
     * @param UploadIdInterface $uploadId Upload identifier object
     */
    public function __construct(UploadIdInterface $uploadId)
    {
        $this->uploadId = $uploadId;
    }

    /**
     * {@inheritdoc}
     */
    public function getUploadId()
    {
        return $this->uploadId;
    }

    /**
     * Get a data value from the transfer state's uploadId
     *
     * @param string $key Key to retrieve (e.g. Bucket, Key, UploadId, etc)
     *
     * @return string|null
     */
    public function getFromId($key)
    {
        $params = $this->uploadId->toParams();

        return isset($params[$key]) ? $params[$key] : null;
    }

    /**
     * {@inheritdoc}
     */
    public function getPart($partNumber)
    {
        return isset($this->parts[$partNumber]) ? $this->parts[$partNumber] : null;
    }

    /**
     * {@inheritdoc}
     */
    public function addPart(UploadPartInterface $part)
    {
        $partNumber = $part->getPartNumber();
        $this->parts[$partNumber] = $part;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function hasPart($partNumber)
    {
        return isset($this->parts[$partNumber]);
    }

    /**
     * {@inheritdoc}
     */
    public function getPartNumbers()
    {
        return array_keys($this->parts);
    }

    /**
     * {@inheritdoc}
     */
    public function setAborted($aborted)
    {
        $this->aborted = (bool) $aborted;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function isAborted()
    {
        return $this->aborted;
    }

    /**
     * {@inheritdoc}
     */
    public function count()
    {
        return count($this->parts);
    }

    /**
     * {@inheritdoc}
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->parts);
    }

    /**
     * {@inheritdoc}
     */
    public function serialize()
    {
        return serialize(get_object_vars($this));
    }

    /**
     * {@inheritdoc}
     */
    public function unserialize($serialized)
    {
        $data = unserialize($serialized);
        foreach (get_object_vars($this) as $property => $oldValue) {
            if (array_key_exists($property, $data)) {
                $this->{$property} = $data[$property];
            } else {
                throw new RuntimeException("The {$property} property could be restored during unserialization.");
            }
        }
    }
}
