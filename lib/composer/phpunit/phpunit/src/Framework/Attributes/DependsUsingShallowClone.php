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
 */
#[Attribute(Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
final class DependsUsingShallowClone
{
    /**
     * @psalm-var non-empty-string
     */
    private readonly string $methodName;

    /**
     * @psalm-param non-empty-string $methodName
     */
    public function __construct(string $methodName)
    {
        $this->methodName = $methodName;
    }

    /**
     * @psalm-return non-empty-string
     */
    public function methodName(): string
    {
        return $this->methodName;
    }
}
