<?php

declare(strict_types=1);

namespace SpomkyLabs\Pki\ASN1\Type;

use SpomkyLabs\Pki\ASN1\Component\Identifier;
use SpomkyLabs\Pki\ASN1\Element;
use SpomkyLabs\Pki\ASN1\Type\Tagged\ExplicitTagging;
use SpomkyLabs\Pki\ASN1\Type\Tagged\ImplicitTagging;
use UnexpectedValueException;

/**
 * Base class for context-specific types.
 */
abstract class TaggedType extends Element
{
    /**
     * Check whether element supports explicit tagging.
     *
     * @param null|int $expectedTag Optional outer tag expectation
     */
    public function expectExplicit(?int $expectedTag = null): ExplicitTagging
    {
        $el = $this;
        if (! $el instanceof ExplicitTagging) {
            throw new UnexpectedValueException("Element doesn't implement explicit tagging.");
        }
        if (isset($expectedTag)) {
            $el->expectTagged($expectedTag);
        }
        return $el;
    }

    /**
     * Get the wrapped inner element employing explicit tagging.
     *
     * @param null|int $expectedTag Optional outer tag expectation
     */
    public function asExplicit(?int $expectedTag = null): UnspecifiedType
    {
        return $this->expectExplicit($expectedTag)
            ->explicit();
    }

    /**
     * Check whether element supports implicit tagging.
     *
     * @param null|int $expectedTag Optional outer tag expectation
     */
    public function expectImplicit(?int $expectedTag = null): ImplicitTagging
    {
        $el = $this;
        if (! $el instanceof ImplicitTagging) {
            throw new UnexpectedValueException("Element doesn't implement implicit tagging.");
        }
        if (isset($expectedTag)) {
            $el->expectTagged($expectedTag);
        }
        return $el;
    }

    /**
     * Get the wrapped inner element employing implicit tagging.
     *
     * @param int $tag Type tag of the inner element
     * @param null|int $expectedTag Optional outer tag expectation
     * @param int $expectedClass Optional inner type class expectation
     */
    public function asImplicit(
        int $tag,
        ?int $expectedTag = null,
        int $expectedClass = Identifier::CLASS_UNIVERSAL
    ): UnspecifiedType {
        return $this->expectImplicit($expectedTag)
            ->implicit($tag, $expectedClass);
    }
}
