<?php

declare(strict_types=1);

namespace SpomkyLabs\Pki\X509\Certificate;

use ArrayIterator;
use Countable;
use IteratorAggregate;
use LogicException;
use SpomkyLabs\Pki\CryptoEncoding\PEM;
use SpomkyLabs\Pki\CryptoEncoding\PEMBundle;
use SpomkyLabs\Pki\X509\CertificationPath\CertificationPath;
use function count;

/**
 * Ordered list of certificates from the end-entity to the trust anchor.
 */
final class CertificateChain implements Countable, IteratorAggregate
{
    /**
     * List of certificates in a chain.
     *
     * @var Certificate[]
     */
    private readonly array $certs;

    /**
     * @param Certificate ...$certs List of certificates, end-entity first
     */
    private function __construct(Certificate ...$certs)
    {
        $this->certs = $certs;
    }

    public static function create(Certificate ...$certs): self
    {
        return new self(...$certs);
    }

    /**
     * Initialize from a list of PEMs.
     */
    public static function fromPEMs(PEM ...$pems): self
    {
        $certs = array_map(static fn (PEM $pem) => Certificate::fromPEM($pem), $pems);
        return self::create(...$certs);
    }

    /**
     * Initialize from a string containing multiple PEM blocks.
     */
    public static function fromPEMString(string $str): self
    {
        $pems = PEMBundle::fromString($str)->all();
        return self::fromPEMs(...$pems);
    }

    /**
     * Get all certificates in a chain ordered from the end-entity certificate to the trust anchor.
     *
     * @return Certificate[]
     */
    public function certificates(): array
    {
        return $this->certs;
    }

    /**
     * Get the end-entity certificate.
     */
    public function endEntityCertificate(): Certificate
    {
        if (count($this->certs) === 0) {
            throw new LogicException('No certificates.');
        }
        return $this->certs[0];
    }

    /**
     * Get the trust anchor certificate.
     */
    public function trustAnchorCertificate(): Certificate
    {
        if (count($this->certs) === 0) {
            throw new LogicException('No certificates.');
        }
        return $this->certs[count($this->certs) - 1];
    }

    /**
     * Convert certificate chain to certification path.
     */
    public function certificationPath(): CertificationPath
    {
        return CertificationPath::fromCertificateChain($this);
    }

    /**
     * Convert certificate chain to string of PEM blocks.
     */
    public function toPEMString(): string
    {
        return implode("\n", array_map(static fn (Certificate $cert) => $cert->toPEM()->string(), $this->certs));
    }

    /**
     * @see \Countable::count()
     */
    public function count(): int
    {
        return count($this->certs);
    }

    /**
     * Get iterator for certificates.
     *
     * @see \IteratorAggregate::getIterator()
     */
    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator($this->certs);
    }
}
