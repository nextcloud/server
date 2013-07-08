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

use Aws\Common\Enum\Size;
use Aws\Common\Enum\UaString as Ua;
use Aws\Common\Exception\InvalidArgumentException;
use Aws\Common\Model\MultipartUpload\AbstractUploadBuilder;
use Aws\Common\Model\MultipartUpload\TransferStateInterface as State;
use Aws\Glacier\Model\MultipartUpload\UploadPartGenerator;

/**
 * Easily create a multipart uploader used to quickly and reliably upload a
 * large file or data stream to Amazon Glacier using multipart uploads
 */
class UploadBuilder extends AbstractUploadBuilder
{
    /**
     * @var string Account ID to upload to
     */
    protected $accountId = '-';

    /**
     * @var string Name of the vault to upload to
     */
    protected $vaultName;

    /**
     * @var int Concurrency level to transfer the parts
     */
    protected $concurrency = 1;

    /**
     * @var int Size of upload parts
     */
    protected $partSize;

    /**
     * @var string Archive description
     */
    protected $archiveDescription;

    /**
     * @var UploadPartGenerator Glacier upload helper object
     */
    protected $partGenerator;

    /**
     * Set the account ID to upload the part to
     *
     * @param string $accountId ID of the account
     *
     * @return self
     */
    public function setAccountId($accountId)
    {
        $this->accountId = $accountId;

        return $this;
    }

    /**
     * Set the vault name to upload the part to
      *
      * @param string $vaultName Name of the vault
      *
      * @return self
     */
    public function setVaultName($vaultName)
    {
        $this->vaultName = $vaultName;

        return $this;
    }

    /**
     * Set the upload part size
     *
     * @param int $partSize Upload part size
     *
     * @return self
     */
    public function setPartSize($partSize)
    {
        $this->partSize = (int) $partSize;

        return $this;
    }

    /**
     * Set the archive description
      *
      * @param string $archiveDescription Archive description
      *
      * @return self
     */
    public function setArchiveDescription($archiveDescription)
    {
        $this->archiveDescription = $archiveDescription;

        return $this;
    }

    /**
     * Set the concurrency level to use when uploading parts. This affects how many parts are uploaded in parallel. You
     * must use a local file as your data source when using a concurrency greater than 1
     *
     * @param int $concurrency Concurrency level
     *
     * @return self
     */
    public function setConcurrency($concurrency)
    {
        $this->concurrency = $concurrency;

        return $this;
    }

    /**
     * Sets the Glacier upload helper object that pre-calculates hashes and sizes for all upload parts
     *
     * @param UploadPartGenerator $partGenerator Glacier upload helper object
     *
     * @return self
     */
    public function setPartGenerator(UploadPartGenerator $partGenerator)
    {
        $this->partGenerator = $partGenerator;

        return $this;
    }

    /**
     * {@inheritdoc}
     * @throws InvalidArgumentException when attempting to resume a transfer using a non-seekable stream
     * @throws InvalidArgumentException when missing required properties (bucket, key, client, source)
     */
    public function build()
    {
        // If a Glacier upload helper object was set, use the source and part size from it
        if ($this->partGenerator) {
            $this->partSize = $this->partGenerator->getPartSize();
        }

        if (!($this->state instanceof State) && !$this->vaultName || !$this->client || !$this->source) {
            throw new InvalidArgumentException('You must specify a vault name, client, and source.');
        }

        if (!$this->source->isSeekable()) {
            throw new InvalidArgumentException('You cannot upload from a non-seekable source.');
        }

        // If no state was set, then create one by initiating or loading a multipart upload
        if (is_string($this->state)) {
            if (!$this->partGenerator) {
                throw new InvalidArgumentException('You must provide an UploadPartGenerator when resuming an upload.');
            }
            /** @var $state \Aws\Glacier\Model\MultipartUpload\TransferState */
            $this->state = TransferState::fromUploadId($this->client, UploadId::fromParams(array(
                'accountId' => $this->accountId,
                'vaultName' => $this->vaultName,
                'uploadId'  => $this->state
            )));
            $this->state->setPartGenerator($this->partGenerator);
        } elseif (!$this->state) {
            $this->state = $this->initiateMultipartUpload();
        }

        $options = array(
            'concurrency' => $this->concurrency
        );

        return $this->concurrency > 1
            ? new ParallelTransfer($this->client, $this->state, $this->source, $options)
            : new SerialTransfer($this->client, $this->state, $this->source, $options);
    }

    /**
     * {@inheritdoc}
     */
    protected function initiateMultipartUpload()
    {
        $params = array(
            'accountId' => $this->accountId,
            'vaultName' => $this->vaultName
        );

        $partGenerator = $this->partGenerator ?: UploadPartGenerator::factory($this->source, $this->partSize);

        $command = $this->client->getCommand('InitiateMultipartUpload', array_replace($params, array(
            'command.headers'    => $this->headers,
            'partSize'           => $partGenerator->getPartSize(),
            'archiveDescription' => $this->archiveDescription,
            Ua::OPTION           => Ua::MULTIPART_UPLOAD
        )));
        $params['uploadId'] = $command->getResult()->get('uploadId');

        // Create a new state based on the initiated upload
        $state = new TransferState(UploadId::fromParams($params));
        $state->setPartGenerator($partGenerator);

        return $state;
    }
}
