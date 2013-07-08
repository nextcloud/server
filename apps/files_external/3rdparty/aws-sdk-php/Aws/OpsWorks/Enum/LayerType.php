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

namespace Aws\OpsWorks\Enum;

use Aws\Common\Enum;

/**
 * Contains enumerable LayerType values
 */
class LayerType extends Enum
{
    const LB = 'lb';
    const WEB = 'web';
    const PHP_APP = 'php-app';
    const RAILS_APP = 'rails-app';
    const NODEJS_APP = 'nodejs-app';
    const MEMCACHED = 'memcached';
    const DB_MASTER = 'db-master';
    const MONITORING_MASTER = 'monitoring-master';
    const CUSTOM = 'custom';
}
