<?php
namespace Aws\Multipart;

/**
 * Representation of the multipart upload.
 *
 * This object keeps track of the state of the upload, including the status and
 * which parts have been uploaded.
 */
class UploadState
{
    const CREATED = 0;
    const INITIATED = 1;
    const COMPLETED = 2;

    /** @var array Params used to identity the upload. */
    private $id;

    /** @var int Part size being used by the upload. */
    private $partSize;

    /** @var array Parts that have been uploaded. */
    private $uploadedParts = [];

    /** @var int Identifies the status the upload. */
    private $status = self::CREATED;

    /**
     * @param array $id Params used to identity the upload.
     */
    public function __construct(array $id)
    {
        $this->id = $id;
    }

    /**
     * Get the upload's ID, which is a tuple of parameters that can uniquely
     * identify the upload.
     *
     * @return array
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set's the "upload_id", or 3rd part of the upload's ID. This typically
     * only needs to be done after initiating an upload.
     *
     * @param string $key   The param key of the upload_id.
     * @param string $value The param value of the upload_id.
     */
    public function setUploadId($key, $value)
    {
        $this->id[$key] = $value;
    }

    /**
     * Get the part size.
     *
     * @return int
     */
    public function getPartSize()
    {
        return $this->partSize;
    }

    /**
     * Set the part size.
     *
     * @param $partSize int Size of upload parts.
     */
    public function setPartSize($partSize)
    {
        $this->partSize = $partSize;
    }

    /**
     * Marks a part as being uploaded.
     *
     * @param int   $partNumber The part number.
     * @param array $partData   Data from the upload operation that needs to be
     *                          recalled during the complete operation.
     */
    public function markPartAsUploaded($partNumber, array $partData = [])
    {
        $this->uploadedParts[$partNumber] = $partData;
    }

    /**
     * Returns whether a part has been uploaded.
     *
     * @param int $partNumber The part number.
     *
     * @return bool
     */
    public function hasPartBeenUploaded($partNumber)
    {
        return isset($this->uploadedParts[$partNumber]);
    }

    /**
     * Returns a sorted list of all the uploaded parts.
     *
     * @return array
     */
    public function getUploadedParts()
    {
        ksort($this->uploadedParts);

        return $this->uploadedParts;
    }

    /**
     * Set the status of the upload.
     *
     * @param int $status Status is an integer code defined by the constants
     *                    CREATED, INITIATED, and COMPLETED on this class.
     */
    public function setStatus($status)
    {
        $this->status = $status;
    }

    /**
     * Determines whether the upload state is in the INITIATED status.
     *
     * @return bool
     */
    public function isInitiated()
    {
        return $this->status === self::INITIATED;
    }

    /**
     * Determines whether the upload state is in the COMPLETED status.
     *
     * @return bool
     */
    public function isCompleted()
    {
        return $this->status === self::COMPLETED;
    }
}
