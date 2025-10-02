<?php

/**
 * LICENSE: The MIT License (the "License")
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 * https://github.com/azure/azure-storage-php/LICENSE
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 *
 * PHP version 5
 *
 * @category  Microsoft
 * @package   MicrosoftAzure\Storage\Common
 * @author    Azure Storage PHP SDK <dmsh@microsoft.com>
 * @copyright 2017 Microsoft Corporation
 * @license   https://github.com/azure/azure-storage-php/LICENSE
 * @link      https://github.com/azure/azure-storage-php
 */

namespace MicrosoftAzure\Storage\Common;

/**
 * Location mode for the service.
 *
 * @category  Microsoft
 * @package   MicrosoftAzure\Storage\Common
 * @author    Azure Storage PHP SDK <dmsh@microsoft.com>
 * @copyright 2017 Microsoft Corporation
 * @license   https://github.com/azure/azure-storage-php/LICENSE
 * @link      https://github.com/azure/azure-storage-php
 */
class LocationMode
{
    //Request will only be sent to primary endpoint, except for
    //getServiceStats APIs.
    const PRIMARY_ONLY           = 'PrimaryOnly';

    //Request will only be sent to secondary endpoint.
    const SECONDARY_ONLY         = 'SecondaryOnly';

    //Request will be sent to primary endpoint first, and retry for secondary
    //endpoint.
    const PRIMARY_THEN_SECONDARY = 'PrimaryThenSecondary';

    //Request will be sent to secondary endpoint first, and retry for primary
    //endpoint.
    const SECONDARY_THEN_PRIMARY = 'SecondaryThenPrimary';
}
