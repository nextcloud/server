<?php declare(strict_types=1);
/*
 * This file is part of PHPUnit.
 *
 * (c) Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace PHPUnit\Framework\Attributes;

use Attribute;

/**
 * @psalm-immutable
 *
 * @no-named-arguments Parameter names are not covered by the backward compatibility promise for PHPUnit
 *
 * @deprecated https://github.com/sebastianbergmann/phpunit/issues/5513
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
final class IgnoreFunctionForCodeCoverage
{
    /**
     * @psalm-var non-empty-string
     */
    private readonly string $functionName;

    /**
     * @psalm-param non-empty-string $functionName
     */
    public function __construct(string $functionName)
    {
        $this->functionName = $functionName;
    }

    /**
     * @psalm-return non-empty-string
     */
    public function functionName(): string
    {
        return $this->functionName;
    }
}
