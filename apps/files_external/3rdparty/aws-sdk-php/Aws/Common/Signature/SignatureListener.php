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

namespace Aws\Common\Signature;

use Aws\Common\Credentials\CredentialsInterface;
use Guzzle\Common\Event;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Listener used to sign requests before they are sent over the wire
 */
class SignatureListener implements EventSubscriberInterface
{
    /**
     * @var CredentialsInterface
     */
    protected $credentials;

    /**
     * @var SignatureInterface
     */
    protected $signature;

    /**
     * Construct a new request signing plugin
     *
     * @param CredentialsInterface $credentials Credentials used to sign requests
     * @param SignatureInterface   $signature   Signature implementation
     */
    public function __construct(CredentialsInterface $credentials, SignatureInterface $signature)
    {
        $this->credentials = $credentials;
        $this->signature = $signature;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return array(
            'request.before_send'        => array('onRequestBeforeSend', -255),
            'client.credentials_changed' => array('onCredentialsChanged')
        );
    }

    /**
     * Updates the listener with new credentials if the client is updated
     *
     * @param Event $event Event emitted
     */
    public function onCredentialsChanged(Event $event)
    {
        $this->credentials = $event['credentials'];
    }

    /**
     * Signs requests before they are sent
     *
     * @param Event $event Event emitted
     */
    public function onRequestBeforeSend(Event $event)
    {
        $this->signature->signRequest($event['request'], $this->credentials);
    }
}
