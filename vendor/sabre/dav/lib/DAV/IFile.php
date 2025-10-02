<?php

declare(strict_types=1);

namespace Sabre\DAV;

/**
 * This interface represents a file in the directory tree.
 *
 * A file is a bit of a broad definition. In general it implies that on
 * this specific node a PUT or GET method may be performed, to either update,
 * or retrieve the contents of the file.
 *
 * @copyright Copyright (C) fruux GmbH (https://fruux.com/)
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
interface IFile extends INode
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
     * @param resource|string $data
     *
     * @return string|null
     */
    public function put($data);

    /**
     * Returns the data.
     *
     * This method may either return a string or a readable stream resource
     *
     * @return mixed
     */
    public function get();

    /**
     * Returns the mime-type for a file.
     *
     * If null is returned, we'll assume application/octet-stream
     *
     * @return string|null
     */
    public function getContentType();

    /**
     * Returns the ETag for a file.
     *
     * An ETag is a unique identifier representing the current version of the file. If the file changes, the ETag MUST change.
     *
     * Return null if the ETag can not effectively be determined.
     *
     * The ETag must be surrounded by double-quotes, so something like this
     * would make a valid ETag:
     *
     *   return '"someetag"';
     *
     * @return string|null
     */
    public function getETag();

    /**
     * Returns the size of the node, in bytes.
     *
     * @return int
     */
    public function getSize();
}
