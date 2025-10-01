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
 * @package   MicrosoftAzure\Storage\Common\Models
 * @author    Azure Storage PHP SDK <dmsh@microsoft.com>
 * @copyright 2016 Microsoft Corporation
 * @license   https://github.com/azure/azure-storage-php/LICENSE
 * @link      https://github.com/azure/azure-storage-php
 */

namespace MicrosoftAzure\Storage\Common\Models;

use MicrosoftAzure\Storage\Common\Internal\Utilities;

/**
 * Holds elements of queue properties metrics field.
 *
 * @category  Microsoft
 * @package   MicrosoftAzure\Storage\Common\Models
 * @author    Azure Storage PHP SDK <dmsh@microsoft.com>
 * @copyright 2016 Microsoft Corporation
 * @license   https://github.com/azure/azure-storage-php/LICENSE
 * @link      https://github.com/azure/azure-storage-php
 */
class Metrics
{
    private $_version;
    private $_enabled;
    private $_includeAPIs;
    private $_retentionPolicy;

    /**
     * Creates object from $parsedResponse.
     *
     * @internal
     * @param array $parsedResponse XML response parsed into array.
     *
     * @return Metrics
     */
    public static function create(array $parsedResponse)
    {
        $result = new Metrics();
        $result->setVersion($parsedResponse['Version']);
        $result->setEnabled(Utilities::toBoolean($parsedResponse['Enabled']));
        if ($result->getEnabled()) {
            $result->setIncludeAPIs(
                Utilities::toBoolean($parsedResponse['IncludeAPIs'])
            );
        }
        $result->setRetentionPolicy(
            RetentionPolicy::create($parsedResponse['RetentionPolicy'])
        );

        return $result;
    }

    /**
     * Gets retention policy
     *
     * @return RetentionPolicy
     *
     */
    public function getRetentionPolicy()
    {
        return $this->_retentionPolicy;
    }

    /**
     * Sets retention policy
     *
     * @param RetentionPolicy $policy object to use
     *
     * @return void
     */
    public function setRetentionPolicy(RetentionPolicy $policy)
    {
        $this->_retentionPolicy = $policy;
    }

    /**
     * Gets include APIs.
     *
     * @return bool
     */
    public function getIncludeAPIs()
    {
        return $this->_includeAPIs;
    }

    /**
     * Sets include APIs.
     *
     * @param bool $includeAPIs value to use.
     *
     * @return void
     */
    public function setIncludeAPIs($includeAPIs)
    {
        $this->_includeAPIs = $includeAPIs;
    }

    /**
     * Gets enabled.
     *
     * @return bool
     */
    public function getEnabled()
    {
        return $this->_enabled;
    }

    /**
     * Sets enabled.
     *
     * @param bool $enabled value to use.
     *
     * @return void
     */
    public function setEnabled($enabled)
    {
        $this->_enabled = $enabled;
    }

    /**
     * Gets version
     *
     * @return string
     */
    public function getVersion()
    {
        return $this->_version;
    }

    /**
     * Sets version
     *
     * @param string $version new value.
     *
     * @return void
     */
    public function setVersion($version)
    {
        $this->_version = $version;
    }

    /**
     * Converts this object to array with XML tags
     *
     * @internal
     * @return array
     */
    public function toArray()
    {
        $array = array(
            'Version' => $this->_version,
            'Enabled' => Utilities::booleanToString($this->_enabled)
        );
        if ($this->_enabled) {
            $array['IncludeAPIs'] = Utilities::booleanToString($this->_includeAPIs);
        }
        $array['RetentionPolicy'] = !empty($this->_retentionPolicy)
            ? $this->_retentionPolicy->toArray()
            : null;

        return $array;
    }
}
