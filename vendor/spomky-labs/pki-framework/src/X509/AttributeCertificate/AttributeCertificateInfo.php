<?php

declare(strict_types=1);

namespace SpomkyLabs\Pki\X509\AttributeCertificate;

use Brick\Math\BigInteger;
use LogicException;
use SpomkyLabs\Pki\ASN1\Element;
use SpomkyLabs\Pki\ASN1\Type\Constructed\Sequence;
use SpomkyLabs\Pki\ASN1\Type\Primitive\Integer;
use SpomkyLabs\Pki\CryptoBridge\Crypto;
use SpomkyLabs\Pki\CryptoTypes\AlgorithmIdentifier\AlgorithmIdentifier;
use SpomkyLabs\Pki\CryptoTypes\AlgorithmIdentifier\Feature\SignatureAlgorithmIdentifier;
use SpomkyLabs\Pki\CryptoTypes\Asymmetric\PrivateKeyInfo;
use SpomkyLabs\Pki\X509\Certificate\Extension\Extension;
use SpomkyLabs\Pki\X509\Certificate\Extensions;
use SpomkyLabs\Pki\X509\Certificate\UniqueIdentifier;
use UnexpectedValueException;
use function count;
use function strval;

/**
 * Implements *AttributeCertificateInfo* ASN.1 type.
 *
 * @see https://tools.ietf.org/html/rfc5755#section-4.1
 */
final class AttributeCertificateInfo
{
    final public const VERSION_2 = 1;

    /**
     * AC version.
     */
    private readonly int $version;

    /**
     * Signature algorithm identifier.
     */
    private ?SignatureAlgorithmIdentifier $signature = null;

    /**
     * AC serial number as a base 10 integer.
     */
    private ?string $serialNumber = null;

    /**
     * Issuer unique identifier.
     */
    private ?UniqueIdentifier $issuerUniqueID = null;

    /**
     * Extensions.
     */
    private Extensions $extensions;

    /**
     * @param Holder $holder AC holder
     * @param AttCertIssuer $issuer AC issuer
     * @param AttCertValidityPeriod $attrCertValidityPeriod Validity
     * @param Attributes $attributes Attributes
     */
    private function __construct(
        private Holder $holder,
        private AttCertIssuer $issuer,
        private AttCertValidityPeriod $attrCertValidityPeriod,
        private Attributes $attributes
    ) {
        $this->version = self::VERSION_2;
        $this->extensions = Extensions::create();
    }

    public static function create(
        Holder $holder,
        AttCertIssuer $issuer,
        AttCertValidityPeriod $attrCertValidityPeriod,
        Attributes $attributes
    ): self {
        return new self($holder, $issuer, $attrCertValidityPeriod, $attributes);
    }

    /**
     * Initialize from ASN.1.
     */
    public static function fromASN1(Sequence $seq): self
    {
        $idx = 0;
        $version = $seq->at($idx++)
            ->asInteger()
            ->intNumber();
        if ($version !== self::VERSION_2) {
            throw new UnexpectedValueException('Version must be 2.');
        }
        $holder = Holder::fromASN1($seq->at($idx++)->asSequence());
        $issuer = AttCertIssuer::fromASN1($seq->at($idx++));
        $signature = AlgorithmIdentifier::fromASN1($seq->at($idx++)->asSequence());
        if (! $signature instanceof SignatureAlgorithmIdentifier) {
            throw new UnexpectedValueException('Unsupported signature algorithm ' . $signature->oid() . '.');
        }
        $serial = $seq->at($idx++)
            ->asInteger()
            ->number();
        $validity = AttCertValidityPeriod::fromASN1($seq->at($idx++)->asSequence());
        $attribs = Attributes::fromASN1($seq->at($idx++)->asSequence());
        $obj = self::create($holder, $issuer, $validity, $attribs);
        $obj->signature = $signature;
        $obj->serialNumber = $serial;
        if ($seq->has($idx, Element::TYPE_BIT_STRING)) {
            $obj->issuerUniqueID = UniqueIdentifier::fromASN1($seq->at($idx++)->asBitString());
        }
        if ($seq->has($idx, Element::TYPE_SEQUENCE)) {
            $obj->extensions = Extensions::fromASN1($seq->at($idx++)->asSequence());
        }
        return $obj;
    }

    /**
     * Get self with holder.
     */
    public function withHolder(Holder $holder): self
    {
        $obj = clone $this;
        $obj->holder = $holder;
        return $obj;
    }

    /**
     * Get self with issuer.
     */
    public function withIssuer(AttCertIssuer $issuer): self
    {
        $obj = clone $this;
        $obj->issuer = $issuer;
        return $obj;
    }

    /**
     * Get self with signature algorithm identifier.
     */
    public function withSignature(SignatureAlgorithmIdentifier $algo): self
    {
        $obj = clone $this;
        $obj->signature = $algo;
        return $obj;
    }

    /**
     * Get self with serial number.
     *
     * @param int|string $serial Base 10 serial number
     */
    public function withSerialNumber(int|string $serial): self
    {
        $obj = clone $this;
        $obj->serialNumber = strval($serial);
        return $obj;
    }

