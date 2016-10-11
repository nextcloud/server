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

use Aws\Common\Exception\InvalidArgumentException;

/**
 * An object that encapsulates the data for an upload part
 */
abstract class AbstractUploadPart implements UploadPartInterface
{
    /**
     * @var array A map of external array keys to internal property names
     */
    protected static $keyMap = array();

    /**
     * @var int The number of the upload part representing its order in the overall upload
     */
    protected $partNumber;

    /**
     * {@inheritdoc}
     */
    public static function fromArray($data)
    {
        $part = new static();
        $part->loadData($data);

        return $part;
    }

    /**
     * {@inheritdoc}
     */
    public function getPartNumber()
    {
        return $this->partNumber;
    }

    /**
     * {@inheritdoc}
     */
    public function toArray()
    {
        $array = array();
        foreach (static::$keyMap as $key => $property) {
            $array[$key] = $this->{$property};
        }

        return $array;
    }

    /**
     * {@inheritdoc}
     */
    public function serialize()
    {
        return serialize($this->toArray());
    }

    /**
     * {@inheritdoc}
     */
    public function unserialize($serialized)
    {
        $this->loadData(unserialize($serialized));
    }

    /**
     * Loads an array of data into the upload part by extracting only the needed keys
     *
     * @param array|\Traversable $data Data to load into the upload part value object
     *
     * @throws InvalidArgumentException if a required key is missing
     */
    protected function loadData($data)
    {
        foreach (static::$keyMap as $key => $property) {
            if (isset($data[$key])) {
                $this->{$property} = $data[$key];
            } else {
                throw new InvalidArgumentException("A required key [$key] was missing from the upload part.");
            }
        }
    }
}
