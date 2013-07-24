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

namespace Aws\S3\Model;

use Aws\Common\Client\AwsClientInterface;
use Aws\Common\Exception\OverflowException;
use Aws\Common\Enum\UaString as Ua;
use Aws\S3\Exception\InvalidArgumentException;
use Aws\S3\Exception\DeleteMultipleObjectsException;
use Guzzle\Batch\BatchTransferInterface;
use Guzzle\Service\Command\CommandInterface;

/**
 * Transfer logic for deleting multiple objects from an Amazon S3 bucket in a
 * single request
 */
class DeleteObjectsTransfer implements BatchTransferInterface
{
    /**
     * @var AwsClientInterface The Amazon S3 client for doing transfers
     */
    protected $client;

    /**
     * @var string Bucket from which to delete the objects
     */
    protected $bucket;

    /**
     * @var string MFA token to apply to the request
     */
    protected $mfa;

    /**
     * Constructs a transfer using the injected client
     *
     * @param AwsClientInterface $client Client used to transfer the requests
     * @param string             $bucket Name of the bucket that stores the objects
     * @param string             $mfa    MFA token used when contacting the Amazon S3 API
     */
    public function __construct(AwsClientInterface $client, $bucket, $mfa = null)
    {
        $this->client = $client;
        $this->bucket = $bucket;
        $this->mfa = $mfa;
    }

    /**
     * Set a new MFA token value
     *
     * @param string $token MFA token
     *
     * @return self
     */
    public function setMfa($token)
    {
        $this->mfa = $token;

        return $this;
    }

    /**
     * {@inheritdoc}
     * @throws OverflowException        if a batch has more than 1000 items
     * @throws InvalidArgumentException when an invalid batch item is encountered
     */
    public function transfer(array $batch)
    {
        if (empty($batch)) {
            return;
        }

        if (count($batch) > 1000) {
            throw new OverflowException('Batches should be divided into chunks of no larger than 1000 keys');
        }

        $del = array();
        $command = $this->client->getCommand('DeleteObjects', array(
            'Bucket'   => $this->bucket,
            Ua::OPTION => Ua::BATCH
        ));

        if ($this->mfa) {
            $command->getRequestHeaders()->set('x-amz-mfa', $this->mfa);
        }

        foreach ($batch as $object) {
            // Ensure that the batch item is valid
            if (!is_array($object) || !isset($object['Key'])) {
                throw new InvalidArgumentException('Invalid batch item encountered: ' . var_export($batch, true));
            }
            $del[] = array(
                'Key'       => $object['Key'],
                'VersionId' => isset($object['VersionId']) ? $object['VersionId'] : null
            );
        }

        $command['Objects'] = $del;

        $command->execute();
        $this->processResponse($command);
    }

    /**
     * Process the response of the DeleteMultipleObjects request
     *
     * @paramCommandInterface $command Command executed
     */
    protected function processResponse(CommandInterface $command)
    {
        $result = $command->getResult();

        // Ensure that the objects were deleted successfully
        if (!empty($result['Errors'])) {
            $errors = $result['Errors'];
            throw new DeleteMultipleObjectsException($errors);
        }
    }
}
