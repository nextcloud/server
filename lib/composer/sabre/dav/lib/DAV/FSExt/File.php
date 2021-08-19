<?php

declare(strict_types=1);

namespace Sabre\DAV\FSExt;

use Sabre\DAV;
use Sabre\DAV\FS\Node;

/**
 * File class.
 *
 * @copyright Copyright (C) fruux GmbH (https://fruux.com/)
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
class File extends Node implements DAV\PartialUpdate\IPatchSupport
{
    /**
     * Updates the data.
     *
     * Data is a readable stream resource.
     *
     * @param resource|string $data
     *
     * @return string
     */
    public function put($data)
    {
        file_put_contents($this->path, $data);
        clearstatcache(true, $this->path);

        return $this->getETag();
    }

    /**
     * Updates the file based on a range specification.
     *
     * The first argument is the data, which is either a readable stream
     * resource or a string.
     *
     * The second argument is the type of update we're doing.
     * This is either:
     * * 1. append (default)
     * * 2. update based on a start byte
     * * 3. update based on an end byte
     *;
     * The third argument is the start or end byte.
     *
     * After a successful put operation, you may choose to return an ETag. The
     * ETAG must always be surrounded by double-quotes. These quotes must
     * appear in the actual string you're returning.
     *
     * Clients may use the ETag from a PUT request to later on make sure that
     * when they update the file, the contents haven't changed in the mean
     * time.
     *
     * @param resource|string $data
     * @param int             $rangeType
     * @param int             $offset
     *
     * @return string|null
     */
    public function patch($data, $rangeType, $offset = null)
    {
        switch ($rangeType) {
            case 1:
                $f = fopen($this->path, 'a');
                break;
            case 2:
                $f = fopen($this->path, 'c');
                fseek($f, $offset);
                break;
            case 3:
                $f = fopen($this->path, 'c');
                fseek($f, $offset, SEEK_END);
                break;
            default:
                $f = fopen($this->path, 'a');
                break;
        }
        if (is_string($data)) {
            fwrite($f, $data);
        } else {
            stream_copy_to_stream($data, $f);
        }
        fclose($f);
        clearstatcache(true, $this->path);

        return $this->getETag();
    }

    /**
     * Returns the data.
     *
     * @return resource
     */
    public function get()
    {
        return fopen($this->path, 'r');
    }

    /**
     * Delete the current file.
     *
     * @return bool
     */
    public function delete()
    {
        return unlink($this->path);
    }

    /**
     * Returns the ETag for a file.
     *
     * An ETag is a unique identifier representing the current version of the file. If the file changes, the ETag MUST change.
     * The ETag is an arbitrary string, but MUST be surrounded by double-quotes.
     *
     * Return null if the ETag can not effectively be determined
     *
     * @return string|null
     */
    public function getETag()
    {
        return '"'.sha1(
            fileinode($this->path).
            filesize($this->path).
            filemtime($this->path)
        ).'"';
    }

    /**
     * Returns the mime-type for a file.
     *
     * If null is returned, we'll assume application/octet-stream
     *
     * @return string|null
     */
    public function getContentType()
    {
        return null;
    }

    /**
     * Returns the size of the file, in bytes.
     *
     * @return int
     */
    public function getSize()
    {
        return filesize($this->path);
    }
}
