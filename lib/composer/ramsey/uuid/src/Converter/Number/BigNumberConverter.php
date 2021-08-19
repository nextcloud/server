<?php

/**
 * This file is part of the ramsey/uuid library
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @copyright Copyright (c) Ben Ramsey <ben@benramsey.com>
 * @license http://opensource.org/licenses/MIT MIT
 */

declare(strict_types=1);

namespace Ramsey\Uuid\Converter\Number;

use Ramsey\Uuid\Converter\NumberConverterInterface;
use Ramsey\Uuid\Math\BrickMathCalculator;

/**
 * Previously used to integrate moontoast/math as a bignum arithmetic library,
 * BigNumberConverter is deprecated in favor of GenericNumberConverter
 *
 * @deprecated Transition to {@see GenericNumberConverter}.
 *
 * @psalm-immutable
 */
class BigNumberConverter implements NumberConverterInterface
{
    /**
     * @var NumberConverterInterface
     */
    private $converter;

    public function __construct()
    {
        $this->converter = new GenericNumberConverter(new BrickMathCalculator());
    }

    /**
     * @inheritDoc
     * @psalm-pure
     */
    public function fromHex(string $hex): string
    {
        return $this->converter->fromHex($hex);
    }

    /**
     * @inheritDoc
     * @psalm-pure
     */
    public function toHex(string $number): string
    {
        return $this->converter->toHex($number);
    }
}
