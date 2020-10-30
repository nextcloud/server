<?php

/*
 * This file is part of the webmozart/glob package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Webmozart\Glob\Tests;

/**
 * @since  1.0
 *
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class TestStreamWrapper
{
    /**
     * @var string[]
     */
    private static $basePaths = array();

    /**
     * @var resource
     */
    private $handle;

    public static function register($scheme, $basePath)
    {
        self::$basePaths[$scheme] = $basePath;

        stream_wrapper_register($scheme, __CLASS__);
    }

    public static function unregister($scheme)
    {
        if (!isset(self::$basePaths[$scheme])) {
            return;
        }

        unset(self::$basePaths[$scheme]);

        stream_wrapper_unregister($scheme);
    }

    public function dir_opendir($uri, $options)
    {
        $this->handle = opendir($this->uriToPath($uri));

        return true;
    }

    public function dir_closedir()
    {
        assert(null !== $this->handle);

        closedir($this->handle);

        return false;
    }

    public function dir_readdir()
    {
        assert(null !== $this->handle);

        return readdir($this->handle);
    }

    public function dir_rewinddir()
    {
        assert(null !== $this->handle);

        rewinddir($this->handle);

        return true;
    }

    public function mkdir($uri, $mode, $options)
    {
    }

    public function rename($uriFrom, $uriTo)
    {
    }

    public function rmdir($uri, $options)
    {
    }

    public function stream_cast($castAs)
    {
        return $this->handle;
    }

    public function stream_close()
    {
    }

    public function stream_eof()
    {
    }

    public function stream_flush()
    {
    }

    public function stream_lock($operation)
    {
    }

    public function stream_metadata($uri, $option, $value)
    {
    }

    public function stream_open($uri, $mode, $options, &$openedPath)
    {
    }

    public function stream_read($length)
    {
    }

    public function stream_seek($offset, $whence = SEEK_SET)
    {
    }

    public function stream_set_option($option, $arg1, $arg2)
    {
    }

    public function stream_stat()
    {
    }

    public function stream_tell()
    {
    }

    public function stream_truncate($newSize)
    {
    }

    public function stream_write($data)
    {
    }

    public function unlink($uri)
    {
    }

    public function url_stat($uri, $flags)
    {
        $path = $this->uriToPath($uri);

        if ($flags & STREAM_URL_STAT_LINK) {
            return lstat($path);
        }

        return stat($path);
    }

    private function uriToPath($uri)
    {
        $parts = explode('://', $uri);

        return self::$basePaths[$parts[0]].$parts[1];
    }
}
