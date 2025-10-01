<?php

declare(strict_types=1);

namespace SpomkyLabs\Pki\X509\AttributeCertificate;

use SpomkyLabs\Pki\ASN1\Element;
use SpomkyLabs\Pki\ASN1\Type\Constructed\Sequence;
use SpomkyLabs\Pki\ASN1\Type\Primitive\BitString;
use SpomkyLabs\Pki\ASN1\Type\Primitive\Enumerated;
use SpomkyLabs\Pki\ASN1\Type\Primitive\ObjectIdentifier;
use SpomkyLabs\Pki\CryptoTypes\AlgorithmIdentifier\AlgorithmIdentifier;
use SpomkyLabs\Pki\CryptoTypes\AlgorithmIdentifier\Feature\AlgorithmIdentifierType;

/**
 * Implements *ObjectDigestInfo* ASN.1 type.
 *
 * @see https://tools.ietf.org/html/rfc5755#section-4.1
 * @see https://tools.ietf.org/html/rfc5755#section-7.3
 */
final class ObjectDigestInfo
{
    final public const TYPE_PUBLIC_KEY = 0;

    final public const TYPE_PUBLIC_KEY_CERT = 1;

    final public const TYPE_OTHER_OBJECT_TYPES = 2;

    private function __construct(
        private readonly int $digestedObjectType,
        private readonly AlgorithmIdentifierType $digestAlgorithm,
        private readonly BitString $objectDigest,
        private readonly ?string $otherObjectTypeID
    ) {
    }

    public static function create(
        int $digestedObjectType,
        AlgorithmIdentifierType $digestAlgorithm,
        BitString $objectDigest,
        ?string $otherObjectTypeID = null
    ): self {
        return new self($digestedObjectType, $digestAlgorithm, $objectDigest, $otherObjectTypeID);
    }

    /**
     * Initialize from ASN.1.
     */
    public static function fromASN1(Sequence $seq): self
    {
        $idx = 0;
        $oid = null;
        $type = $seq->at($idx++)
            ->asEnumerated()
            ->intNumber();
        if ($seq->has($idx, Element::TYPE_OBJECT_IDENTIFIER)) {
            $oid = $seq->at($idx++)
                ->asObjectIdentifier()
                ->oid();
        }
        $algo = AlgorithmIdentifier::fromASN1($seq->at($idx++)->asSequence());
        $digest = $seq->at($idx)
            ->asBitString();
        return self::create($type, $algo, $digest, $oid);
    }

    /**
     * Generate ASN.1 structure.
     */
    public function toASN1(): Sequence
    {
        $elements = [Enumerated::create($this->digestedObjectType)];
        if (isset($this->otherObjectTypeID)) {
            $elements[] = ObjectIdentifier::create($this->otherObjectTypeID);
        }
        $elements[] = $this->digestAlgorithm->toASN1();
        $elements[] = $this->objectDigest;
        return Sequence::create(...$elements);
    }
}
