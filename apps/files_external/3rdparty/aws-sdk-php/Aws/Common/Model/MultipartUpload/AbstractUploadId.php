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
 * An object that encapsulates the data identifying an upload
 */
abstract class AbstractUploadId implements UploadIdInterface
{
    /**
     * @var array Expected values (with defaults)
     */
    protected static $expectedValues = array();

    /**
     * @var array Params representing the identifying information
     */
    protected $data = array();

    /**
     * {@inheritdoc}
     */
    public static function fromParams($data)
    {
        $uploadId = new static();
        $uploadId->loadData($data);

        return $uploadId;
    }

    /**
     * {@inheritdoc}
     */
    public function toParams()
    {
        return $this->data;
    }

    /**
     * {@inheritdoc}
     */
    public function serialize()
    {
        return serialize($this->data);
    }

    /**
     * {@inheritdoc}
     */
    public function unserialize($serialized)
    {
        $this->loadData(unserialize($serialized));
    }

    /**
     * Loads an array of data into the UploadId by extracting only the needed keys
     *
     * @param array $data Data to load
     *
     * @throws InvalidArgumentException if a required key is missing
     */
    protected function loadData($data)
    {
        $data = array_replace(static::$expectedValues, array_intersect_key($data, static::$expectedValues));
        foreach ($data as $key => $value) {
            if (isset($data[$key])) {
                $this->data[$key] = $data[$key];
            } else {
                throw new InvalidArgumentException("A required key [$key] was missing from the UploadId.");
            }
        }
    }
}
