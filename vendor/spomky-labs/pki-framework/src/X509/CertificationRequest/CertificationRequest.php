<?php

declare(strict_types=1);

namespace SpomkyLabs\Pki\X509\CertificationRequest;

use SpomkyLabs\Pki\ASN1\Type\Constructed\Sequence;
use SpomkyLabs\Pki\ASN1\Type\UnspecifiedType;
use SpomkyLabs\Pki\CryptoBridge\Crypto;
use SpomkyLabs\Pki\CryptoEncoding\PEM;
use SpomkyLabs\Pki\CryptoTypes\AlgorithmIdentifier\AlgorithmIdentifier;
use SpomkyLabs\Pki\CryptoTypes\AlgorithmIdentifier\Feature\SignatureAlgorithmIdentifier;
use SpomkyLabs\Pki\CryptoTypes\Signature\Signature;
use Stringable;
use UnexpectedValueException;

/**
 * Implements *CertificationRequest* ASN.1 type.
 *
 * @see https://tools.ietf.org/html/rfc2986#section-4
 */
final class CertificationRequest implements Stringable
{
    private function __construct(
        private readonly CertificationRequestInfo $certificationRequestInfo,
        private readonly SignatureAlgorithmIdentifier $signatureAlgorithm,
        private readonly Signature $signature
    ) {
    }

    /**
     * Get certification request as a PEM formatted string.
     */
    public function __toString(): string
    {
        return $this->toPEM()
            ->string();
    }

    public static function create(
        CertificationRequestInfo $_certificationRequestInfo,
        SignatureAlgorithmIdentifier $_signatureAlgorithm,
        Signature $_signature
    ): self {
        return new self($_certificationRequestInfo, $_signatureAlgorithm, $_signature);
    }

    /**
     * Initialize from ASN.1.
     */
    public static function fromASN1(Sequence $seq): self
    {
        $info = CertificationRequestInfo::fromASN1($seq->at(0)->asSequence());
        $algo = AlgorithmIdentifier::fromASN1($seq->at(1)->asSequence());
        if (! $algo instanceof SignatureAlgorithmIdentifier) {
            throw new UnexpectedValueException('Unsupported signature algorithm ' . $algo->oid() . '.');
        }
        $signature = Signature::fromSignatureData($seq->at(2)->asBitString()->string(), $algo);
        return self::create($info, $algo, $signature);
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
        if ($pem->type() !== PEM::TYPE_CERTIFICATE_REQUEST) {
            throw new UnexpectedValueException('Invalid PEM type.');
        }
        return self::fromDER($pem->data());
    }

    /**
     * Get certification request info.
     */
    public function certificationRequestInfo(): CertificationRequestInfo
    {
        return $this->certificationRequestInfo;
    }

    /**
     * Get signature algorithm.
     */
    public function signatureAlgorithm(): SignatureAlgorithmIdentifier
    {
        return $this->signatureAlgorithm;
    }

    public function signature(): Signature
    {
        return $this->signature;
    }

    /**
     * Generate ASN.1 structure.
     */
    public function toASN1(): Sequence
    {
        return Sequence::create(
            $this->certificationRequestInfo->toASN1(),
            $this->signatureAlgorithm->toASN1(),
            $this->signature->bitString()
        );
    }

    /**
     * Get certification request as a DER.
     */
    public function toDER(): string
    {
        return $this->toASN1()
            ->toDER();
    }

    /**
     * Get certification request as a PEM.
     */
    public function toPEM(): PEM
    {
        return PEM::create(PEM::TYPE_CERTIFICATE_REQUEST, $this->toDER());
    }

    /**
     * Verify certification request signature.
     *
     * @param null|Crypto $crypto Crypto engine, use default if not set
     *
     * @return bool True if signature matches
     */
    public function verify(?Crypto $crypto = null): bool
    {
        $crypto ??= Crypto::getDefault();
        $data = $this->certificationRequestInfo->toASN1()
            ->toDER();
        $pk_info = $this->certificationRequestInfo->subjectPKInfo();
        return $crypto->verify($data, $this->signature, $pk_info, $this->signatureAlgorithm);
    }
}
