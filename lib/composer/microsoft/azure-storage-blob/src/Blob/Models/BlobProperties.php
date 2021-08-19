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

use MicrosoftAzure\Storage\Blob\Internal\BlobResources as Resources;
use MicrosoftAzure\Storage\Common\Internal\Validate;
use MicrosoftAzure\Storage\Common\Internal\Utilities;

/**
 * Represents blob properties
 *
 * @category  Microsoft
 * @package   MicrosoftAzure\Storage\Blob\Models
 * @author    Azure Storage PHP SDK <dmsh@microsoft.com>
 * @copyright 2016 Microsoft Corporation
 * @license   https://github.com/azure/azure-storage-php/LICENSE
 * @link      https://github.com/azure/azure-storage-php
 */
class BlobProperties
{
    private $lastModified;
    private $creationTime;
    private $etag;
    private $contentType;
    private $contentLength;
    private $contentEncoding;
    private $contentLanguage;
    private $contentMD5;
    private $contentRange;
    private $cacheControl;
    private $contentDisposition;
    private $blobType;
    private $leaseStatus;
    private $leaseState;
    private $leaseDuration;
    private $sequenceNumber;
    private $serverEncrypted;
    private $committedBlockCount;
    private $copyState;
    private $copyDestinationSnapshot;
    private $incrementalCopy;
    private $rangeContentMD5;
    private $accessTier;
    private $accessTierInferred;
    private $accessTierChangeTime;
    private $archiveStatus;
    private $deletedTime;
    private $remainingRetentionDays;

    /**
     * Creates BlobProperties object from $parsed response in array representation of XML elements
     *
     * @param array $parsed parsed response in array format.
     *
     * @internal
     *
     * @return BlobProperties
     */
    public static function createFromXml(array $parsed)
    {
        $result = new BlobProperties();
        $clean  = array_change_key_case($parsed);

        $result->setCommonBlobProperties($clean);
        $result->setLeaseStatus(Utilities::tryGetValue($clean, 'leasestatus'));
        $result->setLeaseState(Utilities::tryGetValue($clean, 'leasestate'));
        $result->setLeaseDuration(Utilities::tryGetValue($clean, 'leaseduration'));
        $result->setCopyState(CopyState::createFromXml($clean));

        $result->setIncrementalCopy(
            Utilities::toBoolean(
                Utilities::tryGetValue($clean, 'incrementalcopy'),
                true
            )
        );

        $result->setAccessTier((
            Utilities::tryGetValue($clean, 'accesstier')
        ));

        $result->setAccessTierInferred(
            Utilities::toBoolean(
                Utilities::tryGetValue($clean, 'accesstierinferred'),
                true
            )
        );

        $accesstierchangetime = Utilities::tryGetValue($clean, 'accesstierchangetime');
        if (!is_null($accesstierchangetime)) {
            $accesstierchangetime = Utilities::rfc1123ToDateTime($accesstierchangetime);
            $result->setAccessTierChangeTime($accesstierchangetime);
        }

        $result->setArchiveStatus(
            Utilities::tryGetValue($clean, 'archivestatus')
        );

        $deletedtime = Utilities::tryGetValue($clean, 'deletedtime');
        if (!is_null($deletedtime)) {
            $deletedtime = Utilities::rfc1123ToDateTime($deletedtime);
            $result->setDeletedTime($deletedtime);
        }

        $remainingretentiondays = Utilities::tryGetValue($clean, 'remainingretentiondays');
        if (!is_null($remainingretentiondays)) {
            $result->setRemainingRetentionDays((int) $remainingretentiondays);
        }
        
        $creationtime = Utilities::tryGetValue($clean, 'creation-time');
        if (!is_null($creationtime)) {
            $creationtime = Utilities::rfc1123ToDateTime($creationtime);
            $result->setCreationTime($creationtime);
        }

        return $result;
    }