    /**
     * Get self with random positive serial number.
     *
     * @param int $size Number of random bytes
     */
    public function withRandomSerialNumber(int $size): self
    {
        // ensure that first byte is always non-zero and having first bit unset
        $num = BigInteger::of(random_int(1, 0x7f));
        for ($i = 1; $i < $size; ++$i) {
            $num = $num->shiftedLeft(8);
            $num = $num->plus(random_int(0, 0xff));
        }
        return $this->withSerialNumber($num->toBase(10));
    }

    /**
     * Get self with validity period.
     */
    public function withValidity(AttCertValidityPeriod $validity): self
    {
        $obj = clone $this;
        $obj->attrCertValidityPeriod = $validity;
        return $obj;
    }

    /**
     * Get self with attributes.
     */
    public function withAttributes(Attributes $attribs): self
    {
        $obj = clone $this;
        $obj->attributes = $attribs;
        return $obj;
    }

    /**
     * Get self with issuer unique identifier.
     */
    public function withIssuerUniqueID(UniqueIdentifier $uid): self
    {
        $obj = clone $this;
        $obj->issuerUniqueID = $uid;
        return $obj;
    }

    /**
     * Get self with extensions.
     */
    public function withExtensions(Extensions $extensions): self
    {
        $obj = clone $this;
        $obj->extensions = $extensions;
        return $obj;
    }

    /**
     * Get self with extensions added.
     *
     * @param Extension ...$exts One or more Extension objects
     */
    public function withAdditionalExtensions(Extension ...$exts): self
    {
        $obj = clone $this;
        $obj->extensions = $obj->extensions->withExtensions(...$exts);
        return $obj;
    }

    public function version(): int
    {
        return $this->version;
    }

    /**
     * Get AC holder.
     */
    public function holder(): Holder
    {
        return $this->holder;
    }

    /**
     * Get AC issuer.
     */
    public function issuer(): AttCertIssuer
    {
        return $this->issuer;
    }

    /**
     * Check whether signature is set.
     */
    public function hasSignature(): bool
    {
        return $this->signature !== null;
    }

    /**
     * Get signature algorithm identifier.
     */
    public function signature(): SignatureAlgorithmIdentifier
    {
        if (! $this->hasSignature()) {
            throw new LogicException('signature not set.');
        }
        return $this->signature;
    }

    /**
     * Check whether serial number is present.
     */
    public function hasSerialNumber(): bool
    {
        return isset($this->serialNumber);
    }

    /**
     * Get AC serial number as a base 10 integer.
     */
    public function serialNumber(): string
    {
        if (! $this->hasSerialNumber()) {
            throw new LogicException('serialNumber not set.');
        }
        return $this->serialNumber;
    }

    /**
     * Get validity period.
     */
    public function validityPeriod(): AttCertValidityPeriod
    {
        return $this->attrCertValidityPeriod;
    }

    public function attributes(): Attributes
    {
        return $this->attributes;
    }

    /**
     * Check whether issuer unique identifier is present.
     */
    public function hasIssuerUniqueID(): bool
    {
        return isset($this->issuerUniqueID);
    }

    /**
     * Get issuer unique identifier.
     */
    public function issuerUniqueID(): UniqueIdentifier
    {
        if (! $this->hasIssuerUniqueID()) {
            throw new LogicException('issuerUniqueID not set.');
        }
        return $this->issuerUniqueID;
    }

    public function extensions(): Extensions
    {
        return $this->extensions;
    }

    /**
     * Get ASN.1 structure.
     */
    public function toASN1(): Sequence
    {
        $elements = [Integer::create($this->version), $this->holder->toASN1(),
            $this->issuer->toASN1(), $this->signature()
                ->toASN1(),
            Integer::create($this->serialNumber()),
            $this->attrCertValidityPeriod->toASN1(),
            $this->attributes->toASN1(), ];
        if (isset($this->issuerUniqueID)) {
            $elements[] = $this->issuerUniqueID->toASN1();
        }
        if (count($this->extensions) !== 0) {
            $elements[] = $this->extensions->toASN1();
        }
        return Sequence::create(...$elements);
    }

    /**
     * Create signed attribute certificate.
     *
     * @param SignatureAlgorithmIdentifier $algo Signature algorithm
     * @param PrivateKeyInfo $privkey_info Private key
     * @param null|Crypto $crypto Crypto engine, use default if not set
     */
    public function sign(
        SignatureAlgorithmIdentifier $algo,
        PrivateKeyInfo $privkey_info,
        ?Crypto $crypto = null
    ): AttributeCertificate {
        $crypto ??= Crypto::getDefault();
        $aci = clone $this;
        if (! isset($aci->serialNumber)) {
            $aci->serialNumber = '0';
        }
        $aci->signature = $algo;
        $data = $aci->toASN1()
            ->toDER();
        $signature = $crypto->sign($data, $privkey_info, $algo);
        return AttributeCertificate::create($aci, $algo, $signature);
    }
}
