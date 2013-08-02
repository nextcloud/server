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

namespace Aws\S3\Sync;

use Aws\Common\Exception\RuntimeException;
use Aws\S3\S3Client;
use Aws\S3\Model\MultipartUpload\UploadBuilder;
use Aws\S3\Model\MultipartUpload\AbstractTransfer;
use Guzzle\Http\EntityBody;

/**
 * Uploads a local directory tree to Amazon S3
 */
class UploadSync extends AbstractSync
{
    protected function init()
    {
        if (null == $this->options['multipart_upload_size']) {
            $this->options['multipart_upload_size'] = AbstractTransfer::MIN_PART_SIZE;
        }
    }

    protected function createTransferAction(\SplFileInfo $file)
    {
        // Open the file for reading
        $filename = $file->getPathName();
        if (!($resource = fopen($filename, 'r'))) {
            // @codeCoverageIgnoreStart
            throw new RuntimeException("Could not open {$filename} for reading");
            // @codeCoverageIgnoreEnd
        }

        $key = $this->options['source_converter']->convert($filename);
        $body = EntityBody::factory($resource);

        // Determine how the ACL should be applied
        if ($acl = $this->options['acl']) {
            $aclType = is_string($this->options['acl']) ? 'ACL' : 'ACP';
        } else {
            $acl = 'private';
            $aclType = 'ACL';
        }

        // Use a multi-part upload if the file is larger than the cutoff size and is a regular file
        if ($body->getWrapper() == 'plainfile' && $file->getSize() >= $this->options['multipart_upload_size']) {
            return UploadBuilder::newInstance()
                ->setBucket($this->options['bucket'])
                ->setKey($key)
                ->setMinPartSize($this->options['multipart_upload_size'])
                ->setOption($aclType, $acl)
                ->setClient($this->options['client'])
                ->setSource($body)
                ->setConcurrency($this->options['concurrency'])
                ->build();
        }

        return $this->options['client']->getCommand('PutObject', array(
            'Bucket' => $this->options['bucket'],
            'Key'    => $key,
            'Body'   => $body,
            $aclType => $acl
        ));
    }
}
