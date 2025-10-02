<?php

declare(strict_types=1);

namespace SpomkyLabs\Pki\X509\CertificationPath\PathValidation;

use SpomkyLabs\Pki\ASN1\Element;
use SpomkyLabs\Pki\CryptoTypes\AlgorithmIdentifier\Feature\AlgorithmIdentifierType;
use SpomkyLabs\Pki\CryptoTypes\Asymmetric\PublicKeyInfo;
use SpomkyLabs\Pki\X509\Certificate\Certificate;
use SpomkyLabs\Pki\X509\Certificate\Extension\CertificatePolicy\PolicyInformation;
use SpomkyLabs\Pki\X509\CertificationPath\Policy\PolicyTree;
use function count;

/**
 * Result of the path validation process.
 *
 * @see https://tools.ietf.org/html/rfc5280#section-6.1.6
 */
final class PathValidationResult
{
    /**
     * Certificates in a certification path.
     *
     * @var Certificate[]
     */
    private readonly array $certificates;

    /**
     * @param Certificate[] $certificates
     */
    private function __construct(
        array $certificates,
        private readonly ?PolicyTree $policyTree,
        private readonly PublicKeyInfo $publicKeyInfo,
        private readonly AlgorithmIdentifierType $publicKeyAlgo,
        private readonly ?Element $publicKeyParameters
    ) {
        $this->certificates = array_values($certificates);
    }

    /**
     * @param Certificate[] $certificates Certificates in a certification path
     * @param null|PolicyTree $policyTree Valid policy tree
     * @param PublicKeyInfo $publicKeyInfo Public key of the end-entity certificate
     * @param AlgorithmIdentifierType $publicKeyAlgo Public key algorithm of the end-entity certificate
     * @param null|Element $publicKeyParameters Algorithm parameters
     */
    public static function create(
        array $certificates,
        ?PolicyTree $policyTree,
        PublicKeyInfo $publicKeyInfo,
        AlgorithmIdentifierType $publicKeyAlgo,
        ?Element $publicKeyParameters = null
    ): self {
        return new self($certificates, $policyTree, $publicKeyInfo, $publicKeyAlgo, $publicKeyParameters);
    }

    public function getPolicyTree(): ?PolicyTree
    {
        return $this->policyTree;
    }

    public function getPublicKeyInfo(): PublicKeyInfo
    {
        return $this->publicKeyInfo;
    }

    public function getPublicKeyAlgo(): AlgorithmIdentifierType
    {
        return $this->publicKeyAlgo;
    }

    public function getPublicKeyParameters(): ?Element
    {
        return $this->publicKeyParameters;
    }

    /**
     * Get end-entity certificate.
     */
    public function certificate(): Certificate
    {
        return $this->certificates[count($this->certificates) - 1];
    }

    /**
     * Get certificate policies of the end-entity certificate.
     *
     * @return PolicyInformation[]
     */
    public function policies(): array
    {
        if ($this->policyTree === null) {
            return [];
        }
        return $this->policyTree->policiesAtDepth(count($this->certificates));
    }
}
