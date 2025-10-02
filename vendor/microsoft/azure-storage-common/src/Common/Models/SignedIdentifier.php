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

use MicrosoftAzure\Storage\Common\Internal\Resources;

/**
 * Holds signed identifiers.
 *
 * @category  Microsoft
 * @package   MicrosoftAzure\Storage\Common\Models
 * @author    Azure Storage PHP SDK <dmsh@microsoft.com>
 * @copyright 2016 Microsoft Corporation
 * @license   https://github.com/azure/azure-storage-php/LICENSE
 * @link      https://github.com/azure/azure-storage-php
 */
class SignedIdentifier
{
    private $id;
    private $accessPolicy;

    /**
     * Constructor
     *
     * @param string            $id           The id of this signed identifier.
     * @param AccessPolicy|null $accessPolicy The access policy.
     */
    public function __construct($id = '', AccessPolicy $accessPolicy = null)
    {
        $this->setId($id);
        $this->setAccessPolicy($accessPolicy);
    }

    /**
     * Gets id.
     *
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Sets id.
     *
     * @param string $id value.
     *
     * @return void
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * Gets accessPolicy.
     *
     * @return AccessPolicy
     */
    public function getAccessPolicy()
    {
        return $this->accessPolicy;
    }

    /**
     * Sets accessPolicy.
     *
     * @param AccessPolicy|null $accessPolicy value.
     *
     * @return void
     */
    public function setAccessPolicy(AccessPolicy $accessPolicy = null)
    {
        $this->accessPolicy = $accessPolicy;
    }

    /**
     * Converts this current object to XML representation.
     *
     * @internal
     *
     * @return array
     */
    public function toArray()
    {
        $array = array();
        $accessPolicyArray = array();
        $accessPolicyArray[Resources::XTAG_SIGNED_ID] = $this->getId();
        $accessPolicyArray[Resources::XTAG_ACCESS_POLICY] =
            $this->getAccessPolicy()->toArray();
        $array[Resources::XTAG_SIGNED_IDENTIFIER] = $accessPolicyArray;

        return $array;
    }
}
