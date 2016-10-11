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

namespace Aws\Common\Waiter;

use Guzzle\Common\Collection;

/**
 * Configuration info of a waiter object
 */
class WaiterConfig extends Collection
{
    const WAITER_NAME = 'name';
    const MAX_ATTEMPTS = 'max_attempts';
    const INTERVAL = 'interval';
    const OPERATION = 'operation';
    const IGNORE_ERRORS = 'ignore_errors';
    const DESCRIPTION = 'description';
    const SUCCESS_TYPE = 'success.type';
    const SUCCESS_PATH = 'success.path';
    const SUCCESS_VALUE = 'success.value';
    const FAILURE_TYPE = 'failure.type';
    const FAILURE_PATH = 'failure.path';
    const FAILURE_VALUE = 'failure.value';

    /**
     * @param array $data Array of configuration directives
     */
    public function __construct(array $data = array())
    {
        $this->data = $data;
        $this->extractConfig();
    }

    /**
     * Create the command configuration variables
     */
    protected function extractConfig()
    {
        // Populate success.* and failure.* if specified in acceptor.*
        foreach ($this->data as $key => $value) {
            if (substr($key, 0, 9) == 'acceptor.') {
                $name = substr($key, 9);
                if (!isset($this->data["success.{$name}"])) {
                    $this->data["success.{$name}"] = $value;
                }
                if (!isset($this->data["failure.{$name}"])) {
                    $this->data["failure.{$name}"] = $value;
                }
                unset($this->data[$key]);
            }
        }
    }
}
