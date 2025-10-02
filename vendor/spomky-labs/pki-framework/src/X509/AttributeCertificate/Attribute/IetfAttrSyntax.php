<?php

declare(strict_types=1);

namespace SpomkyLabs\Pki\X509\AttributeCertificate\Attribute;

use ArrayIterator;
use Countable;
use IteratorAggregate;
use LogicException;
use SpomkyLabs\Pki\ASN1\Element;
use SpomkyLabs\Pki\ASN1\Type\Constructed\Sequence;
use SpomkyLabs\Pki\ASN1\Type\Tagged\ImplicitlyTaggedType;
use SpomkyLabs\Pki\ASN1\Type\UnspecifiedType;
use SpomkyLabs\Pki\X501\ASN1\AttributeValue\AttributeValue;
use SpomkyLabs\Pki\X501\MatchingRule\BinaryMatch;
use SpomkyLabs\Pki\X501\MatchingRule\MatchingRule;
use SpomkyLabs\Pki\X509\GeneralName\GeneralNames;
use function count;

/**
 * Base class implementing *IetfAttrSyntax* ASN.1 type used by attribute certificate attribute values.
 *
 * @see https://tools.ietf.org/html/rfc5755#section-4.4
 */
abstract class IetfAttrSyntax extends AttributeValue implements Countable, IteratorAggregate
{
    /**
     * Policy authority.
     */
    protected ?GeneralNames $_policyAuthority;

    /**
     * Values.
     *
     * @var IetfAttrValue[]
     */
    protected array $_values;

    protected function __construct(string $oid, IetfAttrValue ...$values)
    {
        parent::__construct($oid);
        $this->_policyAuthority = null;
        $this->_values = $values;
    }

    abstract public static function create(IetfAttrValue ...$values): self;

    /**
     * @return self
     */
    public static function fromASN1(UnspecifiedType $el): AttributeValue
    {
        $seq = $el->asSequence();
        $authority = null;
        $idx = 0;
        if ($seq->hasTagged(0)) {
            $authority = GeneralNames::fromASN1(
                $seq->getTagged(0)
                    ->asImplicit(Element::TYPE_SEQUENCE)
                    ->asSequence()
            );
            ++$idx;
        }
        $values = array_map(
            static fn (UnspecifiedType $el) => IetfAttrValue::fromASN1($el),
            $seq->at($idx)
                ->asSequence()
                ->elements()
        );
        $obj = static::create(...$values);
        $obj->_policyAuthority = $authority;
        return $obj;
    }

    /**
     * Get self with policy authority.
     */
    public function withPolicyAuthority(GeneralNames $names): self
    {
        $obj = clone $this;
        $obj->_policyAuthority = $names;
        return $obj;
    }

    /**
     * Check whether policy authority is present.
     */
    public function hasPolicyAuthority(): bool
    {
        return isset($this->_policyAuthority);
    }

    /**
     * Get policy authority.
     */
    public function policyAuthority(): GeneralNames
    {
        if (! $this->hasPolicyAuthority()) {
            throw new LogicException('policyAuthority not set.');
        }
        return $this->_policyAuthority;
    }

    /**
     * Get values.
     *
     * @return IetfAttrValue[]
     */
    public function values(): array
    {
        return $this->_values;
    }

    /**
     * Get first value.
     */
    public function first(): IetfAttrValue
    {
        if (count($this->_values) === 0) {
            throw new LogicException('No values.');
        }
        return $this->_values[0];
    }

    public function toASN1(): Element
    {
        $elements = [];
        if (isset($this->_policyAuthority)) {
            $elements[] = ImplicitlyTaggedType::create(0, $this->_policyAuthority->toASN1());
        }
        $values = array_map(static fn (IetfAttrValue $val) => $val->toASN1(), $this->_values);
        $elements[] = Sequence::create(...$values);
        return Sequence::create(...$elements);
    }

    public function stringValue(): string
    {
        return '#' . bin2hex($this->toASN1()->toDER());
    }

    public function equalityMatchingRule(): MatchingRule
    {
        return new BinaryMatch();
    }

    public function rfc2253String(): string
    {
        return $this->stringValue();
    }

    /**
     * Get number of values.
     *
     * @see \Countable::count()
     */
    public function count(): int
    {
        return count($this->_values);
    }

    /**
     * Get iterator for values.
     *
     * @see \IteratorAggregate::getIterator()
     */
    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator($this->_values);
    }

    protected function _transcodedString(): string
    {
        return $this->stringValue();
    }
}
