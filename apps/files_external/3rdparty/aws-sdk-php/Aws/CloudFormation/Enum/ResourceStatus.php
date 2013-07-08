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

namespace Aws\CloudFormation\Enum;

use Aws\Common\Enum;

/**
 * Contains enumerable ResourceStatus values
 */
class ResourceStatus extends Enum
{
    const CREATE_IN_PROGRESS = 'CREATE_IN_PROGRESS';
    const CREATE_FAILED = 'CREATE_FAILED';
    const CREATE_COMPLETE = 'CREATE_COMPLETE';
    const DELETE_IN_PROGRESS = 'DELETE_IN_PROGRESS';
    const DELETE_FAILED = 'DELETE_FAILED';
    const DELETE_COMPLETE = 'DELETE_COMPLETE';
    const UPDATE_IN_PROGRESS = 'UPDATE_IN_PROGRESS';
    const UPDATE_FAILED = 'UPDATE_FAILED';
    const UPDATE_COMPLETE = 'UPDATE_COMPLETE';
}
