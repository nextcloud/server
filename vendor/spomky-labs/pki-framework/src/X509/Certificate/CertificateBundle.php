<?php

declare(strict_types=1);

namespace SpomkyLabs\Pki\X509\Certificate;

use ArrayIterator;
use Countable;
use IteratorAggregate;
use SpomkyLabs\Pki\CryptoEncoding\PEM;
use SpomkyLabs\Pki\CryptoEncoding\PEMBundle;
use function count;

/**
 * Implements a list of certificates.
 */
final class CertificateBundle implements Countable, IteratorAggregate
{
    /**
     * Certificates.
     *
     * @var Certificate[]
     */
    private array $certs;

    /**
     * Mapping from public key id to array of certificates.
     *
     * @var null|(Certificate[])[]
     */
    private ?array $keyIdMap = null;

    /**
     * @param Certificate ...$certs Certificate objects
     */
    private function __construct(Certificate ...$certs)
    {
        $this->certs = $certs;
    }

    /**
     * Reset internal cached variables on clone.
     */
    public function __clone()
    {
        $this->keyIdMap = null;
    }

    public static function create(Certificate ...$certs): self
    {
        return new self(...$certs);
    }

    /**
     * Initialize from PEMs.
     *
     * @param PEM ...$pems PEM objects
     */
    public static function fromPEMs(PEM ...$pems): self
    {
        $certs = array_map(static fn ($pem) => Certificate::fromPEM($pem), $pems);
        return self::create(...$certs);
    }

    /**
     * Initialize from PEM bundle.
     */
    public static function fromPEMBundle(PEMBundle $pem_bundle): self
    {
        return self::fromPEMs(...$pem_bundle->all());
    }

    /**
     * Get self with certificates added.
     */
    public function withCertificates(Certificate ...$cert): self
    {
        $obj = clone $this;
        $obj->certs = array_merge($obj->certs, $cert);
        return $obj;
    }

    /**
     * Get self with certificates from PEMBundle added.
     */
    public function withPEMBundle(PEMBundle $pem_bundle): self
    {
        $certs = $this->certs;
        foreach ($pem_bundle as $pem) {
            $certs[] = Certificate::fromPEM($pem);
        }
        return self::create(...$certs);
    }

    /**
     * Get self with single certificate from PEM added.
     */
    public function withPEM(PEM $pem): self
    {
        $certs = $this->certs;
        $certs[] = Certificate::fromPEM($pem);
        return self::create(...$certs);
    }

    /**
     * Check whether bundle contains a given certificate.
     */
    public function contains(Certificate $cert): bool
    {
        $id = self::_getCertKeyId($cert);
        $map = $this->_getKeyIdMap();
        if (! isset($map[$id])) {
            return false;
        }
        foreach ($map[$id] as $c) {
            /** @var Certificate $c */
            if ($cert->equals($c)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Get all certificates that have given subject key identifier.
     *
     * @return Certificate[]
     */
    public function allBySubjectKeyIdentifier(string $id): array
    {
        $map = $this->_getKeyIdMap();
        if (! isset($map[$id])) {
            return [];
        }
        return $map[$id];
    }

    /**
     * Get all certificates in a bundle.
     *
     * @return Certificate[]
     */
    public function all(): array
    {
        return $this->certs;
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

    /**
     * Get certificate mapping by public key id.
     *
     * @return (Certificate[])[]
     */
    private function _getKeyIdMap(): array
    {
        // lazily build mapping
        if (! isset($this->keyIdMap)) {
            $this->keyIdMap = [];
            foreach ($this->certs as $cert) {
                $id = self::_getCertKeyId($cert);
                if (! isset($this->keyIdMap[$id])) {
                    $this->keyIdMap[$id] = [];
                }
                array_push($this->keyIdMap[$id], $cert);
            }
        }
        return $this->keyIdMap;
    }

    /**
     * Get public key id for the certificate.
     */
    private static function _getCertKeyId(Certificate $cert): string
    {
        $exts = $cert->tbsCertificate()
            ->extensions();
        if ($exts->hasSubjectKeyIdentifier()) {
            return $exts->subjectKeyIdentifier()
                ->keyIdentifier();
        }
        return $cert->tbsCertificate()
            ->subjectPublicKeyInfo()
            ->keyIdentifier();
    }
}
