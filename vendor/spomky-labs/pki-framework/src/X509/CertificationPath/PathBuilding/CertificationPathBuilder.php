<?php

declare(strict_types=1);

namespace SpomkyLabs\Pki\X509\CertificationPath\PathBuilding;

use SpomkyLabs\Pki\X509\Certificate\Certificate;
use SpomkyLabs\Pki\X509\Certificate\CertificateBundle;
use SpomkyLabs\Pki\X509\CertificationPath\CertificationPath;
use SpomkyLabs\Pki\X509\CertificationPath\Exception\PathBuildingException;
use function count;

/**
 * Class for resolving certification paths.
 *
 * @see https://tools.ietf.org/html/rfc4158
 */
final class CertificationPathBuilder
{
    /**
     * @param CertificateBundle $trustList List of trust anchors
     */
    private function __construct(
        private readonly CertificateBundle $trustList
    ) {
    }

    public static function create(CertificateBundle $trustList): self
    {
        return new self($trustList);
    }

    /**
     * Get all certification paths to given target certificate from any trust anchor.
     *
     * @param Certificate $target Target certificate
     * @param null|CertificateBundle $intermediate Optional intermediate certificates
     *
     * @return CertificationPath[]
     */
    public function allPathsToTarget(Certificate $target, ?CertificateBundle $intermediate = null): array
    {
        $paths = $this->resolvePathsToTarget($target, $intermediate);
        // map paths to CertificationPath objects
        return array_map(static fn ($certs) => CertificationPath::create(...$certs), $paths);
    }

    /**
     * Get the shortest path to given target certificate from any trust anchor.
     *
     * @param Certificate $target Target certificate
     * @param null|CertificateBundle $intermediate Optional intermediate certificates
     */
    public function shortestPathToTarget(
        Certificate $target,
        ?CertificateBundle $intermediate = null
    ): CertificationPath {
        $paths = $this->allPathsToTarget($target, $intermediate);
        if (count($paths) === 0) {
            throw new PathBuildingException('No certification paths.');
        }
        usort($paths, fn ($a, $b) => count($a) < count($b) ? -1 : 1);
        return reset($paths);
    }

    /**
     * Find all issuers of the target certificate from a given bundle.
     *
     * @param Certificate $target Target certificate
     * @param CertificateBundle $bundle Certificates to search
     *
     * @return Certificate[]
     */
    private function findIssuers(Certificate $target, CertificateBundle $bundle): array
    {
        $issuers = [];
        $issuer_name = $target->tbsCertificate()
            ->issuer();
        $extensions = $target->tbsCertificate()
            ->extensions();
        // find by authority key identifier
        if ($extensions->hasAuthorityKeyIdentifier()) {
            $ext = $extensions->authorityKeyIdentifier();
            if ($ext->hasKeyIdentifier()) {
                foreach ($bundle->allBySubjectKeyIdentifier($ext->keyIdentifier()) as $issuer) {
                    // check that issuer name matches
                    if ($issuer->tbsCertificate()->subject()->equals($issuer_name)) {
                        $issuers[] = $issuer;
                    }
                }
            }
        }
        return $issuers;
    }

    /**
     * Resolve all possible certification paths from any trust anchor to the target certificate, using optional
     * intermediate certificates.
     *
     * Helper method for allPathsToTarget to be called recursively.
     *
     * @return array<int, array<Certificate>> Array of arrays containing path certificates
     * @todo Implement loop detection
     */
    private function resolvePathsToTarget(Certificate $target, ?CertificateBundle $intermediate = null): array
    {
        // array of possible paths
        $paths = [];
        // signed by certificate in the trust list
        foreach ($this->findIssuers($target, $this->trustList) as $issuer) {
            // if target is self-signed, path consists of only
            // the target certificate
            if ($target->equals($issuer)) {
                $paths[] = [$target];
            } else {
                $paths[] = [$issuer, $target];
            }
        }
        if (isset($intermediate)) {
            // signed by intermediate certificate
            foreach ($this->findIssuers($target, $intermediate) as $issuer) {
                // intermediate certificate must not be self-signed
                if ($issuer->isSelfIssued()) {
                    continue;
                }
                // resolve paths to issuer
                $subpaths = $this->resolvePathsToTarget($issuer, $intermediate);
                foreach ($subpaths as $path) {
                    $paths[] = array_merge($path, [$target]);
                }
            }
        }

        return $paths;
    }
}
