<?php

declare(strict_types=1);

namespace Sabre\DAV;

/**
 * File class.
 *
 * This is a helper class, that should aid in getting file classes setup.
 * Most of its methods are implemented, and throw permission denied exceptions
 *
 * @copyright Copyright (C) fruux GmbH (https://fruux.com/)
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
abstract class File extends Node implements IFile
{
    /**
     * Replaces the contents of the file.
     *
     * The data argument is a readable stream resource.
     *
     * After a successful put operation, you may choose to return an ETag. The
     * etag must always be surrounded by double-quotes. These quotes must
     * appear in the actual string you're returning.
     *
     * Clients may use the ETag from a PUT request to later on make sure that
     * when they update the file, the contents haven't changed in the mean
     * time.
     *
     * If you don't plan to store the file byte-by-byte, and you return a
     * different object on a subsequent GET you are strongly recommended to not
     * return an ETag, and just return null.
     *
     * @param string|resource $data
     *
     * @return string|null
     */
    public function put($data)
    {
        throw new Exception\Forbidden('Permission denied to change data');
    }

    /**
     * Returns the data.
     *
     * This method may either return a string or a readable stream resource
     *
     * @return mixed
     */
    public function get()
    {
        throw new Exception\Forbidden('Permission denied to read this file');
    }

    /**
     * Returns the size of the file, in bytes.
     *
     * @return int
     */
    public function getSize()
    {
        return 0;
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
        return null;
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
}
