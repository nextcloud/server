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

namespace Aws\Common\InstanceMetadata\Waiter;

use Aws\Common\Waiter\AbstractResourceWaiter;
use Guzzle\Http\Exception\CurlException;

/**
 * Waits until the instance metadata service is responding.  Will send up to
 * 4 requests with a 5 second delay between each try.  Each try can last up to
 * 11 seconds to complete if the service is not responding.
 *
 * @codeCoverageIgnore
 */
class ServiceAvailable extends AbstractResourceWaiter
{
    protected $interval = 5;
    protected $maxAttempts = 4;

    /**
     * {@inheritdoc}
     */
    public function doWait()
    {
        $request = $this->client->get();
        try {
            $request->getCurlOptions()->set(CURLOPT_CONNECTTIMEOUT, 10)
                ->set(CURLOPT_TIMEOUT, 10);
            $request->send();

            return true;
        } catch (CurlException $e) {
            return false;
        }
    }
}
