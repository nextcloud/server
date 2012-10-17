<?php

/**
 * This interface provides a way to modify only part of a target resource
 * It may be used to update a file chunk, upload big a file into smaller
 * chunks or resume an upload
 *
 * @package Sabre
 * @subpackage DAV
 * @copyright Copyright (C) 2007-2012 Rooftop Solutions. All rights reserved.
 * @author Jean-Tiare LE BIGOT (http://www.jtlebi.fr/)
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
interface Sabre_DAV_PartialUpdate_IFile extends Sabre_DAV_IFile {

    /**
     * Updates the data at a given offset
     *
     * The data argument is a readable stream resource.
     * The offset argument is an integer describing the offset. Contrary to
     * what's sent in the request, the offset here is a 0-based index.
     *
     * After a successful put operation, you may choose to return an ETag. The
     * etag must always be surrounded by double-quotes. These quotes must
     * appear in the actual string you're returning.
     *
     * Clients may use the ETag from a PUT request to later on make sure that
     * when they update the file, the contents haven't changed in the mean
     * time.
     *
     * @param resource $data
     * @param integer $offset
     * @return string|null
     */
    function putRange($data, $offset);

}

