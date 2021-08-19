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
 * @package   MicrosoftAzure\Storage\Common\Internal\Serialization
 * @author    Azure Storage PHP SDK <dmsh@microsoft.com>
 * @copyright Microsoft Corporation
 * @license   https://github.com/azure/azure-storage-php/LICENSE
 * @link      https://github.com/azure/azure-storage-php
 */

namespace MicrosoftAzure\Storage\Common\Internal\Serialization;

use MicrosoftAzure\Storage\Common\Internal\Validate;

/**
 * Perform JSON serialization / deserialization
 *
 * @ignore
 * @category  Microsoft
 * @package   MicrosoftAzure\Storage\Common\Internal\Serialization
 * @author    Azure Storage PHP SDK <dmsh@microsoft.com>
 * @copyright Microsoft Corporation
 * @license   https://github.com/azure/azure-storage-php/LICENSE
 * @link      https://github.com/azure/azure-storage-php
 */
class JsonSerializer implements ISerializer
{
    /**
     * Serialize an object with specified root element name.
     *
     * @param object $targetObject The target object.
     * @param string $rootName     The name of the root element.
     *
     * @return string
     */
    public static function objectSerialize($targetObject, $rootName)
    {
        Validate::notNull($targetObject, 'targetObject');
        Validate::canCastAsString($rootName, 'rootName');

        $contianer = new \stdClass();

        $contianer->$rootName = $targetObject;

        return json_encode($contianer);
    }

    /**
     * Serializes given array. The array indices must be string to use them as
     * as element name.
     *
     * @param array $array      The object to serialize represented in array.
     * @param array $properties The used properties in the serialization process.
     *
     * @return string
     */
    public function serialize(array $array = null, array $properties = null)
    {
        Validate::isArray($array, 'array');

        return json_encode($array);
    }

    /**
     * Unserializes given serialized string to array.
     *
     * @param string $serialized The serialized object in string representation.
     *
     * @return array
     */
    public function unserialize($serialized)
    {
        Validate::canCastAsString($serialized, 'serialized');

        $json = json_decode($serialized);
        if ($json && !is_array($json)) {
            return get_object_vars($json);
        } else {
            return $json;
        }
    }
}
