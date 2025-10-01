<?php

declare(strict_types=1);

namespace SpomkyLabs\Pki\X509\AttributeCertificate;

use LogicException;
use SpomkyLabs\Pki\ASN1\Element;
use SpomkyLabs\Pki\ASN1\Type\Constructed\Sequence;
use SpomkyLabs\Pki\ASN1\Type\Tagged\ImplicitlyTaggedType;
use SpomkyLabs\Pki\X509\Certificate\Certificate;
use SpomkyLabs\Pki\X509\GeneralName\DirectoryName;
use SpomkyLabs\Pki\X509\GeneralName\GeneralName;
use SpomkyLabs\Pki\X509\GeneralName\GeneralNames;

/**
 * Implements *Holder* ASN.1 type.
 *
 * @see https://tools.ietf.org/html/rfc5755#section-4.1
 */
final class Holder
{
    /**
     * Linked object.
     */
    private ?ObjectDigestInfo $objectDigestInfo = null;

    private function __construct(
        private ?IssuerSerial $baseCertificateID,
        private ?GeneralNames $entityName
    ) {
    }

    public static function create(?IssuerSerial $baseCertificateID = null, ?GeneralNames $entityName = null): self
    {
        return new self($baseCertificateID, $entityName);
    }

    /**
     * Initialize from a holder's public key certificate.
     */
    public static function fromPKC(Certificate $cert): self
    {
        return self::create(IssuerSerial::fromPKC($cert));
    }

    /**
     * Initialize from ASN.1.
     */
    public static function fromASN1(Sequence $seq): self
    {
        $cert_id = null;
        $entity_name = null;
        $digest_info = null;
        if ($seq->hasTagged(0)) {
            $cert_id = IssuerSerial::fromASN1(
                $seq->getTagged(0)
                    ->asImplicit(Element::TYPE_SEQUENCE)
                    ->asSequence()
            );
        }
        if ($seq->hasTagged(1)) {
            $entity_name = GeneralNames::fromASN1(
                $seq->getTagged(1)
                    ->asImplicit(Element::TYPE_SEQUENCE)
                    ->asSequence()
            );
        }
        if ($seq->hasTagged(2)) {
            $digest_info = ObjectDigestInfo::fromASN1(
                $seq->getTagged(2)
                    ->asImplicit(Element::TYPE_SEQUENCE)
                    ->asSequence()
            );
        }
        return self::create($cert_id, $entity_name)
            ->withObjectDigestInfo($digest_info);
    }

    /**
     * Get self with base certificate ID.
     */
    public function withBaseCertificateID(IssuerSerial $issuer): self
    {
        $obj = clone $this;
        $obj->baseCertificateID = $issuer;
        return $obj;
    }

    /**
     * Get self with entity name.
     */
    public function withEntityName(GeneralNames $names): self
    {
        $obj = clone $this;
        $obj->entityName = $names;
        return $obj;
    }

    /**
     * Get self with object digest info.
     */
    public function withObjectDigestInfo(?ObjectDigestInfo $odi): self
    {
        $obj = clone $this;
        $obj->objectDigestInfo = $odi;
        return $obj;
    }

    /**
     * Check whether base certificate ID is present.
     */
    public function hasBaseCertificateID(): bool
    {
        return isset($this->baseCertificateID);
    }

    /**
     * Get base certificate ID.
     */
    public function baseCertificateID(): IssuerSerial
    {
        if (! $this->hasBaseCertificateID()) {
            throw new LogicException('baseCertificateID not set.');
        }
        return $this->baseCertificateID;
    }

    /**
     * Check whether entity name is present.
     */
    public function hasEntityName(): bool
    {
        return isset($this->entityName);
    }

    /**
     * Get entity name.
     */
    public function entityName(): GeneralNames
    {
        if (! $this->hasEntityName()) {
            throw new LogicException('entityName not set.');
        }
        return $this->entityName;
    }

    /**
     * Check whether object digest info is present.
     */
    public function hasObjectDigestInfo(): bool
    {
        return isset($this->objectDigestInfo);
    }

    /**
     * Get object digest info.
     */
    public function objectDigestInfo(): ObjectDigestInfo
    {
        if (! $this->hasObjectDigestInfo()) {
            throw new LogicException('objectDigestInfo not set.');
        }
        return $this->objectDigestInfo;
    }

    /**
     * Generate ASN.1 structure.
     */
    public function toASN1(): Sequence
    {
        $elements = [];
        if (isset($this->baseCertificateID)) {
            $elements[] = ImplicitlyTaggedType::create(0, $this->baseCertificateID->toASN1());
        }
        if (isset($this->entityName)) {
            $elements[] = ImplicitlyTaggedType::create(1, $this->entityName->toASN1());
        }
        if (isset($this->objectDigestInfo)) {
            $elements[] = ImplicitlyTaggedType::create(2, $this->objectDigestInfo->toASN1());
        }
        return Sequence::create(...$elements);
    }

    /**
     * Check whether Holder identifies given certificate.
     */
    public function identifiesPKC(Certificate $cert): bool
    {
        // if neither baseCertificateID nor entityName are present
        if ($this->baseCertificateID === null && $this->entityName === null) {
            return false;
        }
        // if baseCertificateID is present, but doesn't match
        if ($this->baseCertificateID !== null && ! $this->baseCertificateID->identifiesPKC($cert)) {
            return false;
        }
        // if entityName is present, but doesn't match
        if ($this->entityName !== null && ! $this->_checkEntityName($cert)) {
            return false;
        }
        return true;
    }

    /**
     * Check whether entityName matches the given certificate.
     */
    private function _checkEntityName(Certificate $cert): bool
    {
        $name = $this->entityName?->firstDN();
        if ($name !== null && $cert->tbsCertificate()->subject()->equals($name)) {
            return true;
        }
        $exts = $cert->tbsCertificate()
            ->extensions();
        if ($exts->hasSubjectAlternativeName()) {
            $ext = $exts->subjectAlternativeName();
            if ($this->_checkEntityAlternativeNames($ext->names())) {
                return true;
            }
        }
        return false;
    }

    /**
     * Check whether any of the subject alternative names match entityName.
     */
    private function _checkEntityAlternativeNames(GeneralNames $san): bool
    {
        // only directory names supported for now
        $name = $this->entityName?->firstDN();
        if ($name === null) {
            return false;
        }
        foreach ($san->allOf(GeneralName::TAG_DIRECTORY_NAME) as $dn) {
            if ($dn instanceof DirectoryName && $dn->dn()->equals($name)) {
                return true;
            }
        }
        return false;
    }
}
