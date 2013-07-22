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

namespace Aws\Common\Model\MultipartUpload;

use Aws\Common\Client\AwsClientInterface;
use Aws\Common\Exception\MultipartUploadException;
use Aws\Common\Exception\RuntimeException;
use Guzzle\Common\AbstractHasDispatcher;
use Guzzle\Http\EntityBody;
use Guzzle\Http\EntityBodyInterface;
use Guzzle\Service\Command\OperationCommand;
use Guzzle\Service\Resource\Model;

/**
 * Abstract class for transfer commonalities
 */
abstract class AbstractTransfer extends AbstractHasDispatcher implements TransferInterface
{
    const BEFORE_UPLOAD      = 'multipart_upload.before_upload';
    const AFTER_UPLOAD       = 'multipart_upload.after_upload';
    const BEFORE_PART_UPLOAD = 'multipart_upload.before_part_upload';
    const AFTER_PART_UPLOAD  = 'multipart_upload.after_part_upload';
    const AFTER_ABORT        = 'multipart_upload.after_abort';
    const AFTER_COMPLETE     = 'multipart_upload.after_complete';

    /**
     * @var AwsClientInterface Client used for the transfers
     */
    protected $client;

    /**
     * @var TransferStateInterface State of the transfer
     */
    protected $state;

    /**
     * @var EntityBody Data source of the transfer
     */
    protected $source;

    /**
     * @var array Associative array of options
     */
    protected $options;

    /**
     * @var int Size of each part to upload
     */
    protected $partSize;

    /**
     * @var bool Whether or not the transfer has been stopped
     */
    protected $stopped = false;

    /**
     * Construct a new transfer object
     *
     * @param AwsClientInterface     $client  Client used for the transfers
     * @param TransferStateInterface $state   State used to track transfer
     * @param EntityBody             $source  Data source of the transfer
     * @param array                  $options Array of options to apply
     */
    public function __construct(
        AwsClientInterface $client,
        TransferStateInterface $state,
        EntityBody $source,
        array $options = array()
    ) {
        $this->client  = $client;
        $this->state   = $state;
        $this->source  = $source;
        $this->options = $options;

        $this->init();

        $this->partSize = $this->calculatePartSize();
    }

    public function __invoke()
    {
        return $this->upload();
    }

    /**
     * {@inheritdoc}
     */
    public static function getAllEvents()
    {
        return array(
            self::BEFORE_PART_UPLOAD,
            self::AFTER_UPLOAD,
            self::BEFORE_PART_UPLOAD,
            self::AFTER_PART_UPLOAD,
            self::AFTER_ABORT,
            self::AFTER_COMPLETE
        );
    }

    /**
     * {@inheritdoc}
     */
    public function abort()
    {
        $command = $this->getAbortCommand();
        $result = $command->getResult();

        $this->state->setAborted(true);
        $this->stop();
        $this->dispatch(self::AFTER_ABORT, $this->getEventData($command));

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function stop()
    {
        $this->stopped = true;

        return $this->state;
    }

    /**
     * {@inheritdoc}
     */
    public function getState()
    {
        return $this->state;
    }

    /**
     * Get the array of options associated with the transfer
     *
     * @return array
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * Set an option on the transfer
     *
     * @param string $option Name of the option
     * @param mixed  $value  Value to set
     *
     * @return self
     */
    public function setOption($option, $value)
    {
        $this->options[$option] = $value;

        return $this;
    }

    /**
     * Get the source body of the upload
     *
     * @return EntityBodyInterface
     */
    public function getSource()
    {
        return $this->source;
    }

    /**
     * {@inheritdoc}
     * @throws MultipartUploadException when an error is encountered. Use getLastException() to get more information.
     * @throws RuntimeException         when attempting to upload an aborted transfer
     */
    public function upload()
    {
        if ($this->state->isAborted()) {
            throw new RuntimeException('The transfer has been aborted and cannot be uploaded');
        }

        $this->stopped = false;
        $eventData = $this->getEventData();
        $this->dispatch(self::BEFORE_UPLOAD, $eventData);

        try {
            $this->transfer();
            $this->dispatch(self::AFTER_UPLOAD, $eventData);

            if ($this->stopped) {
                return null;
            } else {
                $result = $this->complete();
                $this->dispatch(self::AFTER_COMPLETE, $eventData);
            }
        } catch (\Exception $e) {
            throw new MultipartUploadException($this->state, $e);
        }

        return $result;
    }

    /**
     * Get an array used for event notifications
     *
     * @param OperationCommand $command Command to include in event data
     *
     * @return array
     */
    protected function getEventData(OperationCommand $command = null)
    {
        $data = array(
            'transfer'  => $this,
            'source'    => $this->source,
            'options'   => $this->options,
            'client'    => $this->client,
            'part_size' => $this->partSize,
            'state'     => $this->state
        );

        if ($command) {
            $data['command'] = $command;
        }

        return $data;
    }

    /**
     * Hook to initialize the transfer
     */
    protected function init() {}

    /**
     * Determine the upload part size based on the size of the source data and
     * taking into account the acceptable minimum and maximum part sizes.
     *
     * @return int The part size
     */
    abstract protected function calculatePartSize();

    /**
     * Complete the multipart upload
     *
     * @return Model Returns the result of the complete multipart upload command
     */
    abstract protected function complete();

    /**
     * Hook to implement in subclasses to perform the actual transfer
     */
    abstract protected function transfer();

    /**
     * Fetches the abort command fom the concrete implementation
     *
     * @return OperationCommand
     */
    abstract protected function getAbortCommand();
}
