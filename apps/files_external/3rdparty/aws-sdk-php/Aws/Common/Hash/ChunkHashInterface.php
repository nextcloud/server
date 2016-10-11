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

/**
 * Interface for objects that encapsulate the creation of a hash from streamed chunks of data
 */
interface ChunkHashInterface
{
    const DEFAULT_ALGORITHM = 'sha256';

    /**
     * Constructs the chunk hash and sets the algorithm to use for hashing
     *
     * @param string $algorithm A valid hash algorithm name as returned by `hash_algos()`
     *
     * @return self
     */
    public function __construct($algorithm = 'sha256');

    /**
     * Add a chunk of data to be hashed
     *
     * @param string $data Data to be hashed
     *
     * @return self
     */
    public function addData($data);

    /**
     * Return the results of the hash
     *
     * @param bool $returnBinaryForm If true, returns the hash in binary form instead of hex form
     *
     * @return string
     */
    public function getHash($returnBinaryForm = false);
}
