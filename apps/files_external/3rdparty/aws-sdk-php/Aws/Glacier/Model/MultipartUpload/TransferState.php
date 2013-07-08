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

use Aws\Glacier\Model\MultipartUpload\UploadPartGenerator;
use Aws\Common\Client\AwsClientInterface;
use Aws\Common\Model\MultipartUpload\AbstractTransferState;
use Aws\Common\Model\MultipartUpload\UploadIdInterface;

/**
 * State of a multipart upload
 */
class TransferState extends AbstractTransferState
{
    const ALREADY_UPLOADED = '-';

    /**
     * @var UploadPartGenerator Glacier upload helper object that contains part information
     */
    protected $partGenerator;

    /**
     * {@inheritdoc}
     */
    public static function fromUploadId(AwsClientInterface $client, UploadIdInterface $uploadId)
    {
        $transferState = new self($uploadId);
        $listParts = $client->getIterator('ListParts', $uploadId->toParams());

        foreach ($listParts as $part) {
            list($firstByte, $lastByte) = explode('-', $part['RangeInBytes']);
            $partSize = (float) $listParts->getLastResult()->get('PartSizeInBytes');
            $partData = array(
                'partNumber'  => $firstByte / $partSize + 1,
                'checksum'    => $part['SHA256TreeHash'],
                'contentHash' => self::ALREADY_UPLOADED,
                'size'        => $lastByte - $firstByte + 1,
                'offset'      => $firstByte
            );
            $transferState->addPart(UploadPart::fromArray($partData));
        }

        return $transferState;
    }

    /**
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
     * @return UploadPartGenerator Glacier upload helper object
     */
    public function getPartGenerator()
    {
        return $this->partGenerator;
    }
}
