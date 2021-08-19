<?php

/*
 * This file is part of SwiftMailer.
 * (c) 2004-2009 Chris Corbyn
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * An attachment, in a multipart message.
 *
 * @author Chris Corbyn
 */
class Swift_Mime_Attachment extends Swift_Mime_SimpleMimeEntity
{
    /** Recognized MIME types */
    private $mimeTypes = [];

    /**
     * Create a new Attachment with $headers, $encoder and $cache.
     *
     * @param array $mimeTypes
     */
    public function __construct(Swift_Mime_SimpleHeaderSet $headers, Swift_Mime_ContentEncoder $encoder, Swift_KeyCache $cache, Swift_IdGenerator $idGenerator, $mimeTypes = [])
    {
        parent::__construct($headers, $encoder, $cache, $idGenerator);
        $this->setDisposition('attachment');
        $this->setContentType('application/octet-stream');
        $this->mimeTypes = $mimeTypes;
    }

    /**
     * Get the nesting level used for this attachment.
     *
     * Always returns {@link LEVEL_MIXED}.
     *
     * @return int
     */
    public function getNestingLevel()
    {
        return self::LEVEL_MIXED;
    }

    /**
     * Get the Content-Disposition of this attachment.
     *
     * By default attachments have a disposition of "attachment".
     *
     * @return string
     */
    public function getDisposition()
    {
        return $this->getHeaderFieldModel('Content-Disposition');
    }

    /**
     * Set the Content-Disposition of this attachment.
     *
     * @param string $disposition
     *
     * @return $this
     */
    public function setDisposition($disposition)
    {
        if (!$this->setHeaderFieldModel('Content-Disposition', $disposition)) {
            $this->getHeaders()->addParameterizedHeader('Content-Disposition', $disposition);
        }

        return $this;
    }

    /**
     * Get the filename of this attachment when downloaded.
     *
     * @return string
     */
    public function getFilename()
    {
        return $this->getHeaderParameter('Content-Disposition', 'filename');
    }

    /**
     * Set the filename of this attachment.
     *
     * @param string $filename
     *
     * @return $this
     */
    public function setFilename($filename)
    {
        $this->setHeaderParameter('Content-Disposition', 'filename', $filename);
        $this->setHeaderParameter('Content-Type', 'name', $filename);

        return $this;
    }

    /**
     * Get the file size of this attachment.
     *
     * @return int
     */
    public function getSize()
    {
        return $this->getHeaderParameter('Content-Disposition', 'size');
    }

    /**
     * Set the file size of this attachment.
     *
     * @param int $size
     *
     * @return $this
     */
    public function setSize($size)
    {
        $this->setHeaderParameter('Content-Disposition', 'size', $size);

        return $this;
    }

    /**
     * Set the file that this attachment is for.
     *
     * @param string $contentType optional
     *
     * @return $this
     */
    public function setFile(Swift_FileStream $file, $contentType = null)
    {
        $this->setFilename(basename($file->getPath()));
        $this->setBody($file, $contentType);
        if (!isset($contentType)) {
            $extension = strtolower(substr($file->getPath(), strrpos($file->getPath(), '.') + 1));

            if (\array_key_exists($extension, $this->mimeTypes)) {
                $this->setContentType($this->mimeTypes[$extension]);
            }
        }

        return $this;
    }
}
