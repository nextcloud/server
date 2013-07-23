<?php

namespace Guzzle\Http;

/**
 * EntityBody decorator used to return only a subset of an entity body
 */
class ReadLimitEntityBody extends AbstractEntityBodyDecorator
{
    /** @var int Limit the number of bytes that can be read */
    protected $limit;

    /** @var int Offset to start reading from */
    protected $offset;

    /**
     * @param EntityBodyInterface $body   Body to wrap
     * @param int                 $limit  Total number of bytes to allow to be read from the stream
     * @param int                 $offset Position to seek to before reading (only works on seekable streams)
     */
    public function __construct(EntityBodyInterface $body, $limit, $offset = 0)
    {
        parent::__construct($body);
        $this->setLimit($limit)->setOffset($offset);
        $this->body->seek($offset);
    }

    /**
     * Returns only a subset of the decorated entity body when cast as a string
     * {@inheritdoc}
     */
    public function __toString()
    {
        return substr((string) $this->body, $this->offset, $this->limit) ?: '';
    }

    public function isConsumed()
    {
        return (($this->offset + $this->limit) - $this->body->ftell()) <= 0;
    }

    /**
     * Returns the Content-Length of the limited subset of data
     * {@inheritdoc}
     */
    public function getContentLength()
    {
        $length = $this->body->getContentLength();

        return $length === false
            ? $this->limit
            : min($this->limit, min($length, $this->offset + $this->limit) - $this->offset);
    }

    /**
     * Allow for a bounded seek on the read limited entity body
     * {@inheritdoc}
     */
    public function seek($offset, $whence = SEEK_SET)
    {
        return $whence === SEEK_SET
            ? $this->body->seek(max($this->offset, min($this->offset + $this->limit, $offset)))
            : false;
    }

    /**
     * Set the offset to start limiting from
     *
     * @param int $offset Offset to seek to and begin byte limiting from
     *
     * @return self
     */
    public function setOffset($offset)
    {
        $this->body->seek($offset);
        $this->offset = $offset;

        return $this;
    }

    /**
     * Set the limit of bytes that the decorator allows to be read from the stream
     *
     * @param int $limit Total number of bytes to allow to be read from the stream
     *
     * @return self
     */
    public function setLimit($limit)
    {
        $this->limit = $limit;

        return $this;
    }

    public function read($length)
    {
        // Check if the current position is less than the total allowed bytes + original offset
        $remaining = ($this->offset + $this->limit) - $this->body->ftell();
        if ($remaining > 0) {
            // Only return the amount of requested data, ensuring that the byte limit is not exceeded
            return $this->body->read(min($remaining, $length));
        } else {
            return false;
        }
    }
}