    /**
     * Creates BlobProperties object from $parsed response in array representation of http headers
     *
     * @param array $parsed parsed response in array format.
     *
     * @internal
     *
     * @return BlobProperties
     */
    public static function createFromHttpHeaders(array $parsed)
    {
        $result = new BlobProperties();
        $clean  = array_change_key_case($parsed);

        $result->setCommonBlobProperties($clean);

        $result->setBlobType(Utilities::tryGetValue($clean, Resources::X_MS_BLOB_TYPE));
        $result->setLeaseStatus(Utilities::tryGetValue($clean, Resources::X_MS_LEASE_STATUS));
        $result->setLeaseState(Utilities::tryGetValue($clean, Resources::X_MS_LEASE_STATE));
        $result->setLeaseDuration(Utilities::tryGetValue($clean, Resources::X_MS_LEASE_DURATION));
        $result->setCopyState(CopyState::createFromHttpHeaders($clean));

        $result->setServerEncrypted(
            Utilities::toBoolean(
                Utilities::tryGetValue(
                    $clean,
                    Resources::X_MS_SERVER_ENCRYPTED
                ),
                true
            )
        );
        $result->setIncrementalCopy(
            Utilities::toBoolean(
                Utilities::tryGetValue(
                    $clean,
                    Resources::X_MS_INCREMENTAL_COPY
                ),
                true
            )
        );
        $result->setCommittedBlockCount(
            intval(Utilities::tryGetValue(
                $clean,
                Resources::X_MS_BLOB_COMMITTED_BLOCK_COUNT
            ))
        );
        $result->setCopyDestinationSnapshot(
            Utilities::tryGetValue(
                $clean,
                Resources::X_MS_COPY_DESTINATION_SNAPSHOT
            )
        );

        $result->setAccessTier((
            Utilities::tryGetValue($clean, Resources::X_MS_ACCESS_TIER)
        ));

        $result->setAccessTierInferred(
            Utilities::toBoolean(
                Utilities::tryGetValue($clean, Resources::X_MS_ACCESS_TIER_INFERRED),
                true
            )
        );

        $date = Utilities::tryGetValue($clean, Resources::X_MS_ACCESS_TIER_CHANGE_TIME);
        if (!is_null($date)) {
            $date = Utilities::rfc1123ToDateTime($date);
            $result->setAccessTierChangeTime($date);
        }

        $result->setArchiveStatus(
            Utilities::tryGetValue($clean, Resources::X_MS_ARCHIVE_STATUS)
        );

        return $result;
    }

    /**
     * Gets blob lastModified.
     *
     * @return \DateTime
     */
    public function getLastModified()
    {
        return $this->lastModified;
    }

    /**
     * Sets blob lastModified.
     *
     * @param \DateTime $lastModified value.
     *
     * @return void
     */
    public function setLastModified(\DateTime $lastModified)
    {
        Validate::isDate($lastModified);
        $this->lastModified = $lastModified;
    }

    /**
     * Gets blob creationTime.
     *
     * @return \DateTime
     */
    public function getCreationTime()
    {
        return $this->creationTime;
    }

    /**
     * Sets blob creationTime.
     *
     * @param \DateTime $creationTime value.
     *
     * @return void
     */
    public function setCreationTime(\DateTime $creationTime)
    {
        Validate::isDate($creationTime);
        $this->creationTime = $creationTime;
    }

    /**
     * Gets blob etag.
     *
     * @return string
     */
    public function getETag()
    {
        return $this->etag;
    }

    /**
     * Sets blob etag.
     *
     * @param string $etag value.
     *
     * @return void
     */
    public function setETag($etag)
    {
        $this->etag = $etag;
    }

    /**
     * Gets blob contentType.
     *
     * @return string
     */
    public function getContentType()
    {
        return $this->contentType;
    }

    /**
     * Sets blob contentType.
     *
     * @param string $contentType value.
     *
     * @return void
     */
    public function setContentType($contentType)
    {
        $this->contentType = $contentType;
    }

    /**
     * Gets blob contentRange.
     *
     * @return string
     */
    public function getContentRange()
    {
        return $this->contentRange;
    }

