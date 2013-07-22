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

namespace Aws\Common\Client;

use Guzzle\Common\Event;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Listener used to append strings to the User-Agent header of a request based
 * on the `ua.append` option. `ua.append` can contain a string or array of values.
 */
class UserAgentListener implements EventSubscriberInterface
{
    /**
     * @var string Option used to store User-Agent modifiers
     */
    const OPTION = 'ua.append';

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return array('command.before_send' => 'onBeforeSend');
    }

    /**
     * Adds strings to the User-Agent header using the `ua.append` parameter of a command
     *
     * @param Event $event Event emitted
     */
    public function onBeforeSend(Event $event)
    {
        $command = $event['command'];
        if ($userAgentAppends = $command->get(self::OPTION)) {
            $request = $command->getRequest();
            $userAgent = (string) $request->getHeader('User-Agent');
            foreach ((array) $userAgentAppends as $append) {
                $append = ' ' . $append;
                if (strpos($userAgent, $append) === false) {
                    $userAgent .= $append;
                }
            }
            $request->setHeader('User-Agent', $userAgent);
        }
    }
}
