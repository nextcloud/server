<?php

declare(strict_types=1);

namespace SpomkyLabs\Pki\CryptoEncoding;

use RuntimeException;
use Stringable;
use UnexpectedValueException;
use function is_string;

/**
 * Implements PEM file encoding and decoding.
 *
 * @see https://tools.ietf.org/html/rfc7468
 */
final class PEM implements Stringable
{
    // well-known PEM types
    final public const TYPE_CERTIFICATE = 'CERTIFICATE';

    final public const TYPE_CRL = 'X509 CRL';

    final public const TYPE_CERTIFICATE_REQUEST = 'CERTIFICATE REQUEST';

    final public const TYPE_ATTRIBUTE_CERTIFICATE = 'ATTRIBUTE CERTIFICATE';

    final public const TYPE_PRIVATE_KEY = 'PRIVATE KEY';

    final public const TYPE_PUBLIC_KEY = 'PUBLIC KEY';

    final public const TYPE_ENCRYPTED_PRIVATE_KEY = 'ENCRYPTED PRIVATE KEY';

    final public const TYPE_RSA_PRIVATE_KEY = 'RSA PRIVATE KEY';

    final public const TYPE_RSA_PUBLIC_KEY = 'RSA PUBLIC KEY';

    final public const TYPE_EC_PRIVATE_KEY = 'EC PRIVATE KEY';

    final public const TYPE_PKCS7 = 'PKCS7';

    final public const TYPE_CMS = 'CMS';

    /**
     * Regular expression to match PEM block.
     *
     * @var string
     */
    final public const PEM_REGEX = '/' .
    /* line start */
    '(?:^|[\r\n])' .
    /* header */
    '-----BEGIN (.+?)-----[\r\n]+' .
    /* payload */
    '(.+?)' .
    /* trailer */
    '[\r\n]+-----END \\1-----' .
    '/ms';

    /**
     * @param string $type Content type
     * @param string $data Payload
     */
    private function __construct(
        private readonly string $type,
        private readonly string $data
    ) {
    }

    public function __toString(): string
    {
        return $this->string();
    }

    public static function create(string $_type, string $_data): self
    {
        return new self($_type, $_data);
    }

    /**
     * Initialize from a PEM-formatted string.
     */
    public static function fromString(string $str): self
    {
        if (preg_match(self::PEM_REGEX, $str, $match) !== 1) {
            throw new UnexpectedValueException('Not a PEM formatted string.');
        }
        $payload = preg_replace('/\s+/', '', $match[2]);
        if (! is_string($payload)) {
            throw new UnexpectedValueException('Failed to decode PEM data.');
        }
        $data = base64_decode($payload, true);
        if ($data === false) {
            throw new UnexpectedValueException('Failed to decode PEM data.');
        }
        return self::create($match[1], $data);
    }

    /**
     * Initialize from a file.
     *
     * @param string $filename Path to file
     */
    public static function fromFile(string $filename): self
    {
        if (! is_readable($filename)) {
            throw new RuntimeException("Failed to read {$filename}.");
        }
        $str = file_get_contents($filename);
        if ($str === false) {
            throw new RuntimeException("Failed to read {$filename}.");
        }
        return self::fromString($str);
    }

    /**
     * Get content type.
     */
    public function type(): string
    {
        return $this->type;
    }

    public function data(): string
    {
        return $this->data;
    }

    /**
     * Encode to PEM string.
     */
    public function string(): string
    {
        return "-----BEGIN {$this->type}-----\n" .
            trim(chunk_split(base64_encode($this->data), 64, "\n")) . "\n" .
            "-----END {$this->type}-----";
    }
}