    /**
     * Sets blob contentRange.
     *
     * @param string $contentRange value.
     *
     * @return void
     */
    public function setContentRange($contentRange)
    {
        $this->contentRange = $contentRange;
    }

    /**
     * Gets blob contentLength.
     *
     * @return integer
     */
    public function getContentLength()
    {
        return $this->contentLength;
    }

    /**
     * Sets blob contentLength.
     *
     * @param integer $contentLength value.
     *
     * @return void
     */
    public function setContentLength($contentLength)
    {
        Validate::isInteger($contentLength, 'contentLength');
        $this->contentLength = $contentLength;
    }

    /**
     * Gets blob contentEncoding.
     *
     * @return string
     */
    public function getContentEncoding()
    {
        return $this->contentEncoding;
    }

    /**
     * Sets blob contentEncoding.
     *
     * @param string $contentEncoding value.
     *
     * @return void
     */
    public function setContentEncoding($contentEncoding)
    {
        $this->contentEncoding = $contentEncoding;
    }

    /**
     * Gets blob access tier.
     *
     * @return string
     */
    public function getAccessTier()
    {
        return $this->accessTier;
    }

    /**
     * Sets blob access tier.
     *
     * @param string $accessTier value.
     *
     * @return void
     */
    public function setAccessTier($accessTier)
    {
        $this->accessTier = $accessTier;
    }

    /**
     * Gets blob archive status.
     *
     * @return string
     */
    public function getArchiveStatus()
    {
        return $this->archiveStatus;
    }

    /**
     * Sets blob archive status.
     *
     * @param string $archiveStatus value.
     *
     * @return void
     */
    public function setArchiveStatus($archiveStatus)
    {
        $this->archiveStatus = $archiveStatus;
    }
    
    /**
     * Gets blob deleted time.
     *
     * @return string
     */
    public function getDeletedTime()
    {
        return $this->deletedTime;
    }
    
    /**
     * Sets blob deleted time.
     *
     * @param \DateTime $deletedTime value.
     *
     * @return void
     */
    public function setDeletedTime(\DateTime $deletedTime)
    {
        $this->deletedTime = $deletedTime;
    }
    
    /**
     * Gets blob remaining retention days.
     *
     * @return integer
     */
    public function getRemainingRetentionDays()
    {
        return $this->remainingRetentionDays;
    }

    /**
     * Sets blob remaining retention days.
     *
     * @param integer $remainingRetentionDays value.
     *
     * @return void
     */
    public function setRemainingRetentionDays($remainingRetentionDays)
    {
        $this->remainingRetentionDays = $remainingRetentionDays;
    }

    
    /**
     * Gets blob access inferred.
     *
     * @return boolean
     */
    public function getAccessTierInferred()
    {
        return $this->accessTierInferred;
    }

    /**
     * Sets blob access tier inferred.
     *
     * @param boolean $accessTierInferred value.
     *
     * @return void
     */
    public function setAccessTierInferred($accessTierInferred)
    {
        Validate::isBoolean($accessTierInferred);
        $this->accessTierInferred = $accessTierInferred;
    }

    /**
     * Gets blob access tier change time.
     *
     * @return \DateTime
     */
    public function getAccessTierChangeTime()
    {
        return $this->accessTierChangeTime;
    }

    /**
     * Sets blob access tier change time.
     *
     * @param \DateTime $accessTierChangeTime value.
     *
     * @return void
     */
    public function setAccessTierChangeTime(\DateTime $accessTierChangeTime)
    {
        Validate::isDate($accessTierChangeTime);
        $this->accessTierChangeTime = $accessTierChangeTime;
    }

    /**
     * Gets blob contentLanguage.
     *
     * @return string
     */
    public function getContentLanguage()
    {
        return $this->contentLanguage;
    }

    /**
     * Sets blob contentLanguage.
     *
     * @param string $contentLanguage value.
     *
     * @return void
     */
    public function setContentLanguage($contentLanguage)
    {
        $this->contentLanguage = $contentLanguage;
    }

