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

namespace Aws\Sqs;

use Guzzle\Common\Event;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Guzzle\Service\Command\AbstractCommand;

/**
 * Listener used to change the endpoint to the queue URL
 */
class QueueUrlListener implements EventSubscriberInterface
{
    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return array('command.before_send' => array('onCommandBeforeSend', -255));
    }

    /**
     * Updates the request URL to use the Queue URL
     *
     * @param Event $event Event emitted
     */
    public function onCommandBeforeSend(Event $event)
    {
        /** @var $command AbstractCommand */
        $command = $event['command'];
        if ($command->hasKey('QueueUrl')) {
            $request = $command->getRequest();
            $requestUrl = $request->getUrl(true);
            $request->setUrl($requestUrl->combine($command->get('QueueUrl')));
            $request->getParams()->remove('QueueUrl');
        }
    }
}
