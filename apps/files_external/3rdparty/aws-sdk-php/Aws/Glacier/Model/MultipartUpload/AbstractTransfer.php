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

use Aws\Common\Enum\UaString as Ua;
use Aws\Common\Model\MultipartUpload\AbstractTransfer as CommonAbstractTransfer;
use Aws\Glacier\Model\MultipartUpload\TransferState;
use Guzzle\Http\EntityBody;
use Guzzle\Http\ReadLimitEntityBody;
use Guzzle\Service\Command\OperationCommand;

/**
 * Abstract class for transfer commonalities
 */
abstract class AbstractTransfer extends CommonAbstractTransfer
{
    /**
     * @var TransferState Glacier transfer state
     */
    protected $state;

    /**
     * {@inheritdoc}
     */
    protected function calculatePartSize()
    {
        return $this->state->getPartGenerator()->getPartSize();
    }

    /**
     * {@inheritdoc}
     */
    protected function complete()
    {
        $partGenerator = $this->state->getPartGenerator();

        $params = array_replace($this->state->getUploadId()->toParams(), array(
            'archiveSize' => $partGenerator->getArchiveSize(),
            'checksum'    => $partGenerator->getRootChecksum(),
            Ua::OPTION    => Ua::MULTIPART_UPLOAD
        ));
        $command = $this->client->getCommand('CompleteMultipartUpload', $params);

        return $command->getResult();
    }

    /**
     * {@inheritdoc}
     */
    protected function getAbortCommand()
    {
        $params = $this->state->getUploadId()->toParams();
        $params[Ua::OPTION] = Ua::MULTIPART_UPLOAD;

        /** @var $command OperationCommand */
        $command = $this->client->getCommand('AbortMultipartUpload', $params);

        return $command;
    }

    /**
     * Creates an UploadMultipartPart command from an UploadPart object
     *
     * @param UploadPart $part          UploadPart for which to create a command
     * @param bool       $useSourceCopy Whether or not to use the original source or a copy of it
     *
     * @return OperationCommand
     */
    protected function getCommandForPart(UploadPart $part, $useSourceCopy = false)
    {
        // Setup the command with identifying parameters (accountId, vaultName, and uploadId)
        /** @var $command OperationCommand */
        $command = $this->client->getCommand('UploadMultipartPart', $this->state->getUploadId()->toParams());
        $command->set(Ua::OPTION, Ua::MULTIPART_UPLOAD);

        // Get the correct source
        $source = $this->source;
        if ($useSourceCopy) {
            $sourceUri = $this->source->getUri();
            $source = new EntityBody(fopen($sourceUri, 'r'));
        }

        // Add the range, checksum, and the body limited by the range
        $command->set('range', $part->getFormattedRange());
        $command->set('checksum', $part->getChecksum());
        $command->set('ContentSHA256', $part->getContentHash());
        $command->set('body', new ReadLimitEntityBody($source, $part->getSize(), $part->getOffset()));

        return $command;
    }
}
