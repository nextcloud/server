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
 * @package   MicrosoftAzure\Storage\Common\Internal\Http
 * @author    Azure Storage PHP SDK <dmsh@microsoft.com>
 * @copyright 2016 Microsoft Corporation
 * @license   https://github.com/azure/azure-storage-php/LICENSE
 * @link      https://github.com/azure/azure-storage-php
 */

namespace MicrosoftAzure\Storage\Common\Internal\Http;

/**
 * Helper class to format the http headers
 *
 * @ignore
 * @category  Microsoft
 * @package   MicrosoftAzure\Storage\Common\Internal\Http
 * @author    Azure Storage PHP SDK <dmsh@microsoft.com>
 * @copyright 2016 Microsoft Corporation
 * @license   https://github.com/azure/azure-storage-php/LICENSE
 * @link      https://github.com/azure/azure-storage-php
 */
class HttpFormatter
{
    /**
     * Convert a http headers array into an uniformed format for further process
     *
     * @param array $headers headers for format
     *
     * @return array
     */
    public static function formatHeaders(array $headers)
    {
        $result = array();
        foreach ($headers as $key => $value) {
            if (is_array($value) && count($value) == 1) {
                $result[strtolower($key)] = $value[0];
            } else {
                $result[strtolower($key)] = $value;
            }
        }

        return $result;
    }
}
