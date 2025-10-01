<?php

declare(strict_types=1);

namespace SpomkyLabs\Pki\X509\Certificate\Extension;

use LogicException;
use SpomkyLabs\Pki\ASN1\Element;
use SpomkyLabs\Pki\ASN1\Type\Constructed\Sequence;
use SpomkyLabs\Pki\ASN1\Type\Primitive\Boolean;
use SpomkyLabs\Pki\ASN1\Type\Primitive\Integer;
use SpomkyLabs\Pki\ASN1\Type\UnspecifiedType;

/**
 * Implements 'Basic Constraints' certificate extension.
 *
 * @see https://tools.ietf.org/html/rfc5280#section-4.2.1.9
 */
final class BasicConstraintsExtension extends Extension
{
    /**
     * @param bool $ca Whether certificate is a CA.
     * @param int|null $pathLen Maximum certification path length.
     */
    private function __construct(
        bool $critical,
        private readonly bool $ca,
        private readonly ?int $pathLen
    ) {
        parent::__construct(self::OID_BASIC_CONSTRAINTS, $critical);
    }

    public static function create(bool $critical, bool $ca, ?int $pathLen = null): self
    {
        return new self($critical, $ca, $pathLen);
    }

    /**
     * Whether certificate is a CA.
     */
    public function isCA(): bool
    {
        return $this->ca;
    }

    /**
     * Whether path length is present.
     */
    public function hasPathLen(): bool
    {
        return isset($this->pathLen);
    }

    /**
     * Get path length.
     */
    public function pathLen(): int
    {
        if (! $this->hasPathLen()) {
            throw new LogicException('pathLenConstraint not set.');
        }
        return $this->pathLen;
    }

    protected static function fromDER(string $data, bool $critical): static
    {
        $seq = UnspecifiedType::fromDER($data)->asSequence();
        $ca = false;
        $path_len = null;
        $idx = 0;
        if ($seq->has($idx, Element::TYPE_BOOLEAN)) {
            $ca = $seq->at($idx++)
                ->asBoolean()
                ->value();
        }
        if ($seq->has($idx, Element::TYPE_INTEGER)) {
            $path_len = $seq->at($idx)
                ->asInteger()
                ->intNumber();
        }
        return self::create($critical, $ca, $path_len);
    }

    protected function valueASN1(): Element
    {
        $elements = [];
        if ($this->ca) {
            $elements[] = Boolean::create(true);
        }
        if (isset($this->pathLen)) {
            $elements[] = Integer::create($this->pathLen);
        }
        return Sequence::create(...$elements);
    }
}
