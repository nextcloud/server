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

use MicrosoftAzure\Storage\Common\Internal\Validate;

/**
 * Optional parameters for listBlobBlock wrapper
 *
 * @category  Microsoft
 * @package   MicrosoftAzure\Storage\Blob\Models
 * @author    Azure Storage PHP SDK <dmsh@microsoft.com>
 * @copyright 2016 Microsoft Corporation
 * @license   https://github.com/azure/azure-storage-php/LICENSE
 * @link      https://github.com/azure/azure-storage-php
 */
class ListBlobBlocksOptions extends BlobServiceOptions
{
    private $_snapshot;
    private $_includeUncommittedBlobs;
    private $_includeCommittedBlobs;
    private static $_listType;

    /**
     * Constructs the static variable $listType.
     */
    public function __construct()
    {
        parent::__construct();
        self::$_listType[true][true]   = 'all';
        self::$_listType[true][false]  = 'uncommitted';
        self::$_listType[false][true]  = 'committed';
        self::$_listType[false][false] = 'all';

        $this->_includeUncommittedBlobs = false;
        $this->_includeCommittedBlobs   = false;
    }

    /**
     * Gets blob snapshot.
     *
     * @return string
     */
    public function getSnapshot()
    {
        return $this->_snapshot;
    }

    /**
     * Sets blob snapshot.
     *
     * @param string $snapshot value.
     *
     * @return void
     */
    public function setSnapshot($snapshot)
    {
        $this->_snapshot = $snapshot;
    }

    /**
     * Sets the include uncommittedBlobs flag.
     *
     * @param bool $includeUncommittedBlobs value.
     *
     * @return void
     */
    public function setIncludeUncommittedBlobs($includeUncommittedBlobs)
    {
        Validate::isBoolean($includeUncommittedBlobs);
        $this->_includeUncommittedBlobs = $includeUncommittedBlobs;
    }

    /**
     * Indicates if uncommittedBlobs is included or not.
     *
     * @return boolean
     */
    public function getIncludeUncommittedBlobs()
    {
        return $this->_includeUncommittedBlobs;
    }

    /**
     * Sets the include committedBlobs flag.
     *
     * @param bool $includeCommittedBlobs value.
     *
     * @return void
     */
    public function setIncludeCommittedBlobs($includeCommittedBlobs)
    {
        Validate::isBoolean($includeCommittedBlobs);
        $this->_includeCommittedBlobs = $includeCommittedBlobs;
    }

    /**
     * Indicates if committedBlobs is included or not.
     *
     * @return boolean
     */
    public function getIncludeCommittedBlobs()
    {
        return $this->_includeCommittedBlobs;
    }

    /**
     * Gets block list type.
     *
     * @return string
     */
    public function getBlockListType()
    {
        $includeUncommitted = $this->_includeUncommittedBlobs;
        $includeCommitted   = $this->_includeCommittedBlobs;

        return self::$_listType[$includeUncommitted][$includeCommitted];
    }
}
