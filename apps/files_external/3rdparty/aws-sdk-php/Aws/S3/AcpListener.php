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

namespace Aws\S3;

use Aws\Common\Exception\InvalidArgumentException;
use Aws\S3\Model\Acp;
use Guzzle\Common\Event;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Listener used to add an Access Control Policy to a request
 */
class AcpListener implements EventSubscriberInterface
{
    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return array('command.before_prepare' => array('onCommandBeforePrepare', -255));
    }

    /**
     * An event handler for constructing ACP definitions.
     *
     * @param Event $event The event to respond to.
     *
     * @throws InvalidArgumentException
     */
    public function onCommandBeforePrepare(Event $event)
    {
        /** @var $command \Guzzle\Service\Command\AbstractCommand */
        $command = $event['command'];
        $operation = $command->getOperation();
        if ($operation->hasParam('ACP') && $command->hasKey('ACP')) {
            if ($acp = $command->get('ACP')) {
                // Ensure that the correct object was passed
                if (!($acp instanceof Acp)) {
                    throw new InvalidArgumentException('ACP must be an instance of Aws\S3\Model\Acp');
                }

                // Check if the user specified both an ACP and Grants
                if ($command->hasKey('Grants')) {
                    throw new InvalidArgumentException(
                        'Use either the ACP parameter or the Grants parameter. Do not use both.'
                    );
                }

                // Add the correct headers/body based parameters to the command
                if ($operation->hasParam('Grants')) {
                    $command->overwriteWith($acp->toArray());
                } else {
                    $acp->updateCommand($command);
                }
            }

            // Remove the ACP parameter
            $command->remove('ACP');
        }
    }
}
