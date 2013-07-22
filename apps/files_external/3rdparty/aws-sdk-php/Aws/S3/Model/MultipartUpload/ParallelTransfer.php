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

use Aws\Common\Exception\RuntimeException;
use Aws\Common\Enum\DateFormat;
use Aws\Common\Enum\UaString as Ua;
use Guzzle\Http\EntityBody;
use Guzzle\Http\ReadLimitEntityBody;

/**
 * Transfers multipart upload parts in parallel
 */
class ParallelTransfer extends AbstractTransfer
{
    /**
     * {@inheritdoc}
     */
    protected function init()
    {
        parent::init();

        if (!$this->source->isLocal() || $this->source->getWrapper() != 'plainfile') {
            throw new RuntimeException('The source data must be a local file stream when uploading in parallel.');
        }

        if (empty($this->options['concurrency'])) {
            throw new RuntimeException('The `concurrency` option must be specified when instantiating.');
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function transfer()
    {
        $totalParts  = (int) ceil($this->source->getContentLength() / $this->partSize);
        $concurrency = min($totalParts, $this->options['concurrency']);
        $partsToSend = $this->prepareParts($concurrency);
        $eventData   = $this->getEventData();

        while (!$this->stopped && count($this->state) < $totalParts) {

            $currentTotal = count($this->state);
            $commands = array();

            for ($i = 0; $i < $concurrency && $i + $currentTotal < $totalParts; $i++) {

                // Move the offset to the correct position
                $partsToSend[$i]->setOffset(($currentTotal + $i) * $this->partSize);

                // @codeCoverageIgnoreStart
                if ($partsToSend[$i]->getContentLength() == 0) {
                    break;
                }
                // @codeCoverageIgnoreEnd

                $params = $this->state->getUploadId()->toParams();
                $eventData['command'] = $this->client->getCommand('UploadPart', array_replace($params, array(
                    'PartNumber' => count($this->state) + 1 + $i,
                    'Body'       => $partsToSend[$i],
                    'ContentMD5' => (bool) $this->options['part_md5'],
                    Ua::OPTION   => Ua::MULTIPART_UPLOAD
                )));
                $commands[] = $eventData['command'];
                // Notify any listeners of the part upload
                $this->dispatch(self::BEFORE_PART_UPLOAD, $eventData);
            }

            // Allow listeners to stop the transfer if needed
            if ($this->stopped) {
                break;
            }

            // Execute each command, iterate over the results, and add to the transfer state
            /** @var $command \Guzzle\Service\Command\OperationCommand */
            foreach ($this->client->execute($commands) as $command) {
                $this->state->addPart(UploadPart::fromArray(array(
                    'PartNumber'   => count($this->state) + 1,
                    'ETag'         => $command->getResponse()->getEtag(),
                    'Size'         => (int) $command->getResponse()->getContentLength(),
                    'LastModified' => gmdate(DateFormat::RFC2822)
                )));
                $eventData['command'] = $command;
                // Notify any listeners the the part was uploaded
                $this->dispatch(self::AFTER_PART_UPLOAD, $eventData);
            }
        }
    }

    /**
     * Prepare the entity body handles to use while transferring
     *
     * @param int $concurrency Number of parts to prepare
     *
     * @return array Parts to send
     */
    protected function prepareParts($concurrency)
    {
        $url = $this->source->getUri();
        // Use the source EntityBody as the first part
        $parts = array(new ReadLimitEntityBody($this->source, $this->partSize));
        // Open EntityBody handles for each part to upload in parallel
        for ($i = 1; $i < $concurrency; $i++) {
            $parts[] = new ReadLimitEntityBody(new EntityBody(fopen($url, 'r')), $this->partSize);
        }

        return $parts;
    }
}
