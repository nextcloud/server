<?php

declare(strict_types=1);

namespace SpomkyLabs\Pki\X509\Certificate;

use SpomkyLabs\Pki\ASN1\Type\Constructed\Sequence;
use SpomkyLabs\Pki\ASN1\Type\UnspecifiedType;
use SpomkyLabs\Pki\CryptoBridge\Crypto;
use SpomkyLabs\Pki\CryptoEncoding\PEM;
use SpomkyLabs\Pki\CryptoTypes\AlgorithmIdentifier\AlgorithmIdentifier;
use SpomkyLabs\Pki\CryptoTypes\AlgorithmIdentifier\Feature\SignatureAlgorithmIdentifier;
use SpomkyLabs\Pki\CryptoTypes\Asymmetric\PublicKeyInfo;
use SpomkyLabs\Pki\CryptoTypes\Signature\Signature;
use Stringable;
use UnexpectedValueException;

/**
 * Implements *Certificate* ASN.1 type.
 *
 * @see https://tools.ietf.org/html/rfc5280#section-4.1
 */
final class Certificate implements Stringable
{
    /**
     * @param TBSCertificate $tbsCertificate "To be signed" certificate information.
     * @param SignatureAlgorithmIdentifier $signatureAlgorithm Signature algorithm.
     * @param Signature $signatureValue Signature value.
     */
    private function __construct(
        private readonly TBSCertificate $tbsCertificate,
        private readonly SignatureAlgorithmIdentifier $signatureAlgorithm,
        private readonly Signature $signatureValue
    ) {
    }

    /**
     * Get certificate as a PEM formatted string.
     */
    public function __toString(): string
    {
        return $this->toPEM()
            ->string();
    }

    public static function create(
        TBSCertificate $tbsCertificate,
        SignatureAlgorithmIdentifier $signatureAlgorithm,
        Signature $signatureValue
    ): self {
        return new self($tbsCertificate, $signatureAlgorithm, $signatureValue);
    }

    /**
     * Initialize from ASN.1.
     */
    public static function fromASN1(Sequence $seq): self
    {
        $tbsCert = TBSCertificate::fromASN1($seq->at(0)->asSequence());
        $algo = AlgorithmIdentifier::fromASN1($seq->at(1)->asSequence());
        if (! $algo instanceof SignatureAlgorithmIdentifier) {
            throw new UnexpectedValueException('Unsupported signature algorithm ' . $algo->oid() . '.');
        }
        $signature = Signature::fromSignatureData($seq->at(2)->asBitString()->string(), $algo);
        return self::create($tbsCert, $algo, $signature);
    }

    /**
     * Initialize from DER.
     */
    public static function fromDER(string $data): self
    {
        return self::fromASN1(UnspecifiedType::fromDER($data)->asSequence());
    }

    /**
     * Initialize from PEM.
     */
    public static function fromPEM(PEM $pem): self
    {
        if ($pem->type() !== PEM::TYPE_CERTIFICATE) {
            throw new UnexpectedValueException('Invalid PEM type.');
        }
        return self::fromDER($pem->data());
    }

    /**
     * Get certificate information.
     */
    public function tbsCertificate(): TBSCertificate
    {
        return $this->tbsCertificate;
    }

    /**
     * Get signature algorithm.
     */
    public function signatureAlgorithm(): SignatureAlgorithmIdentifier
    {
        return $this->signatureAlgorithm;
    }

    /**
     * Get signature value.
     */
    public function signatureValue(): Signature
    {
        return $this->signatureValue;
    }

    /**
     * Check whether certificate is self-issued.
     */
    public function isSelfIssued(): bool
    {
        return $this->tbsCertificate->subject()
            ->equals($this->tbsCertificate->issuer());
    }

    /**
     * Check whether certificate is semantically equal to another.
     *
     * @param Certificate $cert Certificate to compare to
     */
    public function equals(self $cert): bool
    {
        return $this->_hasEqualSerialNumber($cert) &&
            $this->_hasEqualPublicKey($cert) && $this->_hasEqualSubject($cert);
    }

    /**
     * Generate ASN.1 structure.
     */
    public function toASN1(): Sequence
    {
        return Sequence::create(
            $this->tbsCertificate->toASN1(),
            $this->signatureAlgorithm->toASN1(),
            $this->signatureValue->bitString()
        );
    }

    /**
     * Get certificate as a DER.
     */
    public function toDER(): string
    {
        return $this->toASN1()
            ->toDER();
    }

    /**
     * Get certificate as a PEM.
     */
    public function toPEM(): PEM
    {
        return PEM::create(PEM::TYPE_CERTIFICATE, $this->toDER());
    }

    /**
     * Verify certificate signature.
     *
     * @param PublicKeyInfo $pubkey_info Issuer's public key
     * @param null|Crypto $crypto Crypto engine, use default if not set
     *
     * @return bool True if certificate signature is valid
     */
    public function verify(PublicKeyInfo $pubkey_info, ?Crypto $crypto = null): bool
    {
        $crypto ??= Crypto::getDefault();
        $data = $this->tbsCertificate->toASN1()
            ->toDER();
        return $crypto->verify($data, $this->signatureValue, $pubkey_info, $this->signatureAlgorithm);
    }

    /**
     * Check whether certificate has serial number equal to another.
     */
    private function _hasEqualSerialNumber(self $cert): bool
    {
        $sn1 = $this->tbsCertificate->serialNumber();
        $sn2 = $cert->tbsCertificate->serialNumber();
        return $sn1 === $sn2;
    }

    /**
     * Check whether certificate has public key equal to another.
     */
    private function _hasEqualPublicKey(self $cert): bool
    {
        $kid1 = $this->tbsCertificate->subjectPublicKeyInfo()
            ->keyIdentifier();
        $kid2 = $cert->tbsCertificate->subjectPublicKeyInfo()
            ->keyIdentifier();
        return $kid1 === $kid2;
    }

    /**
     * Check whether certificate has subject equal to another.
     */
    private function _hasEqualSubject(self $cert): bool
    {
        $dn1 = $this->tbsCertificate->subject();
        $dn2 = $cert->tbsCertificate->subject();
        return $dn1->equals($dn2);
    }
}
