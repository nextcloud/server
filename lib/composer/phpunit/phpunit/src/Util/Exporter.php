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

use function is_array;
use function is_scalar;
use SebastianBergmann\RecursionContext\Context;

/**
 * @no-named-arguments Parameter names are not covered by the backward compatibility promise for PHPUnit
 *
 * @internal This class is not covered by the backward compatibility promise for PHPUnit
 *
 * @deprecated
 */
final class Exporter
{
    public static function export(mixed $value, bool $exportObjects = false): string
    {
        if (self::isExportable($value) || $exportObjects) {
            return (new \SebastianBergmann\Exporter\Exporter)->export($value);
        }

        return '{enable export of objects to see this value}';
    }

    private static function isExportable(mixed &$value, ?Context $context = null): bool
    {
        if (is_scalar($value) || $value === null) {
            return true;
        }

        if (!is_array($value)) {
            return false;
        }

        if (!$context) {
            $context = new Context;
        }

        if ($context->contains($value) !== false) {
            return true;
        }

        $array = $value;
        $context->add($value);

        foreach ($array as &$_value) {
            if (!self::isExportable($_value, $context)) {
                return false;
            }
        }

        return true;
    }
}
