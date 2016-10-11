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

namespace Aws\Common\Command;

use Guzzle\Service\Command\OperationCommand;
use Guzzle\Http\Curl\CurlHandle;

/**
 * Adds AWS JSON body functionality to dynamically generated HTTP requests
 */
class JsonCommand extends OperationCommand
{
    /**
     * {@inheritdoc}
     */
    protected function build()
    {
        parent::build();

        // Ensure that the body of the request ALWAYS includes some JSON. By default, this is an empty object.
        if (!$this->request->getBody()) {
            $this->request->setBody('{}');
        }

        // Never send the Expect header when interacting with a JSON query service
        $this->request->removeHeader('Expect');

        // Always send JSON requests as a raw string rather than using streams to avoid issues with
        // cURL error code 65: "necessary data rewind wasn't possible".
        // This could be removed after PHP addresses https://bugs.php.net/bug.php?id=47204
        $this->request->getCurlOptions()->set(CurlHandle::BODY_AS_STRING, true);
    }
}