    /**
     * Gets blob contentMD5.
     *
     * @return string
     */
    public function getContentMD5()
    {
        return $this->contentMD5;
    }

    /**
     * Sets blob contentMD5.
     *
     * @param string $contentMD5 value.
     *
     * @return void
     */
    public function setContentMD5($contentMD5)
    {
        $this->contentMD5 = $contentMD5;
    }

    /**
     * Gets blob range contentMD5.
     *
     * @return string
     */
    public function getRangeContentMD5()
    {
        return $this->rangeContentMD5;
    }

    /**
     * Sets blob range contentMD5.
     *
     * @param string rangeContentMD5 value.
     *
     * @return void
     */
    public function setRangeContentMD5($rangeContentMD5)
    {
        $this->rangeContentMD5 = $rangeContentMD5;
    }

    /**
     * Gets blob cacheControl.
     *
     * @return string
     */
    public function getCacheControl()
    {
        return $this->cacheControl;
    }

    /**
     * Sets blob cacheControl.
     *
     * @param string $cacheControl value.
     *
     * @return void
     */
    public function setCacheControl($cacheControl)
    {
        $this->cacheControl = $cacheControl;
    }

    /**
     * Gets blob contentDisposition.
     *
     * @return string
     */
    public function getContentDisposition()
    {
        return $this->contentDisposition;
    }

    /**
     * Sets blob contentDisposition.
     *
     * @param string $contentDisposition value.
     *
     * @return void
     */
    public function setContentDisposition($contentDisposition)
    {
        $this->contentDisposition = $contentDisposition;
    }

    /**
     * Gets blob blobType.
     *
     * @return string
     */
    public function getBlobType()
    {
        return $this->blobType;
    }

    /**
     * Sets blob blobType.
     *
     * @param string $blobType value.
     *
     * @return void
     */
    public function setBlobType($blobType)
    {
        $this->blobType = $blobType;
    }

    /**
     * Gets blob leaseStatus.
     *
     * @return string
     */
    public function getLeaseStatus()
    {
        return $this->leaseStatus;
    }

    /**
     * Sets blob leaseStatus.
     *
     * @param string $leaseStatus value.
     *
     * @return void
     */
    public function setLeaseStatus($leaseStatus)
    {
        $this->leaseStatus = $leaseStatus;
    }

    /**
     * Gets blob lease state.
     *
     * @return string
     */
    public function getLeaseState()
    {
        return $this->leaseState;
    }

    /**
     * Sets blob lease state.
     *
     * @param string $leaseState value.
     *
     * @return void
     */
    public function setLeaseState($leaseState)
    {
        $this->leaseState = $leaseState;
    }

    /**
     * Gets blob lease duration.
     *
     * @return string
     */
    public function getLeaseDuration()
    {
        return $this->leaseDuration;
    }

    /**
     * Sets blob leaseStatus.
     *
     * @param string $leaseDuration value.
     *
     * @return void
     */
    public function setLeaseDuration($leaseDuration)
    {
        $this->leaseDuration = $leaseDuration;
    }

    /**
     * Gets blob sequenceNumber.
     *
     * @return int
     */
    public function getSequenceNumber()
    {
        return $this->sequenceNumber;
    }

    /**
     * Sets blob sequenceNumber.
     *
     * @param int $sequenceNumber value.
     *
     * @return void
     */
    public function setSequenceNumber($sequenceNumber)
    {
        Validate::isInteger($sequenceNumber, 'sequenceNumber');
        $this->sequenceNumber = $sequenceNumber;
    }

    /**
     * Gets the server encryption status of the blob.
     *
     * @return boolean
     */
    public function getServerEncrypted()
    {
        return $this->serverEncrypted;
    }

    /**
     * Sets the server encryption status of the blob.
     *
     * @param boolean $serverEncrypted
     *
     * @return void
     */
    public function setServerEncrypted($serverEncrypted)
    {
        $this->serverEncrypted = $serverEncrypted;
    }

