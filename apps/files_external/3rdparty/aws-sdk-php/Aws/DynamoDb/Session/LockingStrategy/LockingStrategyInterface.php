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

namespace Aws\DynamoDb\Session\LockingStrategy;

/**
 * An interface describing how a locking strategy should be defined
 */
interface LockingStrategyInterface
{
    /**
     * Reads the session data from Dynamo DB
     *
     * @param string $id The session ID
     *
     * @return array
     */
    public function doRead($id);

    /**
     * Writes the session data to Dynamo DB
     *
     * @param string $id            The session ID
     * @param string $data          The serialized session data
     * @param bool   $isDataChanged Whether or not the data has changed
     *
     * @return bool
     */
    public function doWrite($id, $data, $isDataChanged);

    /**
     * Deletes a session record from Dynamo DB
     *
     * @param string $id The session ID
     *
     * @return bool
     */
    public function doDestroy($id);
}
