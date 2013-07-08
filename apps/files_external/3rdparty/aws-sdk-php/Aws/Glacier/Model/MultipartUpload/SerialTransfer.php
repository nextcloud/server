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

/**
 * Transfers multipart upload parts serially
 */
class SerialTransfer extends AbstractTransfer
{
    /**
     * {@inheritdoc}
     */
    protected function transfer()
    {
        /** @var $partGenerator UploadPartGenerator */
        $partGenerator = $this->state->getPartGenerator();

        /** @var $part UploadPart */
        foreach ($partGenerator as $part) {
            $command = $this->getCommandForPart($part);

            // Notify observers that the part is about to be uploaded
            $eventData = $this->getEventData($command);
            $this->dispatch(self::BEFORE_PART_UPLOAD, $eventData);

            // Allow listeners to stop the transfer if needed
            if ($this->stopped) {
                break;
            }

            $command->execute();
            $this->state->addPart($part);

            // Notify observers that the part was uploaded
            $this->dispatch(self::AFTER_PART_UPLOAD, $eventData);
        }
    }
}
