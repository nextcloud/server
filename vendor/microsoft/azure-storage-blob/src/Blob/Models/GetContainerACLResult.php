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
 * @package   MicrosoftAzure\Storage\Blob\Models
 * @author    Azure Storage PHP SDK <dmsh@microsoft.com>
 * @copyright 2016 Microsoft Corporation
 * @license   https://github.com/azure/azure-storage-php/LICENSE
 * @link      https://github.com/azure/azure-storage-php
 */

namespace MicrosoftAzure\Storage\Blob\Models;

/**
 * Holds container ACL
 *
 * @category  Microsoft
 * @package   MicrosoftAzure\Storage\Blob\Models
 * @author    Azure Storage PHP SDK <dmsh@microsoft.com>
 * @copyright 2016 Microsoft Corporation
 * @license   https://github.com/azure/azure-storage-php/LICENSE
 * @link      https://github.com/azure/azure-storage-php
 */
class GetContainerACLResult
{
    private $containerACL;
    private $lastModified;

    private $etag;

    /**
     * Parses the given array into signed identifiers
     *
     * @param string    $publicAccess container public access
     * @param string    $etag         container etag
     * @param \DateTime $lastModified last modification date
     * @param array     $parsed       parsed response into array
     * representation
     *
     * @internal
     *
     * @return self
     */
    public static function create(
        $publicAccess,
        $etag,
        \DateTime $lastModified,
        array $parsed = null
    ) {
        $result = new GetContainerAclResult();
        $result->setETag($etag);
        $result->setLastModified($lastModified);
        $acl = ContainerACL::create($publicAccess, $parsed);
        $result->setContainerAcl($acl);

        return $result;
    }

    /**
     * Gets container ACL
     *
     * @return ContainerACL
     */
    public function getContainerAcl()
    {
        return $this->containerACL;
    }

    /**
     * Sets container ACL
     *
     * @param ContainerACL $containerACL value.
     *
     * @return void
     */
    protected function setContainerAcl(ContainerACL $containerACL)
    {
        $this->containerACL = $containerACL;
    }

    /**
     * Gets container lastModified.
     *
     * @return \DateTime.
     */
    public function getLastModified()
    {
        return $this->lastModified;
    }

    /**
     * Sets container lastModified.
     *
     * @param \DateTime $lastModified value.
     *
     * @return void
     */
    protected function setLastModified(\DateTime $lastModified)
    {
        $this->lastModified = $lastModified;
    }

    /**
     * Gets container etag.
     *
     * @return string
     */
    public function getETag()
    {
        return $this->etag;
    }

    /**
     * Sets container etag.
     *
     * @param string $etag value.
     *
     * @return void
     */
    protected function setETag($etag)
    {
        $this->etag = $etag;
    }
}
