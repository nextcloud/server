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
 * @copyright 2017 Microsoft Corporation
 * @license   https://github.com/azure/azure-storage-php/LICENSE
 * @link      https://github.com/azure/azure-storage-php
 */

namespace MicrosoftAzure\Storage\Blob\Models;

use MicrosoftAzure\Storage\Blob\Internal\BlobResources as Resources;
use MicrosoftAzure\Storage\Common\Internal\Utilities;

/**
 * Represents blob copy state
 *
 * @category  Microsoft
 * @package   MicrosoftAzure\Storage\Blob\Models
 * @author    Azure Storage PHP SDK <dmsh@microsoft.com>
 * @copyright 2016 Microsoft Corporation
 * @license   https://github.com/azure/azure-storage-php/LICENSE
 * @link      https://github.com/azure/azure-storage-php
 */
class CopyState
{
    private $_copyId;
    private $_completionTime;
    private $_status;
    private $_statusDescription;
    private $_source;
    private $_bytesCopied;
    private $_totalBytes;

    /**
     * Creates CopyState object from $parsed response in array representation of XML elements
     *
     * @param array $parsed parsed response in array format.
     *
     * @internal
     *
     * @return \MicrosoftAzure\Storage\Blob\Models\CopyState
     */
    public static function createFromXml(array $parsed)
    {
        $result = new CopyState();
        $clean  = array_change_key_case($parsed);

        $copyCompletionTime = Utilities::tryGetValue($clean, 'copycompletiontime');
        if (!is_null($copyCompletionTime)) {
            $copyCompletionTime = Utilities::rfc1123ToDateTime($copyCompletionTime);
            $result->setCompletionTime($copyCompletionTime);
        }

        $result->setCopyId(Utilities::tryGetValue($clean, 'copyid'));
        $result->setStatus(Utilities::tryGetValue($clean, 'copystatus'));
        $result->setStatusDescription(Utilities::tryGetValue($clean, 'copystatusdescription'));
        $result->setSource(Utilities::tryGetValue($clean, 'copysource'));

        $copyProgress = Utilities::tryGetValue($clean, 'copyprogress');

        if (!is_null($copyProgress) && strpos($copyProgress, '/') !== false) {
            $parts = explode('/', $copyProgress);
            $bytesCopied = intval($parts[0]);
            $totalBytes = intval($parts[1]);

            $result->setBytesCopied($bytesCopied);
            $result->setTotalBytes($totalBytes);
        }

        return $result;
    }

    /**
     * Creates CopyState object from $parsed response in array representation of http headers
     *
     * @param array $parsed parsed response in array format.
     *
     * @internal
     *
     * @return \MicrosoftAzure\Storage\Blob\Models\CopyState
     */
    public static function createFromHttpHeaders(array $parsed)
    {
        $result = new CopyState();
        $clean  = array_change_key_case($parsed);

        $copyCompletionTime = Utilities::tryGetValue($clean, Resources::X_MS_COPY_COMPLETION_TIME);
        if (!is_null($copyCompletionTime)) {
            $copyCompletionTime = Utilities::rfc1123ToDateTime($copyCompletionTime);
            $result->setCompletionTime($copyCompletionTime);
        }

        $result->setCopyId(Utilities::tryGetValue($clean, Resources::X_MS_COPY_ID));
        $result->setStatus(Utilities::tryGetValue($clean, Resources::X_MS_COPY_STATUS));
        $result->setStatusDescription(Utilities::tryGetValue($clean, Resources::X_MS_COPY_STATUS_DESCRIPTION));
        $result->setSource(Utilities::tryGetValue($clean, Resources::X_MS_COPY_SOURCE));

        $copyProgress = Utilities::tryGetValue($clean, Resources::X_MS_COPY_PROGRESS);
        if (!is_null($copyProgress) && strpos($copyProgress, '/') !== false) {
            $parts = explode('/', $copyProgress);
            $bytesCopied = intval($parts[0]);
            $totalBytes = intval($parts[1]);

            $result->setBytesCopied($bytesCopied);
            $result->setTotalBytes($totalBytes);
        }

        return $result;
    }

    /**
     * Gets copy Id
     *
     * @return string
     */
    public function getCopyId()
    {
        return $this->_copyId;
    }

    /**
     * Sets copy Id
     *
     * @param string $copyId the blob copy id.
     *
     * @internal
     *
     * @return void
     */
    protected function setCopyId($copyId)
    {
        $this->_copyId = $copyId;
    }

    /**
     * Gets copy completion time
     *
     * @return \DateTime
     */
    public function getCompletionTime()
    {
        return $this->_completionTime;
    }

    /**
     * Sets copy completion time
     *
     * @param \DateTime $completionTime the copy completion time.
     *
     * @internal
     *
     * @return void
     */
    protected function setCompletionTime($completionTime)
    {
        $this->_completionTime = $completionTime;
    }

    /**
     * Gets copy status
     *
     * @return string
     */
    public function getStatus()
    {
        return $this->_status;
    }

    /**
     * Sets copy status
     *
     * @param string $status the copy status.
     *
     * @internal
     *
     * @return void
     */
    protected function setStatus($status)
    {
        $this->_status = $status;
    }

    /**
     * Gets copy status description
     *
     * @return string
     */
    public function getStatusDescription()
    {
        return $this->_statusDescription;
    }

    /**
     * Sets copy status description
     *
     * @param string $statusDescription the copy status description.
     *
     * @internal
     *
     * @return void
     */
    protected function setStatusDescription($statusDescription)
    {
        $this->_statusDescription = $statusDescription;
    }

    /**
     * Gets copy source
     *
     * @return string
     */
    public function getSource()
    {
        return $this->_source;
    }

    /**
     * Sets copy source
     *
     * @param string $source the copy source.
     *
     * @internal
     *
     * @return void
     */
    protected function setSource($source)
    {
        $this->_source = $source;
    }

    /**
     * Gets bytes copied
     *
     * @return int
     */
    public function getBytesCopied()
    {
        return $this->_bytesCopied;
    }

    /**
     * Sets bytes copied
     *
     * @param int $bytesCopied the bytes copied.
     *
     * @internal
     *
     * @return void
     */
    protected function setBytesCopied($bytesCopied)
    {
        $this->_bytesCopied = $bytesCopied;
    }

    /**
     * Gets total bytes to be copied
     *
     * @return int
     */
    public function getTotalBytes()
    {
        return $this->_bytesCopied;
    }

    /**
     * Sets total bytes to be copied
     *
     * @param int $totalBytes the bytes copied.
     *
     * @internal
     *
     * @return void
     */
    protected function setTotalBytes($totalBytes)
    {
        $this->_totalBytes = $totalBytes;
    }
}
