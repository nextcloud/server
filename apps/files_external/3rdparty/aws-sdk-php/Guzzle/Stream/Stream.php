<?php

namespace Guzzle\Stream;

use Guzzle\Common\Exception\InvalidArgumentException;

/**
 * PHP stream implementation
 */
class Stream implements StreamInterface
{
    const STREAM_TYPE = 'stream_type';
    const WRAPPER_TYPE = 'wrapper_type';
    const IS_LOCAL = 'is_local';
    const IS_READABLE = 'is_readable';
    const IS_WRITABLE = 'is_writable';
    const SEEKABLE = 'seekable';

    /** @var resource Stream resource */
    protected $stream;

    /** @var int Size of the stream contents in bytes */
    protected $size;

    /** @var array Stream cached data */
    protected $cache = array();

    /** @var array Custom stream data */
    protected $customData = array();

    /** @var array Hash table of readable and writeable stream types for fast lookups */
    protected static $readWriteHash = array(
        'read' => array(
            'r' => true, 'w+' => true, 'r+' => true, 'x+' => true, 'c+' => true,
            'rb' => true, 'w+b' => true, 'r+b' => true, 'x+b' => true, 'c+b' => true,
            'rt' => true, 'w+t' => true, 'r+t' => true, 'x+t' => true, 'c+t' => true, 'a+' => true
        ),
        'write' => array(
            'w' => true, 'w+' => true, 'rw' => true, 'r+' => true, 'x+' => true, 'c+' => true,
            'wb' => true, 'w+b' => true, 'r+b' => true, 'x+b' => true, 'c+b' => true,
            'w+t' => true, 'r+t' => true, 'x+t' => true, 'c+t' => true, 'a' => true, 'a+' => true
        )
    );

    /**
     * @param resource $stream Stream resource to wrap
     * @param int      $size   Size of the stream in bytes. Only pass if the size cannot be obtained from the stream.
     *
     * @throws InvalidArgumentException if the stream is not a stream resource
     */
    public function __construct($stream, $size = null)
    {
        $this->setStream($stream, $size);
    }

    /**
     * Closes the stream when the helper is destructed
     */
    public function __destruct()
    {
        $this->close();
    }

    public function __toString()
    {
        if (!$this->isReadable() || (!$this->isSeekable() && $this->isConsumed())) {
            return '';
        }

        $originalPos = $this->ftell();
        $body = stream_get_contents($this->stream, -1, 0);
        $this->seek($originalPos);

        return $body;
    }

    public function close()
    {
        if (is_resource($this->stream)) {
            fclose($this->stream);
        }
        $this->cache[self::IS_READABLE] = false;
        $this->cache[self::IS_WRITABLE] = false;
    }

    /**
     * Calculate a hash of a Stream
     *
     * @param StreamInterface $stream    Stream to calculate the hash for
     * @param string          $algo      Hash algorithm (e.g. md5, crc32, etc)
     * @param bool            $rawOutput Whether or not to use raw output
     *
     * @return bool|string Returns false on failure or a hash string on success
     */
    public static function getHash(StreamInterface $stream, $algo, $rawOutput = false)
    {
        $pos = $stream->ftell();
        if (!$stream->seek(0)) {
            return false;
        }

        $ctx = hash_init($algo);
        while (!$stream->feof()) {
            hash_update($ctx, $stream->read(8192));
        }

        $out = hash_final($ctx, (bool) $rawOutput);
        $stream->seek($pos);

        return $out;
    }

    public function getMetaData($key = null)
    {
        $meta = stream_get_meta_data($this->stream);

        return !$key ? $meta : (array_key_exists($key, $meta) ? $meta[$key] : null);
    }

    public function getStream()
    {
        return $this->stream;
    }

    public function setStream($stream, $size = null)
    {
        if (!is_resource($stream)) {
            throw new InvalidArgumentException('Stream must be a resource');
        }

        $this->size = $size;
        $this->stream = $stream;
        $this->rebuildCache();

        return $this;
    }

    public function detachStream()
    {
        $this->stream = null;

        return $this;
    }

    public function getWrapper()
    {
        return $this->cache[self::WRAPPER_TYPE];
    }

    public function getWrapperData()
    {
        return $this->getMetaData('wrapper_data') ?: array();
    }

    public function getStreamType()
    {
        return $this->cache[self::STREAM_TYPE];
    }

    public function getUri()
    {
        return $this->cache['uri'];
    }

    public function getSize()
    {
        if ($this->size !== null) {
            return $this->size;
        }

        // If the stream is a file based stream and local, then use fstat
        clearstatcache(true, $this->cache['uri']);
        $stats = fstat($this->stream);
        if (isset($stats['size'])) {
            $this->size = $stats['size'];
            return $this->size;
        } elseif ($this->cache[self::IS_READABLE] && $this->cache[self::SEEKABLE]) {
            // Only get the size based on the content if the the stream is readable and seekable
            $pos = $this->ftell();
            $this->size = strlen((string) $this);
            $this->seek($pos);
            return $this->size;
        }

        return false;
    }

    public function isReadable()
    {
        return $this->cache[self::IS_READABLE];
    }

    public function isRepeatable()
    {
        return $this->cache[self::IS_READABLE] && $this->cache[self::SEEKABLE];
    }

    public function isWritable()
    {
        return $this->cache[self::IS_WRITABLE];
    }

    public function isConsumed()
    {
        return feof($this->stream);
    }

    public function feof()
    {
        return $this->isConsumed();
    }

    public function isLocal()
    {
        return $this->cache[self::IS_LOCAL];
    }

    public function isSeekable()
    {
        return $this->cache[self::SEEKABLE];
    }

    public function setSize($size)
    {
        $this->size = $size;

        return $this;
    }

    public function seek($offset, $whence = SEEK_SET)
    {
        return $this->cache[self::SEEKABLE] ? fseek($this->stream, $offset, $whence) === 0 : false;
    }

    public function read($length)
    {
        return fread($this->stream, $length);
    }

    public function write($string)
    {
        // We can't know the size after writing anything
        $this->size = null;

        return fwrite($this->stream, $string);
    }

    public function ftell()
    {
        return ftell($this->stream);
    }

    public function rewind()
    {
        return $this->seek(0);
    }

    public function readLine($maxLength = null)
    {
        if (!$this->cache[self::IS_READABLE]) {
            return false;
        } else {
            return $maxLength ? fgets($this->getStream(), $maxLength) : fgets($this->getStream());
        }
    }

    public function setCustomData($key, $value)
    {
        $this->customData[$key] = $value;

        return $this;
    }

    public function getCustomData($key)
    {
        return isset($this->customData[$key]) ? $this->customData[$key] : null;
    }

    /**
     * Reprocess stream metadata
     */
    protected function rebuildCache()
    {
        $this->cache = stream_get_meta_data($this->stream);
        $this->cache[self::IS_LOCAL] = stream_is_local($this->stream);
        $this->cache[self::IS_READABLE] = isset(self::$readWriteHash['read'][$this->cache['mode']]);
        $this->cache[self::IS_WRITABLE] = isset(self::$readWriteHash['write'][$this->cache['mode']]);
    }
}
