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
use Aws\Common\Model\MultipartUpload\AbstractTransfer;
use Aws\S3\ResumableDownload;
use Aws\S3\S3Client;
use Guzzle\Common\Event;
use Guzzle\Http\EntityBodyInterface;
use Guzzle\Service\Command\CommandInterface;

class DownloadSyncBuilder extends AbstractSyncBuilder
{
    /** @var bool */
    protected $resumable = false;

    /** @var string */
    protected $directory;

    /** @var int Number of files that can be transferred concurrently */
    protected $concurrency = 5;

    /**
     * Set the directory where the objects from be downloaded to
     *
     * @param string $directory Directory
     *
     * @return self
     */
    public function setDirectory($directory)
    {
        $this->directory = $directory;

        return $this;
    }

    /**
     * Call this function to allow partial downloads to be resumed if the download was previously interrupted
     *
     * @return self
     */
    public function allowResumableDownloads()
    {
        $this->resumable = true;

        return $this;
    }

    protected function specificBuild()
    {
        $sync = new DownloadSync(array(
            'client'           => $this->client,
            'bucket'           => $this->bucket,
            'iterator'         => $this->sourceIterator,
            'source_converter' => $this->sourceConverter,
            'target_converter' => $this->targetConverter,
            'concurrency'      => $this->concurrency,
            'resumable'        => $this->resumable,
            'directory'        => $this->directory
        ));

        return $sync;
    }

    protected function getTargetIterator()
    {
        if (!$this->directory) {
            throw new RuntimeException('A directory is required');
        }

        if (!is_dir($this->directory) && !mkdir($this->directory, 0777, true)) {
            // @codeCoverageIgnoreStart
            throw new RuntimeException('Unable to create root download directory: ' . $this->directory);
            // @codeCoverageIgnoreEnd
        }

        return $this->filterIterator(
            new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($this->directory))
        );
    }

    protected function getDefaultSourceConverter()
    {
        return new KeyConverter(
            "s3://{$this->bucket}/{$this->baseDir}",
            $this->directory . DIRECTORY_SEPARATOR, $this->delimiter
        );
    }

    protected function getDefaultTargetConverter()
    {
        return new KeyConverter("s3://{$this->bucket}/{$this->baseDir}", '', $this->delimiter);
    }

    protected function assertFileIteratorSet()
    {
        $this->sourceIterator = $this->sourceIterator ?: $this->createS3Iterator();
    }

    protected function addDebugListener(AbstractSync $sync, $resource)
    {
        $sync->getEventDispatcher()->addListener(UploadSync::BEFORE_TRANSFER, function (Event $e) use ($resource) {
            if ($e['command'] instanceof CommandInterface) {
                $from = $e['command']['Bucket'] . '/' . $e['command']['Key'];
                $to = $e['command']['SaveAs'] instanceof EntityBodyInterface
                    ? $e['command']['SaveAs']->getUri()
                    : $e['command']['SaveAs'];
                fwrite($resource, "Downloading {$from} -> {$to}\n");
            } elseif ($e['command'] instanceof ResumableDownload) {
                $from = $e['command']->getBucket() . '/' . $e['command']->getKey();
                $to = $e['command']->getFilename();
                fwrite($resource, "Resuming {$from} -> {$to}\n");
            }
        });
    }
}
