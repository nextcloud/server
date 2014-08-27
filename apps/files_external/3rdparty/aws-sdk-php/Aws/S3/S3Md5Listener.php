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

use Aws\Common\Signature\SignatureV4;
use Aws\Common\Signature\SignatureInterface;
use Guzzle\Common\Event;
use Guzzle\Service\Command\CommandInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Adds required and optional Content-MD5 headers
 */
class S3Md5Listener implements EventSubscriberInterface
{
    /** @var S3SignatureInterface */
    private $signature;

    public static function getSubscribedEvents()
    {
        return array('command.after_prepare' => 'onCommandAfterPrepare');
    }

    public function __construct(SignatureInterface $signature)
    {
        $this->signature = $signature;
    }

    public function onCommandAfterPrepare(Event $event)
    {
        $command = $event['command'];
        $operation = $command->getOperation();

        if ($operation->getData('contentMd5')) {
            // Add the MD5 if it is required for all signers
            $this->addMd5($command);
        } elseif ($operation->hasParam('ContentMD5')) {
            $value = $command['ContentMD5'];
            // Add a computed MD5 if the parameter is set to true or if
            // not using Signature V4 and the value is not set (null).
            if ($value === true ||
                ($value === null && !($this->signature instanceof SignatureV4))
            ) {
                $this->addMd5($command);
            }
        }
    }

    private function addMd5(CommandInterface $command)
    {
        $request = $command->getRequest();
        $body = $request->getBody();
        if ($body && $body->getSize() > 0) {
            if (false !== ($md5 = $body->getContentMd5(true, true))) {
                $request->setHeader('Content-MD5', $md5);
            }
        }
    }
}
