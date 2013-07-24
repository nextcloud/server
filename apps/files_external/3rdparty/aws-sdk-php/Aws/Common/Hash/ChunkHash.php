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

namespace Aws\Common\Hash;

use Aws\Common\Exception\LogicException;

/**
 * Encapsulates the creation of a hash from streamed chunks of data
 */
class ChunkHash implements ChunkHashInterface
{
    /**
     * @var resource The hash context as created by `hash_init()`
     */
    protected $context;

    /**
     * @var string The resulting hash in hex form
     */
    protected $hash;

    /**
     * @var string The resulting hash in binary form
     */
    protected $hashRaw;

    /**
     * {@inheritdoc}
     */
    public function __construct($algorithm = self::DEFAULT_ALGORITHM)
    {
        HashUtils::validateAlgorithm($algorithm);
        $this->context = hash_init($algorithm);
    }

    /**
     * {@inheritdoc}
     */
    public function addData($data)
    {
        if (!$this->context) {
            throw new LogicException('You may not add more data to a finalized chunk hash.');
        }

        hash_update($this->context, $data);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getHash($returnBinaryForm = false)
    {
        if (!$this->hash) {
            $this->hashRaw = hash_final($this->context, true);
            $this->hash = HashUtils::binToHex($this->hashRaw);
            $this->context = null;
        }

        return $returnBinaryForm ? $this->hashRaw : $this->hash;
    }

    /**
     * {@inheritdoc}
     */
    public function __clone()
    {
        if ($this->context) {
            $this->context = hash_copy($this->context);
        }
    }
}
