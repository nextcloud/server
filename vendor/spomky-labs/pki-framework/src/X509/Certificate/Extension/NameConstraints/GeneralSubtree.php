<?php

declare(strict_types=1);

namespace SpomkyLabs\Pki\X509\Certificate\Extension\NameConstraints;

use SpomkyLabs\Pki\ASN1\Element;
use SpomkyLabs\Pki\ASN1\Type\Constructed\Sequence;
use SpomkyLabs\Pki\ASN1\Type\Primitive\Integer;
use SpomkyLabs\Pki\ASN1\Type\Tagged\ImplicitlyTaggedType;
use SpomkyLabs\Pki\X509\GeneralName\GeneralName;
use function count;

/**
 * Implements *GeneralSubtree* ASN.1 type used by 'Name Constraints' certificate extension.
 *
 * @see https://tools.ietf.org/html/rfc5280#section-4.2.1.10
 */
final class GeneralSubtree
{
    private function __construct(
        private readonly GeneralName $base,
        private readonly int $min,
        private readonly ?int $max
    ) {
    }

    public static function create(GeneralName $base, int $min = 0, ?int $max = null): self
    {
        return new self($base, $min, $max);
    }

    public function getBase(): GeneralName
    {
        return $this->base;
    }

    public function getMin(): int
    {
        return $this->min;
    }

    public function getMax(): ?int
    {
        return $this->max;
    }

    /**
     * Initialize from ASN.1.
     */
    public static function fromASN1(Sequence $seq): self
    {
        $base = GeneralName::fromASN1($seq->at(0)->asTagged());
        $min = 0;
        $max = null;
        // GeneralName is a CHOICE, which may be tagged as otherName [0]
        // or rfc822Name [1]. As minimum and maximum are also implicitly tagged,
        // we have to iterate the remaining elements instead of just checking
        // for tagged types.
        for ($i = 1; $i < count($seq); ++$i) {
            $el = $seq->at($i)
                ->expectTagged();
            switch ($el->tag()) {
                case 0:
                    $min = $el->asImplicit(Element::TYPE_INTEGER)
                        ->asInteger()
                        ->intNumber();
                    break;
                case 1:
                    $max = $el->asImplicit(Element::TYPE_INTEGER)
                        ->asInteger()
                        ->intNumber();
                    break;
            }
        }
        return self::create($base, $min, $max);
    }

    public function base(): GeneralName
    {
        return $this->base;
    }

    /**
     * Generate ASN.1 structure.
     */
    public function toASN1(): Sequence
    {
        $elements = [$this->base->toASN1()];
        if (isset($this->min) && $this->min !== 0) {
            $elements[] = ImplicitlyTaggedType::create(0, Integer::create($this->min));
        }
        if (isset($this->max)) {
            $elements[] = ImplicitlyTaggedType::create(1, Integer::create($this->max));
        }
        return Sequence::create(...$elements);
    }
}
