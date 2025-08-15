<?php declare(strict_types=1);
/*
 * This file is part of PHPUnit.
 *
 * (c) Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace PHPUnit\Util;

use function in_array;

/**
 * @no-named-arguments Parameter names are not covered by the backward compatibility promise for PHPUnit
 *
 * @psalm-immutable
 */
final class VersionComparisonOperator
{
    /**
     * @psalm-var '<'|'lt'|'<='|'le'|'>'|'gt'|'>='|'ge'|'=='|'='|'eq'|'!='|'<>'|'ne'
     */
    private readonly string $operator;

    /**
     * @psalm-param '<'|'lt'|'<='|'le'|'>'|'gt'|'>='|'ge'|'=='|'='|'eq'|'!='|'<>'|'ne' $operator
     *
     * @throws InvalidVersionOperatorException
     */
    public function __construct(string $operator)
    {
        $this->ensureOperatorIsValid($operator);

        $this->operator = $operator;
    }

    /**
     * @psalm-return '<'|'lt'|'<='|'le'|'>'|'gt'|'>='|'ge'|'=='|'='|'eq'|'!='|'<>'|'ne'
     */
    public function asString(): string
    {
        return $this->operator;
    }

    /**
     * @psalm-param '<'|'lt'|'<='|'le'|'>'|'gt'|'>='|'ge'|'=='|'='|'eq'|'!='|'<>'|'ne' $operator
     *
     * @throws InvalidVersionOperatorException
     */
    private function ensureOperatorIsValid(string $operator): void
    {
        if (!in_array($operator, ['<', 'lt', '<=', 'le', '>', 'gt', '>=', 'ge', '==', '=', 'eq', '!=', '<>', 'ne'], true)) {
            throw new InvalidVersionOperatorException($operator);
        }
    }
}
