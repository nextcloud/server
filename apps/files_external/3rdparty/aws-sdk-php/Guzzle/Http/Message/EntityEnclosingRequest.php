<?php

namespace Guzzle\Http\Message;

use Guzzle\Http\EntityBody;
use Guzzle\Http\EntityBodyInterface;
use Guzzle\Http\QueryString;
use Guzzle\Http\RedirectPlugin;
use Guzzle\Http\Exception\RequestException;

/**
 * HTTP request that sends an entity-body in the request message (POST, PUT, PATCH, DELETE)
 */
class EntityEnclosingRequest extends Request implements EntityEnclosingRequestInterface
{
    /** @var int When the size of the body is greater than 1MB, then send Expect: 100-Continue */
    protected $expectCutoff = 1048576;

    /** @var EntityBodyInterface $body Body of the request */
    protected $body;

    /** @var QueryString POST fields to use in the EntityBody */
    protected $postFields;

    /** @var array POST files to send with the request */
    protected $postFiles = array();

    public function __construct($method, $url, $headers = array())
    {
        $this->postFields = new QueryString();
        parent::__construct($method, $url, $headers);
    }

    /**
     * @return string
     */
    public function __toString()
    {
        // Only attempt to include the POST data if it's only fields
        if (count($this->postFields) && empty($this->postFiles)) {
            return parent::__toString() . (string) $this->postFields;
        }

        return parent::__toString() . $this->body;
    }

    public function setState($state, array $context = array())
    {
        parent::setState($state, $context);
        if ($state == self::STATE_TRANSFER && !$this->body && !count($this->postFields) && !count($this->postFiles)) {
            $this->setHeader('Content-Length', 0)->removeHeader('Transfer-Encoding');
        }

        return $this->state;
    }

    public function setBody($body, $contentType = null)
    {
        $this->body = EntityBody::factory($body);

        // Auto detect the Content-Type from the path of the request if possible
        if ($contentType === null && !$this->hasHeader('Content-Type')) {
            $contentType = $this->body->getContentType();
        }

        if ($contentType) {
            $this->setHeader('Content-Type', $contentType);
        }

        // Always add the Expect 100-Continue header if the body cannot be rewound. This helps with redirects.
        if (!$this->body->isSeekable() && $this->expectCutoff !== false) {
            $this->setHeader('Expect', '100-Continue');
        }

        // Set the Content-Length header if it can be determined
        $size = $this->body->getContentLength();
        if ($size !== null && $size !== false) {
            $this->setHeader('Content-Length', $size);
            if ($size > $this->expectCutoff) {
                $this->setHeader('Expect', '100-Continue');
            }
        } elseif (!$this->hasHeader('Content-Length')) {
            if ('1.1' == $this->protocolVersion) {
                $this->setHeader('Transfer-Encoding', 'chunked');
            } else {
                throw new RequestException(
                    'Cannot determine Content-Length and cannot use chunked Transfer-Encoding when using HTTP/1.0'
                );
            }
        }

        return $this;
    }

    public function getBody()
    {
        return $this->body;
    }

    /**
     * Set the size that the entity body of the request must exceed before adding the Expect: 100-Continue header.
     *
     * @param int|bool $size Cutoff in bytes. Set to false to never send the expect header (even with non-seekable data)
     *
     * @return self
     */
    public function setExpectHeaderCutoff($size)
    {
        $this->expectCutoff = $size;
        if ($size === false || !$this->body) {
            $this->removeHeader('Expect');
        } elseif ($this->body && $this->body->getSize() && $this->body->getSize() > $size) {
            $this->setHeader('Expect', '100-Continue');
        }

        return $this;
    }

    public function configureRedirects($strict = false, $maxRedirects = 5)
    {
        $this->getParams()->set(RedirectPlugin::STRICT_REDIRECTS, $strict);
        if ($maxRedirects == 0) {
            $this->getParams()->set(RedirectPlugin::DISABLE, true);
        } else {
            $this->getParams()->set(RedirectPlugin::MAX_REDIRECTS, $maxRedirects);
        }

        return $this;
    }

    public function getPostField($field)
    {
        return $this->postFields->get($field);
    }

    public function getPostFields()
    {
        return $this->postFields;
    }

    public function setPostField($key, $value)
    {
        $this->postFields->set($key, $value);
        $this->processPostFields();

        return $this;
    }

    public function addPostFields($fields)
    {
        $this->postFields->merge($fields);
        $this->processPostFields();

        return $this;
    }

    public function removePostField($field)
    {
        $this->postFields->remove($field);
        $this->processPostFields();

        return $this;
    }

    public function getPostFiles()
    {
        return $this->postFiles;
    }

    public function getPostFile($fieldName)
    {
        return isset($this->postFiles[$fieldName]) ? $this->postFiles[$fieldName] : null;
    }

    public function removePostFile($fieldName)
    {
        unset($this->postFiles[$fieldName]);
        $this->processPostFields();

        return $this;
    }

    public function addPostFile($field, $filename = null, $contentType = null, $postname = null)
    {
        $data = null;

        if ($field instanceof PostFileInterface) {
            $data = $field;
        } elseif (is_array($filename)) {
            // Allow multiple values to be set in a single key
            foreach ($filename as $file) {
                $this->addPostFile($field, $file, $contentType);
            }
            return $this;
        } elseif (!is_string($filename)) {
            throw new RequestException('The path to a file must be a string');
        } elseif (!empty($filename)) {
            // Adding an empty file will cause cURL to error out
            $data = new PostFile($field, $filename, $contentType, $postname);
        }

        if ($data) {
            if (!isset($this->postFiles[$data->getFieldName()])) {
                $this->postFiles[$data->getFieldName()] = array($data);
            } else {
                $this->postFiles[$data->getFieldName()][] = $data;
            }
            $this->processPostFields();
        }

        return $this;
    }

    public function addPostFiles(array $files)
    {
        foreach ($files as $key => $file) {
            if ($file instanceof PostFileInterface) {
                $this->addPostFile($file, null, null, false);
            } elseif (is_string($file)) {
                // Convert non-associative array keys into 'file'
                if (is_numeric($key)) {
                    $key = 'file';
                }
                $this->addPostFile($key, $file, null, false);
            } else {
                throw new RequestException('File must be a string or instance of PostFileInterface');
            }
        }

        return $this;
    }

    /**
     * Determine what type of request should be sent based on post fields
     */
    protected function processPostFields()
    {
        if (!$this->postFiles) {
            $this->removeHeader('Expect')->setHeader('Content-Type', self::URL_ENCODED);
        } else {
            $this->setHeader('Content-Type', self::MULTIPART);
            if ($this->expectCutoff !== false) {
                $this->setHeader('Expect', '100-Continue');
            }
        }
    }
}
