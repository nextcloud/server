<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Mime\Part;

use Symfony\Component\Mime\Exception\InvalidArgumentException;
use Symfony\Component\Mime\Header\Headers;

/**
 * @author Fabien Potencier <fabien@symfony.com>
 */
class DataPart extends TextPart
{
    /** @internal */
    protected array $_parent;

    private ?string $filename = null;
    private string $mediaType;
    private ?string $cid = null;

    /**
     * @param resource|string|File $body Use a File instance to defer loading the file until rendering
     */
    public function __construct($body, ?string $filename = null, ?string $contentType = null, ?string $encoding = null)
    {
        if ($body instanceof File && !$filename) {
            $filename = $body->getFilename();
        }

        $contentType ??= $body instanceof File ? $body->getContentType() : 'application/octet-stream';
        [$this->mediaType, $subtype] = explode('/', $contentType);

        parent::__construct($body, null, $subtype, $encoding);

        if (null !== $filename) {
            $this->filename = $filename;
            $this->setName($filename);
        }
        $this->setDisposition('attachment');
    }

    public static function fromPath(string $path, ?string $name = null, ?string $contentType = null): self
    {
        return new self(new File($path), $name, $contentType);
    }

    /**
     * @return $this
     */
    public function asInline(): static
    {
        return $this->setDisposition('inline');
    }

    /**
     * @return $this
     */
    public function setContentId(string $cid): static
    {
        if (!str_contains($cid, '@')) {
            throw new InvalidArgumentException(sprintf('Invalid cid "%s".', $cid));
        }

        $this->cid = $cid;

        return $this;
    }

    public function getContentId(): string
    {
        return $this->cid ?: $this->cid = $this->generateContentId();
    }

    public function hasContentId(): bool
    {
        return null !== $this->cid;
    }

    public function getMediaType(): string
    {
        return $this->mediaType;
    }

    public function getPreparedHeaders(): Headers
    {
        $headers = parent::getPreparedHeaders();

        if (null !== $this->cid) {
            $headers->setHeaderBody('Id', 'Content-ID', $this->cid);
        }

        if (null !== $this->filename) {
            $headers->setHeaderParameter('Content-Disposition', 'filename', $this->filename);
        }

        return $headers;
    }

    public function asDebugString(): string
    {
        $str = parent::asDebugString();
        if (null !== $this->filename) {
            $str .= ' filename: '.$this->filename;
        }

        return $str;
    }

    public function getFilename(): ?string
    {
        return $this->filename;
    }

    public function getContentType(): string
    {
        return implode('/', [$this->getMediaType(), $this->getMediaSubtype()]);
    }

    private function generateContentId(): string
    {
        return bin2hex(random_bytes(16)).'@symfony';
    }

    public function __sleep(): array
    {
        // converts the body to a string
        parent::__sleep();

        $this->_parent = [];
        foreach (['body', 'charset', 'subtype', 'disposition', 'name', 'encoding'] as $name) {
            $r = new \ReflectionProperty(TextPart::class, $name);
            $this->_parent[$name] = $r->getValue($this);
        }
        $this->_headers = $this->getHeaders();

        return ['_headers', '_parent', 'filename', 'mediaType'];
    }

    /**
     * @return void
     */
    public function __wakeup()
    {
        $r = new \ReflectionProperty(AbstractPart::class, 'headers');
        $r->setValue($this, $this->_headers);
        unset($this->_headers);

        if (!\is_array($this->_parent)) {
            throw new \BadMethodCallException('Cannot unserialize '.__CLASS__);
        }
        foreach (['body', 'charset', 'subtype', 'disposition', 'name', 'encoding'] as $name) {
            if (null !== $this->_parent[$name] && !\is_string($this->_parent[$name]) && !$this->_parent[$name] instanceof File) {
                throw new \BadMethodCallException('Cannot unserialize '.__CLASS__);
            }
            $r = new \ReflectionProperty(TextPart::class, $name);
            $r->setValue($this, $this->_parent[$name]);
        }
        unset($this->_parent);
    }
}
