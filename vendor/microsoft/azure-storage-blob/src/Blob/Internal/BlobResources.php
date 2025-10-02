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
 * @package   MicrosoftAzure\Storage\Common\Internal
 * @author    Azure Storage PHP SDK <dmsh@microsoft.com>
 * @copyright 2016 Microsoft Corporation
 * @license   https://github.com/azure/azure-storage-php/LICENSE
 * @link      https://github.com/azure/azure-storage-php
 */

namespace MicrosoftAzure\Storage\Blob\Internal;

use MicrosoftAzure\Storage\Common\Internal\Resources;

/**
 * Project resources.
 *
 * @ignore
 * @category  Microsoft
 * @package   MicrosoftAzure\Storage\Common\Internal
 * @author    Azure Storage PHP SDK <dmsh@microsoft.com>
 * @copyright 2017 Microsoft Corporation
 * @license   https://github.com/azure/azure-storage-php/LICENSE
 * @link      https://github.com/azure/azure-storage-php
 */
class BlobResources extends Resources
{
    // @codingStandardsIgnoreStart

    const BLOB_SDK_VERSION = '1.5.4';
    const STORAGE_API_LATEST_VERSION = '2017-11-09';

    // Error messages
    const INVALID_BTE_MSG = "The blob block type must exist in %s";
    const INVALID_BLOB_PAT_MSG = 'The provided access type is invalid.';
    const INVALID_ACH_MSG = 'The provided access condition header is invalid';
    const ERROR_TOO_LARGE_FOR_BLOCK_BLOB = 'Error: Exceeds the upper limit of the blob.';
    const ERROR_RANGE_NOT_ALIGN_TO_512 = 'Error: Range of the page blob must be align to 512';
    const ERROR_CONTAINER_NOT_EXIST = 'The specified container does not exist';
    const ERROR_BLOB_NOT_EXIST = 'The specified blob does not exist';
    const CONTENT_SIZE_TOO_LARGE = 'The content is too large for the selected blob type.';

    // Headers
    const X_MS_BLOB_PUBLIC_ACCESS = 'x-ms-blob-public-access';
    const X_MS_BLOB_SEQUENCE_NUMBER = 'x-ms-blob-sequence-number';
    const X_MS_BLOB_SEQUENCE_NUMBER_ACTION = 'x-ms-sequence-number-action';
    const X_MS_BLOB_TYPE = 'x-ms-blob-type';
    const X_MS_BLOB_CONTENT_TYPE = 'x-ms-blob-content-type';
    const X_MS_BLOB_CONTENT_ENCODING = 'x-ms-blob-content-encoding';
    const X_MS_BLOB_CONTENT_LANGUAGE = 'x-ms-blob-content-language';
    const X_MS_BLOB_CONTENT_MD5 = 'x-ms-blob-content-md5';
    const X_MS_BLOB_CACHE_CONTROL = 'x-ms-blob-cache-control';
    const X_MS_BLOB_CONTENT_DISPOSITION = 'x-ms-blob-content-disposition';
    const X_MS_BLOB_CONTENT_LENGTH = 'x-ms-blob-content-length';
    const X_MS_BLOB_CONDITION_MAXSIZE = 'x-ms-blob-condition-maxsize';
    const X_MS_BLOB_CONDITION_APPENDPOS = 'x-ms-blob-condition-appendpos';
    const X_MS_BLOB_APPEND_OFFSET = 'x-ms-blob-append-offset';
    const X_MS_BLOB_COMMITTED_BLOCK_COUNT = 'x-ms-blob-committed-block-count';
    const X_MS_LEASE_DURATION = 'x-ms-lease-duration';
    const X_MS_LEASE_ID = 'x-ms-lease-id';
    const X_MS_LEASE_TIME = 'x-ms-lease-time';
    const X_MS_LEASE_STATUS = 'x-ms-lease-status';
    const X_MS_LEASE_STATE = 'x-ms-lease-state';
    const X_MS_LEASE_ACTION = 'x-ms-lease-action';
    const X_MS_PROPOSED_LEASE_ID = 'x-ms-proposed-lease-id';
    const X_MS_LEASE_BREAK_PERIOD = 'x-ms-lease-break-period';
    const X_MS_PAGE_WRITE = 'x-ms-page-write';
    const X_MS_REQUEST_SERVER_ENCRYPTED = 'x-ms-request-server-encrypted';
    const X_MS_SERVER_ENCRYPTED = 'x-ms-server-encrypted';
    const X_MS_INCREMENTAL_COPY = 'x-ms-incremental-copy';
    const X_MS_COPY_DESTINATION_SNAPSHOT = 'x-ms-copy-destination-snapshot';
    const X_MS_ACCESS_TIER = 'x-ms-access-tier';
    const X_MS_ACCESS_TIER_INFERRED = 'x-ms-access-tier-inferred';
    const X_MS_ACCESS_TIER_CHANGE_TIME = 'x-ms-access-tier-change-time';
    const X_MS_ARCHIVE_STATUS = 'x-ms-archive-status';
    const MAX_BLOB_SIZE = 'x-ms-blob-condition-maxsize';
    const MAX_APPEND_POSITION = 'x-ms-blob-condition-appendpos';
    const SEQUENCE_NUMBER_LESS_THAN_OR_EQUAL = 'x-ms-if-sequence-number-le';
    const SEQUENCE_NUMBER_LESS_THAN = 'x-ms-if-sequence-number-lt';
    const SEQUENCE_NUMBER_EQUAL = 'x-ms-if-sequence-number-eq';
    const BLOB_CONTENT_MD5 = 'x-ms-blob-content-md5';

    // Query parameters
    const QP_DELIMITER = 'Delimiter';
    const QP_BLOCKID = 'blockid';
    const QP_BLOCK_LIST_TYPE = 'blocklisttype';
    const QP_PRE_SNAPSHOT = 'prevsnapshot';

    // Resource permissions
    const ACCESS_PERMISSIONS = [
        Resources::RESOURCE_TYPE_BLOB => ['r', 'a', 'c', 'w', 'd'],
        Resources::RESOURCE_TYPE_CONTAINER => ['r', 'a', 'c', 'w', 'd', 'l']
    ];

    // @codingStandardsIgnoreEnd
}
