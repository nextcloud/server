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

use Aws\S3\S3Client;
use Aws\S3\Model\MultipartUpload\AbstractTransfer;
use Guzzle\Common\AbstractHasDispatcher;
use Guzzle\Common\Collection;
use Guzzle\Http\EntityBody;
use Guzzle\Iterator\ChunkedIterator;
use Guzzle\Service\Command\CommandInterface;

abstract class AbstractSync extends AbstractHasDispatcher
{
    const BEFORE_TRANSFER = 's3.sync.before_transfer';
    const AFTER_TRANSFER = 's3.sync.after_transfer';

    /** @var Collection */
    protected $options;

    /**
     * @param array $options Associative array of options:
     *     - client: (S3Client) used to transfer requests
     *     - bucket: (string) Amazon S3 bucket
     *     - iterator: (\Iterator) Iterator that yields SplFileInfo objects to transfer
     *     - source_converter: (FilenameConverterInterface) Converter used to convert filenames
     *     - *: Any other options required by subclasses
     */
    public function __construct(array $options)
    {
        $this->options = Collection::fromConfig(
            $options,
            array('concurrency' => 10),
            array('client', 'bucket', 'iterator', 'source_converter')
        );
        $this->init();
    }

    public static function getAllEvents()
    {
        return array(self::BEFORE_TRANSFER, self::AFTER_TRANSFER);
    }

    /**
     * Begin transferring files
     */
    public function transfer()
    {
        // Pull out chunks of uploads to upload in parallel
        $iterator = new ChunkedIterator($this->options['iterator'], $this->options['concurrency']);
        foreach ($iterator as $files) {
            $this->transferFiles($files);
        }
    }

    /**
     * Create a command or special transfer action for the
     *
     * @param \SplFileInfo $file File used to build the transfer
     *
     * @return CommandInterface|callable
     */
    abstract protected function createTransferAction(\SplFileInfo $file);

    /**
     * Hook to initialize subclasses
     * @codeCoverageIgnore
     */
    protected function init() {}

    /**
     * Process and transfer a group of files
     *
     * @param array $files Files to transfer
     */
    protected function transferFiles(array $files)
    {
        // Create the base event data object
        $event = array('sync' => $this, 'client' => $this->options['client']);

        $commands = array();
        foreach ($files as $file) {
            if ($action = $this->createTransferAction($file)) {
                $event = array('command' => $action, 'file' => $file) + $event;
                $this->dispatch(self::BEFORE_TRANSFER, $event);
                if ($action instanceof CommandInterface) {
                    $commands[] = $action;
                } elseif (is_callable($action)) {
                    $action();
                    $this->dispatch(self::AFTER_TRANSFER, $event);
                }
            }
        }

        $this->transferCommands($commands);
    }

    /**
     * Transfer an array of commands in parallel
     *
     * @param array $commands Commands to transfer
     */
    protected function transferCommands(array $commands)
    {
        if ($commands) {
            $this->options['client']->execute($commands);
            // Notify listeners that each command finished
            $event = array('sync' => $this, 'client' => $this->options['client']);
            foreach ($commands as $command) {
                $event['command'] = $command;
                $this->dispatch(self::AFTER_TRANSFER, $event);
            }
        }
    }
}
