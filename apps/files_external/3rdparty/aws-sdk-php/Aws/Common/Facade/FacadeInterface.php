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

namespace Aws\Common\Facade;

/**
 * Interface that defines a client facade. Facades are convenient static classes that allow you to run client methods
 * statically on a default instance from the service builder. The facades themselves are aliased into the global
 * namespace for ease of use.
 */
interface FacadeInterface
{
    /**
     * Returns the key used to access the client instance from the Service Builder
     *
     * @return string
     */
    public static function getServiceBuilderKey();
}
