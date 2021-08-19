<?php

declare(strict_types=1);

namespace Sabre\DAV;

/**
 * SimpleFile.
 *
 * The 'SimpleFile' class is used to easily add read-only immutable files to
 * the directory structure. One usecase would be to add a 'readme.txt' to a
 * root of a webserver with some standard content.
 *
 * @copyright Copyright (C) fruux GmbH (https://fruux.com/)
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
class SimpleFile extends File
{
    /**
     * File contents.
     *
     * @var string
     */
    protected $contents = [];

    /**
     * Name of this resource.
     *
     * @var string
     */
    protected $name;

    /**
     * A mimetype, such as 'text/plain' or 'text/html'.
     *
     * @var string
     */
    protected $mimeType;

    /**
     * Creates this node.
     *
     * The name of the node must be passed, as well as the contents of the
     * file.
     *
     * @param string      $name
     * @param string      $contents
     * @param string|null $mimeType
     */
    public function __construct($name, $contents, $mimeType = null)
    {
        $this->name = $name;
        $this->contents = $contents;
        $this->mimeType = $mimeType;
    }

    /**
     * Returns the node name for this file.
     *
     * This name is used to construct the url.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
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
        return $this->contents;
    }

    /**
     * Returns the size of the file, in bytes.
     *
     * @return int
     */
    public function getSize()
    {
        return strlen($this->contents);
    }

    /**
     * Returns the ETag for a file.
     *
     * An ETag is a unique identifier representing the current version of the file. If the file changes, the ETag MUST change.
     * The ETag is an arbitrary string, but MUST be surrounded by double-quotes.
     *
     * Return null if the ETag can not effectively be determined
     *
     * @return string
     */
    public function getETag()
    {
        return '"'.sha1($this->contents).'"';
    }

    /**
     * Returns the mime-type for a file.
     *
     * If null is returned, we'll assume application/octet-stream
     *
     * @return string
     */
    public function getContentType()
    {
        return $this->mimeType;
    }
}
