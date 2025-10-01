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

use Symfony\Component\Mime\Encoder\Base64ContentEncoder;
use Symfony\Component\Mime\Encoder\ContentEncoderInterface;
use Symfony\Component\Mime\Encoder\EightBitContentEncoder;
use Symfony\Component\Mime\Encoder\QpContentEncoder;
use Symfony\Component\Mime\Exception\InvalidArgumentException;
use Symfony\Component\Mime\Header\Headers;

/**
 * @author Fabien Potencier <fabien@symfony.com>
 */
class TextPart extends AbstractPart
{
    /** @internal */
    protected Headers $_headers;

    private static array $encoders = [];

    /** @var resource|string|File */
    private $body;
    private ?string $charset;
    private string $subtype;
    private ?string $disposition = null;
    private ?string $name = null;
    private string $encoding;
    private ?bool $seekable = null;

    /**
     * @param resource|string|File $body Use a File instance to defer loading the file until rendering
     */
    public function __construct($body, ?string $charset = 'utf-8', string $subtype = 'plain', ?string $encoding = null)
    {
        parent::__construct();

        if (!\is_string($body) && !\is_resource($body) && !$body instanceof File) {
            throw new \TypeError(sprintf('The body of "%s" must be a string, a resource, or an instance of "%s" (got "%s").', self::class, File::class, get_debug_type($body)));
        }

        if ($body instanceof File) {
            $path = $body->getPath();
            if ((is_file($path) && !is_readable($path)) || is_dir($path)) {
                throw new InvalidArgumentException(sprintf('Path "%s" is not readable.', $path));
            }
        }

        $this->body = $body;
        $this->charset = $charset;
        $this->subtype = $subtype;
        $this->seekable = \is_resource($body) ? stream_get_meta_data($body)['seekable'] && 0 === fseek($body, 0, \SEEK_CUR) : null;

        if (null === $encoding) {
            $this->encoding = $this->chooseEncoding();
        } else {
            if ('quoted-printable' !== $encoding && 'base64' !== $encoding && '8bit' !== $encoding) {
                throw new InvalidArgumentException(sprintf('The encoding must be one of "quoted-printable", "base64", or "8bit" ("%s" given).', $encoding));
            }
            $this->encoding = $encoding;
        }
    }

    public function getMediaType(): string
    {
        return 'text';
    }

    public function getMediaSubtype(): string
    {
        return $this->subtype;
    }

    /**
     * @param string $disposition one of attachment, inline, or form-data
     *
     * @return $this
     */
    public function setDisposition(string $disposition): static
    {
        $this->disposition = $disposition;

        return $this;
    }

    /**
     * @return ?string null or one of attachment, inline, or form-data
     */
    public function getDisposition(): ?string
    {
        return $this->disposition;
    }

    /**
     * Sets the name of the file (used by FormDataPart).
     *
     * @return $this
     */
    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Gets the name of the file.
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    public function getBody(): string
    {
        if ($this->body instanceof File) {
            if (false === $ret = @file_get_contents($this->body->getPath())) {
                throw new InvalidArgumentException(error_get_last()['message']);
            }

            return $ret;
        }

        if (null === $this->seekable) {
            return $this->body;
        }

        if ($this->seekable) {
            rewind($this->body);
        }

        return stream_get_contents($this->body) ?: '';
    }

    public function bodyToString(): string
    {
        return $this->getEncoder()->encodeString($this->getBody(), $this->charset);
    }

    public function bodyToIterable(): iterable
    {
        if ($this->body instanceof File) {
            $path = $this->body->getPath();
            if (false === $handle = @fopen($path, 'r', false)) {
                throw new InvalidArgumentException(sprintf('Unable to open path "%s".', $path));
            }

            yield from $this->getEncoder()->encodeByteStream($handle);
        } elseif (null !== $this->seekable) {
            if ($this->seekable) {
                rewind($this->body);
            }
            yield from $this->getEncoder()->encodeByteStream($this->body);
        } else {
            yield $this->getEncoder()->encodeString($this->body);
        }
    }

    public function getPreparedHeaders(): Headers
    {
        $headers = parent::getPreparedHeaders();

        $headers->setHeaderBody('Parameterized', 'Content-Type', $this->getMediaType().'/'.$this->getMediaSubtype());
        if ($this->charset) {
            $headers->setHeaderParameter('Content-Type', 'charset', $this->charset);
        }
        if ($this->name && 'form-data' !== $this->disposition) {
            $headers->setHeaderParameter('Content-Type', 'name', $this->name);
        }
        $headers->setHeaderBody('Text', 'Content-Transfer-Encoding', $this->encoding);

        if (!$headers->has('Content-Disposition') && null !== $this->disposition) {
            $headers->setHeaderBody('Parameterized', 'Content-Disposition', $this->disposition);
            if ($this->name) {
                $headers->setHeaderParameter('Content-Disposition', 'name', $this->name);
            }
        }

        return $headers;
    }

    public function asDebugString(): string
    {
        $str = parent::asDebugString();
        if (null !== $this->charset) {
            $str .= ' charset: '.$this->charset;
        }
        if (null !== $this->disposition) {
            $str .= ' disposition: '.$this->disposition;
        }

        return $str;
    }

    private function getEncoder(): ContentEncoderInterface
    {
        if ('8bit' === $this->encoding) {
            return self::$encoders[$this->encoding] ??= new EightBitContentEncoder();
        }

        if ('quoted-printable' === $this->encoding) {
            return self::$encoders[$this->encoding] ??= new QpContentEncoder();
        }

        return self::$encoders[$this->encoding] ??= new Base64ContentEncoder();
    }

    private function chooseEncoding(): string
    {
        if (null === $this->charset) {
            return 'base64';
        }

        return 'quoted-printable';
    }

    public function __sleep(): array
    {
        // convert resources to strings for serialization
        if (null !== $this->seekable) {
            $this->body = $this->getBody();
            $this->seekable = null;
        }

        $this->_headers = $this->getHeaders();

        return ['_headers', 'body', 'charset', 'subtype', 'disposition', 'name', 'encoding'];
    }

    /**
     * @return void
     */
    public function __wakeup()
    {
        $r = new \ReflectionProperty(AbstractPart::class, 'headers');
        $r->setValue($this, $this->_headers);
        unset($this->_headers);
    }
}