    /**
     * Gets the number of committed blocks present in the blob.
     *
     * @return int
     */
    public function getCommittedBlockCount()
    {
        return $this->committedBlockCount;
    }

    /**
     * Sets the number of committed blocks present in the blob.
     *
     * @param int $committedBlockCount the number of committed blocks present in the blob.
     *
     * @return void
     */
    public function setCommittedBlockCount($committedBlockCount)
    {
        $this->committedBlockCount = $committedBlockCount;
    }

    /**
     * Gets copy state of the blob.
     *
     * @return CopyState
     */
    public function getCopyState()
    {
        return $this->copyState;
    }

    /**
     * Sets the copy state of the blob.
     *
     * @param CopyState $copyState the copy state of the blob.
     *
     * @return void
     */
    public function setCopyState($copyState)
    {
        $this->copyState = $copyState;
    }

    /**
     * Gets snapshot time of the last successful incremental copy snapshot for this blob.
     *
     * @return string
     */
    public function getCopyDestinationSnapshot()
    {
        return $this->copyDestinationSnapshot;
    }

    /**
     * Sets snapshot time of the last successful incremental copy snapshot for this blob.
     *
     * @param string $copyDestinationSnapshot last successful incremental copy snapshot.
     */
    public function setCopyDestinationSnapshot($copyDestinationSnapshot)
    {
        $this->copyDestinationSnapshot = $copyDestinationSnapshot;
    }

    /**
     * Gets whether the blob is an incremental copy blob.
     *
     * @return boolean
     */
    public function getIncrementalCopy()
    {
        return $this->incrementalCopy;
    }

    /**
     * Sets whether the blob is an incremental copy blob.
     *
     * @param boolean $incrementalCopy whether blob is an incremental copy blob.
     */
    public function setIncrementalCopy($incrementalCopy)
    {
        $this->incrementalCopy = $incrementalCopy;
    }

    private function setCommonBlobProperties(array $clean)
    {
        $date = Utilities::tryGetValue($clean, Resources::LAST_MODIFIED);
        if (!is_null($date)) {
            $date = Utilities::rfc1123ToDateTime($date);
            $this->setLastModified($date);
        }

        $this->setBlobType(Utilities::tryGetValue($clean, 'blobtype'));

        $this->setContentLength(intval($clean[Resources::CONTENT_LENGTH]));
        $this->setETag(Utilities::tryGetValue($clean, Resources::ETAG));
        $this->setSequenceNumber(
            intval(
                Utilities::tryGetValue($clean, Resources::X_MS_BLOB_SEQUENCE_NUMBER)
            )
        );
        $this->setContentRange(
            Utilities::tryGetValue($clean, Resources::CONTENT_RANGE)
        );
        $this->setCacheControl(
            Utilities::tryGetValue($clean, Resources::CACHE_CONTROL)
        );
        $this->setContentDisposition(
            Utilities::tryGetValue($clean, Resources::CONTENT_DISPOSITION)
        );
        $this->setContentEncoding(
            Utilities::tryGetValue($clean, Resources::CONTENT_ENCODING)
        );
        $this->setContentLanguage(
            Utilities::tryGetValue($clean, Resources::CONTENT_LANGUAGE)
        );
        $this->setContentType(
            Utilities::tryGetValue($clean, Resources::CONTENT_TYPE_LOWER_CASE)
        );

        if (Utilities::tryGetValue($clean, Resources::CONTENT_MD5) &&
            !Utilities::tryGetValue($clean, Resources::CONTENT_RANGE)
        ) {
            $this->setContentMD5(
                Utilities::tryGetValue($clean, Resources::CONTENT_MD5)
            );
        } else {
            $this->setContentMD5(
                Utilities::tryGetValue($clean, Resources::BLOB_CONTENT_MD5)
            );
            $this->setRangeContentMD5(
                Utilities::tryGetValue($clean, Resources::CONTENT_MD5)
            );
        }
    }
}
