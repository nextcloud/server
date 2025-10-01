<?php

declare(strict_types=1);

namespace Sabre\DAV\PartialUpdate;

use Sabre\DAV;

/**
 * This interface provides a way to modify only part of a target resource
 * It may be used to update a file chunk, upload big a file into smaller
 * chunks or resume an upload.
 *
 * @copyright Copyright (C) fruux GmbH (https://fruux.com/)
 * @author Jean-Tiare LE BIGOT (http://www.jtlebi.fr/)
 * @license http://sabre.io/license/ Modified BSD License
 */
interface IPatchSupport extends DAV\IFile
{
    /**
     * Updates the file based on a range specification.
     *
     * The first argument is the data, which is either a readable stream
     * resource or a string.
     *
     * The second argument is the type of update we're doing.
     * This is either:
     * * 1. append
     * * 2. update based on a start byte
     * * 3. update based on an end byte
     *;
     * The third argument is the start or end byte.
     *
     * After a successful put operation, you may choose to return an ETag. The
     * etag must always be surrounded by double-quotes. These quotes must
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
    public function patch($data, $rangeType, $offset = null);
}
