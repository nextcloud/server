<?php

declare(strict_types=1);

namespace SpomkyLabs\Pki\X501\MatchingRule;

use SpomkyLabs\Pki\X501\StringPrep\StringPreparer;

/**
 * Implements 'caseExactMatch' matching rule.
 *
 * @see https://tools.ietf.org/html/rfc4517#section-4.2.4
 */
final class CaseExactMatch extends StringPrepMatchingRule
{
    /**
     * @param int $stringType ASN.1 string type tag
     */
    private function __construct(int $stringType)
    {
        parent::__construct(StringPreparer::forStringType($stringType));
    }

    public static function create(int $stringType): self
    {
        return new self($stringType);
    }
}
