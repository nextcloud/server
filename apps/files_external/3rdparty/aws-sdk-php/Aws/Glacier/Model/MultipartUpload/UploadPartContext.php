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

namespace Aws\Glacier\Model\MultipartUpload;

use Aws\Common\Hash\ChunkHash;
use Aws\Common\Hash\TreeHash;
use Aws\Common\Exception\LogicException;

/**
 * An object that encapsulates the data for a Glacier upload operation
 */
class UploadPartContext
{
    /**
     * @var TreeHash Tree hash context of the data
     */
    protected $treeHash;

    /**
     * @var ChunkHash Chunk hash context of the data
     */
    protected $chunkHash;

    /**
     * @var int The size (or content-length) in bytes of the upload body
     */
    protected $size;

    /**
     * @var int The starting offset byte of the upload body
     */
    protected $offset;

    /**
     * @var int The maximum size of the upload in bytes
     */
    protected $maxSize;

    /**
     * @var UploadPart The calculated upload part
     */
    protected $uploadPart;

    /**
     * @param int $maxSize Maximum allowed size of this upload context
     * @param int $offset  The starting offset byte of the upload body
     */
    public function __construct($maxSize, $offset = 0)
    {
        $this->maxSize = $maxSize;
        $this->offset  = $offset;
        $this->size    = 0;

        $this->treeHash  = new TreeHash();
        $this->chunkHash = new ChunkHash();
    }

    /**
     * Adds data to the context. This adds data to both the tree and chunk hashes and increases the size
     *
     * @param string $data Data to add to the context
     *
     * @return self
     * @throws LogicException when the context is already finalized
     */
    public function addData($data)
    {
        $size = strlen($data);

        if ($this->size + $size > $this->maxSize) {
            throw new LogicException('You cannot add data that will exceed the maximum size of this upload.');
        }

        try {
            $this->treeHash->addData($data);
            $this->chunkHash->addData($data);
            $this->size += $size;
        } catch (LogicException $e) {
            throw new LogicException('You cannot add data to a finalized UploadPartContext.', 0, $e);
        }

        return $this;
    }

    /**
     * Finalizes the context by calculating the final hashes and generates an upload part object
     *
     * @return UploadPart
     */
    public function generatePart()
    {
        if (!$this->uploadPart) {
            $this->uploadPart = UploadPart::fromArray(array(
                'partNumber'  => (int) ($this->offset / $this->maxSize + 1),
                'checksum'    => $this->treeHash->getHash(),
                'contentHash' => $this->chunkHash->getHash(),
                'size'        => $this->size,
                'offset'      => $this->offset
            ));
        }

        return $this->uploadPart;
    }

    /**
     * Checks if the size of the context is the same as the maximum size
     *
     * @return bool
     */
    public function isFull()
    {
        return $this->size === $this->maxSize;
    }

    /**
     * Checks if the size of the context is 0
     *
     * @return bool
     */
    public function isEmpty()
    {
        return $this->size === 0;
    }
}
