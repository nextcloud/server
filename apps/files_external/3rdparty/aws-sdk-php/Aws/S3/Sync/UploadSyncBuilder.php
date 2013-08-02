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

use \FilesystemIterator as FI;
use Aws\Common\Model\MultipartUpload\AbstractTransfer;
use Aws\S3\Model\Acp;
use Aws\S3\S3Client;
use Guzzle\Common\Event;
use Guzzle\Service\Command\CommandInterface;

class UploadSyncBuilder extends AbstractSyncBuilder
{
    /** @var string|Acp Access control policy to set on each object */
    protected $acp = 'private';

    /** @var int */
    protected $multipartUploadSize;

    /**
     * Set the path that contains files to recursively upload to Amazon S3
     *
     * @param string $path Path that contains files to upload
     *
     * @return self
     */
    public function uploadFromDirectory($path)
    {
        $this->baseDir = $path;
        $this->sourceIterator = $this->filterIterator(new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(
            $path,
            FI::SKIP_DOTS | FI::UNIX_PATHS | FI::FOLLOW_SYMLINKS
        )));

        return $this;
    }

    /**
     * Set a glob expression that will match files to upload to Amazon S3
     *
     * @param string $glob Glob expression
     *
     * @return self
     * @link http://www.php.net/manual/en/function.glob.php
     */
    public function uploadFromGlob($glob)
    {
        $this->sourceIterator = $this->filterIterator(
            new \GlobIterator($glob, FI::SKIP_DOTS | FI::UNIX_PATHS | FI::FOLLOW_SYMLINKS)
        );

        return $this;
    }

    /**
     * Set a canned ACL to apply to each uploaded object
     *
     * @param string $acl Canned ACL for each upload
     *
     * @return self
     */
    public function setAcl($acl)
    {
        $this->acp = $acl;

        return $this;
    }

    /**
     * Set an Access Control Policy to apply to each uploaded object
     *
     * @param Acp $acp Access control policy
     *
     * @return self
     */
    public function setAcp(Acp $acp)
    {
        $this->acp = $acp;

        return $this;
    }

    /**
     * Set the multipart upload size threshold. When the size of a file exceeds this value, the file will be uploaded
     * using a multipart upload.
     *
     * @param int $size Size threshold
     *
     * @return self
     */
    public function setMultipartUploadSize($size)
    {
        $this->multipartUploadSize = $size;

        return $this;
    }

    protected function specificBuild()
    {
        $sync = new UploadSync(array(
            'client' => $this->client,
            'bucket' => $this->bucket,
            'iterator' => $this->sourceIterator,
            'source_converter' => $this->sourceConverter,
            'target_converter' => $this->targetConverter,
            'concurrency' => $this->concurrency,
            'multipart_upload_size' => $this->multipartUploadSize,
            'acl' => $this->acp
        ));

        return $sync;
    }

    protected function getTargetIterator()
    {
        return $this->createS3Iterator();
    }

    protected function getDefaultSourceConverter()
    {
        return new KeyConverter($this->baseDir, $this->keyPrefix . $this->delimiter, $this->delimiter);
    }

    protected function getDefaultTargetConverter()
    {
        return new KeyConverter('s3://' . $this->bucket . '/', '', DIRECTORY_SEPARATOR);
    }

    protected function addDebugListener(AbstractSync $sync, $resource)
    {
        $sync->getEventDispatcher()->addListener(UploadSync::BEFORE_TRANSFER, function (Event $e) use ($resource) {

            $c = $e['command'];

            if ($c instanceof CommandInterface) {
                $uri = $c['Body']->getUri();
                $size = $c['Body']->getSize();
                fwrite($resource, "Uploading {$uri} -> {$c['Key']} ({$size} bytes)\n");
                return;
            }

            // Multipart upload
            $body = $c->getSource();
            $totalSize = $body->getSize();
            $progress = 0;
            fwrite($resource, "Beginning multipart upload: " . $body->getUri() . ' -> ');
            fwrite($resource, $c->getState()->getFromId('Key') . " ({$totalSize} bytes)\n");

            $c->getEventDispatcher()->addListener(
                AbstractTransfer::BEFORE_PART_UPLOAD,
                function ($e) use (&$progress, $totalSize, $resource) {
                    $command = $e['command'];
                    $size = $command['Body']->getContentLength();
                    $percentage = number_format(($progress / $totalSize) * 100, 2);
                    fwrite($resource, "- Part {$command['PartNumber']} ({$size} bytes, {$percentage}%)\n");
                    $progress .=  $size;
                }
            );
        });
    }
}
