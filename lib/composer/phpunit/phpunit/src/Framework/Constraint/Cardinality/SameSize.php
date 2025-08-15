<?php declare(strict_types=1);
/*
 * This file is part of PHPUnit.
 *
 * (c) Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace PHPUnit\Framework\Constraint;

use Countable;
use PHPUnit\Framework\Exception;

/**
 * @no-named-arguments Parameter names are not covered by the backward compatibility promise for PHPUnit
 */
final class SameSize extends Count
{
    /**
     * @psalm-param Countable|iterable $expected
     *
     * @throws Exception
     */
    public function __construct($expected)
    {
        parent::__construct((int) $this->getCountOf($expected));
    }
}
