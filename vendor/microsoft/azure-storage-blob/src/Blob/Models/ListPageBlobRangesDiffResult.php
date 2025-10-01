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

use MicrosoftAzure\Storage\Common\Internal\Utilities;
use MicrosoftAzure\Storage\Blob\Internal\BlobResources as Resources;
use MicrosoftAzure\Storage\Common\Models\RangeDiff;

/**
 * Holds result of calling listPageBlobRangesDiff wrapper
 *
 * @category  Microsoft
 * @package   MicrosoftAzure\Storage\Blob\Models
 * @author    Azure Storage PHP SDK <dmsh@microsoft.com>
 * @copyright 2017 Microsoft Corporation
 * @license   https://github.com/azure/azure-storage-php/LICENSE
 * @link      https://github.com/azure/azure-storage-php
 */
class ListPageBlobRangesDiffResult extends ListPageBlobRangesResult
{
    /**
     * Creates ListPageBlobRangesDiffResult object from $parsed response in array representation
     *
     * @param array $headers HTTP response headers
     * @param array $parsed  parsed response in array format.
     *
     * @internal
     *
     * @return ListPageBlobRangesDiffResult
     */
    public static function create(array $headers, array $parsed = null)
    {
        $result  = new ListPageBlobRangesDiffResult();
        $headers = array_change_key_case($headers);

        $date          = $headers[Resources::LAST_MODIFIED];
        $date          = Utilities::rfc1123ToDateTime($date);
        $blobLength    = intval($headers[Resources::X_MS_BLOB_CONTENT_LENGTH]);

        $result->setContentLength($blobLength);
        $result->setLastModified($date);
        $result->setETag($headers[Resources::ETAG]);

        if (is_null($parsed)) {
            return $result;
        }

        $parsed = array_change_key_case($parsed);

        $rawRanges = array();
        if (!empty($parsed[strtolower(Resources::XTAG_PAGE_RANGE)])) {
            $rawRanges = Utilities::getArray($parsed[strtolower(Resources::XTAG_PAGE_RANGE)]);
        }

        $pageRanges = array();
        foreach ($rawRanges as $value) {
            $pageRanges[] = new RangeDiff(
                intval($value[Resources::XTAG_RANGE_START]),
                intval($value[Resources::XTAG_RANGE_END])
            );
        }

        $rawRanges = array();
        if (!empty($parsed[strtolower(Resources::XTAG_CLEAR_RANGE)])) {
            $rawRanges = Utilities::getArray($parsed[strtolower(Resources::XTAG_CLEAR_RANGE)]);
        }

        foreach ($rawRanges as $value) {
            $pageRanges[] = new RangeDiff(
                intval($value[Resources::XTAG_RANGE_START]),
                intval($value[Resources::XTAG_RANGE_END]),
                true
            );
        }

        $result->setRanges($pageRanges);
        return $result;
    }
}
