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

namespace Aws\S3\Model\MultipartUpload;

use Aws\Common\Model\MultipartUpload\AbstractUploadPart;

/**
 * An object that encapsulates the data for a Glacier upload operation
 */
class UploadPart extends AbstractUploadPart
{
    /**
     * {@inheritdoc}
     */
    protected static $keyMap = array(
        'PartNumber'   => 'partNumber',
        'ETag'         => 'eTag',
        'LastModified' => 'lastModified',
        'Size'         => 'size'
    );

    /**
     * @var string The ETag for this part
     */
    protected $eTag;

    /**
     * @var string The last modified date
     */
    protected $lastModified;

    /**
     * @var int The size (or content-length) in bytes of the upload body
     */
    protected $size;

    /**
     * @return string
     */
    public function getETag()
    {
        return $this->eTag;
    }

    /**
     * @return string
     */
    public function getLastModified()
    {
        return $this->lastModified;
    }

    /**
     * @return int
     */
    public function getSize()
    {
        return $this->size;
    }
}
