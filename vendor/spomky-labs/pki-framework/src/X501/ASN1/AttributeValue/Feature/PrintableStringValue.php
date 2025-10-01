<?php

declare(strict_types=1);

namespace SpomkyLabs\Pki\X501\ASN1\AttributeValue\Feature;

use SpomkyLabs\Pki\ASN1\Element;
use SpomkyLabs\Pki\ASN1\Type\Primitive\PrintableString;
use SpomkyLabs\Pki\X501\ASN1\AttributeValue\AttributeValue;
use SpomkyLabs\Pki\X501\DN\DNParser;
use SpomkyLabs\Pki\X501\MatchingRule\CaseIgnoreMatch;
use SpomkyLabs\Pki\X501\MatchingRule\MatchingRule;

/**
 * Base class for attribute values having *PrintableString* syntax.
 */
abstract class PrintableStringValue extends AttributeValue
{
    /**
     * @param string $_string String value
     */
    protected function __construct(
        string $oid,
        protected string $_string
    ) {
        parent::__construct($oid);
    }

    public function toASN1(): Element
    {
        return PrintableString::create($this->_string);
    }

    public function stringValue(): string
    {
        return $this->_string;
    }

    public function equalityMatchingRule(): MatchingRule
    {
        // default to caseIgnoreMatch
        return CaseIgnoreMatch::create(Element::TYPE_PRINTABLE_STRING);
    }

    public function rfc2253String(): string
    {
        return DNParser::escapeString($this->_transcodedString());
    }

    protected function _transcodedString(): string
    {
        // PrintableString maps directly to UTF-8
        return $this->_string;
    }
}
