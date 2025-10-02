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
use MicrosoftAzure\Storage\Common\MarkerContinuationTokenTrait;

/**
 * Optional parameters for listBlobs API.
 *
 * @category  Microsoft
 * @package   MicrosoftAzure\Storage\Blob\Models
 * @author    Azure Storage PHP SDK <dmsh@microsoft.com>
 * @copyright 2016 Microsoft Corporation
 * @license   https://github.com/azure/azure-storage-php/LICENSE
 * @link      https://github.com/azure/azure-storage-php
 */
class ListBlobsOptions extends BlobServiceOptions
{
    use MarkerContinuationTokenTrait;

    private $_prefix;
    private $_delimiter;
    private $_maxResults;
    private $_includeMetadata;
    private $_includeSnapshots;
    private $_includeUncommittedBlobs;
    private $_includeCopy;
    private $_includeDeleted;

    /**
     * Gets prefix.
     *
     * @return string
     */
    public function getPrefix()
    {
        return $this->_prefix;
    }

    /**
     * Sets prefix.
     *
     * @param string $prefix value.
     *
     * @return void
     */
    public function setPrefix($prefix)
    {
        Validate::canCastAsString($prefix, 'prefix');
        $this->_prefix = $prefix;
    }

    /**
     * Gets delimiter.
     *
     * @return string
     */
    public function getDelimiter()
    {
        return $this->_delimiter;
    }

    /**
     * Sets prefix.
     *
     * @param string $delimiter value.
     *
     * @return void
     */
    public function setDelimiter($delimiter)
    {
        Validate::canCastAsString($delimiter, 'delimiter');
        $this->_delimiter = $delimiter;
    }

    /**
     * Gets max results.
     *
     * @return integer
     */
    public function getMaxResults()
    {
        return $this->_maxResults;
    }

    /**
     * Sets max results.
     *
     * @param integer $maxResults value.
     *
     * @return void
     */
    public function setMaxResults($maxResults)
    {
        Validate::isInteger($maxResults, 'maxResults');
        $this->_maxResults = $maxResults;
    }

    /**
     * Indicates if metadata is included or not.
     *
     * @return boolean
     */
    public function getIncludeMetadata()
    {
        return $this->_includeMetadata;
    }

    /**
     * Sets the include metadata flag.
     *
     * @param bool $includeMetadata value.
     *
     * @return void
     */
    public function setIncludeMetadata($includeMetadata)
    {
        Validate::isBoolean($includeMetadata);
        $this->_includeMetadata = $includeMetadata;
    }

    /**
     * Indicates if snapshots is included or not.
     *
     * @return boolean
     */
    public function getIncludeSnapshots()
    {
        return $this->_includeSnapshots;
    }

    /**
     * Sets the include snapshots flag.
     *
     * @param bool $includeSnapshots value.
     *
     * @return void
     */
    public function setIncludeSnapshots($includeSnapshots)
    {
        Validate::isBoolean($includeSnapshots);
        $this->_includeSnapshots = $includeSnapshots;
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
     * Indicates if copy is included or not.
     *
     * @return boolean
     */
    public function getIncludeCopy()
    {
        return $this->_includeCopy;
    }

    /**
     * Sets the include copy flag.
     *
     * @param bool $includeCopy value.
     *
     * @return void
     */
    public function setIncludeCopy($includeCopy)
    {
        Validate::isBoolean($includeCopy);
        $this->_includeCopy = $includeCopy;
    }

    /**
     * Indicates if deleted is included or not.
     *
     * @return boolean
     */
    public function getIncludeDeleted()
    {
        return $this->_includeDeleted;
    }

    /**
     * Sets the include deleted flag.
     *
     * @param bool $includeDeleted value.
     *
     * @return void
     */
    public function setIncludeDeleted($includeDeleted)
    {
        Validate::isBoolean($includeDeleted);
        $this->_includeDeleted = $includeDeleted;
    }
}
