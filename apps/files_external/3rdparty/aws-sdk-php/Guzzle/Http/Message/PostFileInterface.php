<?php

namespace Guzzle\Http\Message;

use Guzzle\Common\Exception\InvalidArgumentException;

/**
 * POST file upload
 */
interface PostFileInterface
{
    /**
     * Set the name of the field
     *
     * @param string $name Field name
     *
     * @return self
     */
    public function setFieldName($name);

    /**
     * Get the name of the field
     *
     * @return string
     */
    public function getFieldName();

    /**
     * Set the path to the file
     *
     * @param string $path Full path to the file
     *
     * @return self
     * @throws InvalidArgumentException if the file cannot be read
     */
    public function setFilename($path);

    /**
     * Set the post name of the file
     *
     * @param string $name The new name of the file
     *
     * @return self
     */
    public function setPostname($name);

    /**
     * Get the full path to the file
     *
     * @return string
     */
    public function getFilename();

    /**
     * Get the post name of the file
     *
     * @return string
     */
    public function getPostname();

    /**
     * Set the Content-Type of the file
     *
     * @param string $type Content type
     *
     * @return self
     */
    public function setContentType($type);

    /**
     * Get the Content-Type of the file
     *
     * @return string
     */
    public function getContentType();

    /**
     * Get a cURL ready string or CurlFile object for the upload
     *
     * @return string
     */
    public function getCurlValue();
}
