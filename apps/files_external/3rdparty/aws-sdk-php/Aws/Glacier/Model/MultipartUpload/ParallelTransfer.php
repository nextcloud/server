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

use Aws\Common\Exception\RuntimeException;
use Guzzle\Iterator\ChunkedIterator;

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
        /** @var $parts UploadPartGenerator */
        $parts     = $this->state->getPartGenerator();
        $chunkSize = min($this->options['concurrency'], count($parts));
        $partSets  = new ChunkedIterator($parts, $chunkSize);

        foreach ($partSets as $partSet) {
            /** @var $part UploadPart */
            $commands = array();
            foreach ($partSet as $index => $part) {
                $command = $this->getCommandForPart($part, (bool) $index)->set('part', $part);
                $this->dispatch(self::BEFORE_PART_UPLOAD, $this->getEventData($command));
                $commands[] = $command;
            }

            // Allow listeners to stop the transfer if needed
            if ($this->stopped) {
                break;
            }

            // Execute each command, iterate over the results, and add to the transfer state
            /** @var $command \Guzzle\Service\Command\OperationCommand */
            foreach ($this->client->execute($commands) as $command) {
                $this->state->addPart($command->get('part'));
                $this->dispatch(self::AFTER_PART_UPLOAD, $this->getEventData($command));
            }
        }
    }
}
