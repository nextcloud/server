<?php declare(strict_types=1);
/*
 * This file is part of PHPUnit.
 *
 * (c) Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace PHPUnit\Framework\MockObject\Generator;

use function sprintf;

/**
 * @no-named-arguments Parameter names are not covered by the backward compatibility promise for PHPUnit
 *
 * @internal This class is not covered by the backward compatibility promise for PHPUnit
 */
final class CannotUseAddMethodsException extends \PHPUnit\Framework\Exception implements Exception
{
    public function __construct(string $type, string $methodName)
    {
        parent::__construct(
            sprintf(
                'Trying to configure method "%s" with addMethods(), but it exists in class "%s". Use onlyMethods() for methods that exist in the class',
                $methodName,
                $type,
            ),
        );
    }
}
