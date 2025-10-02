<?php

declare(strict_types=1);

namespace SpomkyLabs\Pki\X509\CertificationPath;

use ArrayIterator;
use Countable;
use IteratorAggregate;
use LogicException;
use SpomkyLabs\Pki\CryptoBridge\Crypto;
use SpomkyLabs\Pki\X509\Certificate\Certificate;
use SpomkyLabs\Pki\X509\Certificate\CertificateBundle;
use SpomkyLabs\Pki\X509\Certificate\CertificateChain;
use SpomkyLabs\Pki\X509\CertificationPath\PathBuilding\CertificationPathBuilder;
use SpomkyLabs\Pki\X509\CertificationPath\PathValidation\PathValidationConfig;
use SpomkyLabs\Pki\X509\CertificationPath\PathValidation\PathValidationResult;
use SpomkyLabs\Pki\X509\CertificationPath\PathValidation\PathValidator;
use function count;

/**
 * Implements certification path structure.
 *
 * Certification path is a list of certificates from the trust anchor to the end entity certificate, possibly spanning
 * over multiple intermediate certificates.
 *
 * @see https://tools.ietf.org/html/rfc5280#section-3.2
 */
final class CertificationPath implements Countable, IteratorAggregate
{
    /**
     * Certification path.
     *
     * @var Certificate[]
     */
    private readonly array $certificates;

    /**
     * @param Certificate ...$certificates Certificates from the trust anchor
     * to the target end-entity certificate
     */
    private function __construct(Certificate ...$certificates)
    {
        $this->certificates = $certificates;
    }

    public static function create(Certificate ...$certificates): self
    {
        return new self(...$certificates);
    }

    /**
     * Initialize from a certificate chain.
     */
    public static function fromCertificateChain(CertificateChain $chain): self
    {
        return self::create(...array_reverse($chain->certificates(), false));
    }

    /**
     * Build certification path to given target.
     *
     * @param Certificate $target Target end-entity certificate
     * @param CertificateBundle $trust_anchors List of trust anchors
     * @param null|CertificateBundle $intermediate Optional intermediate certificates
     */
    public static function toTarget(
        Certificate $target,
        CertificateBundle $trust_anchors,
        ?CertificateBundle $intermediate = null
    ): self {
        return CertificationPathBuilder::create($trust_anchors)->shortestPathToTarget($target, $intermediate);
    }

    /**
     * Build certification path from given trust anchor to target certificate, using intermediate certificates from
     * given bundle.
     *
     * @param Certificate $trust_anchor Trust anchor certificate
     * @param Certificate $target Target end-entity certificate
     * @param null|CertificateBundle $intermediate Optional intermediate certificates
     */
    public static function fromTrustAnchorToTarget(
        Certificate $trust_anchor,
        Certificate $target,
        ?CertificateBundle $intermediate = null
    ): self {
        return self::toTarget($target, CertificateBundle::create($trust_anchor), $intermediate);
    }

    /**
     * Get certificates.
     *
     * @return Certificate[]
     */
    public function certificates(): array
    {
        return $this->certificates;
    }

    /**
     * Get the trust anchor certificate from the path.
     */
    public function trustAnchorCertificate(): Certificate
    {
        if (count($this->certificates) === 0) {
            throw new LogicException('No certificates.');
        }
        return $this->certificates[0];
    }

    /**
     * Get the end-entity certificate from the path.
     */
    public function endEntityCertificate(): Certificate
    {
        if (count($this->certificates) === 0) {
            throw new LogicException('No certificates.');
        }
        return $this->certificates[count($this->certificates) - 1];
    }

    /**
     * Get certification path as a certificate chain.
     */
    public function certificateChain(): CertificateChain
    {
        return CertificateChain::create(...array_reverse($this->certificates, false));
    }

    /**
     * Check whether certification path starts with one ore more given certificates in parameter order.
     *
     * @param Certificate ...$certs Certificates
     */
    public function startsWith(Certificate ...$certs): bool
    {
        $n = count($certs);
        if ($n > count($this->certificates)) {
            return false;
        }
        for ($i = 0; $i < $n; ++$i) {
            if (! $certs[$i]->equals($this->certificates[$i])) {
                return false;
            }
        }
        return true;
    }

    /**
     * Validate certification path.
     *
     * @param null|Crypto $crypto Crypto engine, use default if not set
     */
    public function validate(PathValidationConfig $config, ?Crypto $crypto = null): PathValidationResult
    {
        $crypto ??= Crypto::getDefault();
        return PathValidator::create($crypto, $config, ...$this->certificates)->validate();
    }

    /**
     * @see \Countable::count()
     */
    public function count(): int
    {
        return count($this->certificates);
    }

    /**
     * Get iterator for certificates.
     *
     * @see \IteratorAggregate::getIterator()
     */
    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator($this->certificates);
    }
}
