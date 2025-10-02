<?php

namespace Doctrine\DBAL\Driver\API\SQLite;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Platforms\SqlitePlatform;
use Doctrine\Deprecations\Deprecation;

use function array_merge;
use function strpos;

/**
 * User-defined SQLite functions.
 *
 * @internal
 */
final class UserDefinedFunctions
{
    private const DEFAULT_FUNCTIONS = [
        'sqrt' => ['callback' => [SqlitePlatform::class, 'udfSqrt'], 'numArgs' => 1],
        'mod'  => ['callback' => [SqlitePlatform::class, 'udfMod'], 'numArgs' => 2],
        'locate'  => ['callback' => [SqlitePlatform::class, 'udfLocate'], 'numArgs' => -1],
    ];

    /**
     * @param callable(string, callable, int): bool                  $callback
     * @param array<string, array{callback: callable, numArgs: int}> $additionalFunctions
     */
    public static function register(callable $callback, array $additionalFunctions = []): void
    {
        $userDefinedFunctions = array_merge(self::DEFAULT_FUNCTIONS, $additionalFunctions);

        foreach ($userDefinedFunctions as $function => $data) {
            $callback($function, $data['callback'], $data['numArgs']);
        }
    }

    /**
     * User-defined function that implements MOD().
     *
     * @param int $a
     * @param int $b
     */
    public static function mod($a, $b): int
    {
        return $a % $b;
    }

    /**
     * User-defined function that implements LOCATE().
     *
     * @param string $str
     * @param string $substr
     * @param int    $offset
     */
    public static function locate($str, $substr, $offset = 0): int
    {
        Deprecation::trigger(
            'doctrine/dbal',
            'https://github.com/doctrine/dbal/pull/5749',
            'Relying on DBAL\'s emulated LOCATE() function is deprecated. '
                . 'Use INSTR() or %s::getLocateExpression() instead.',
            AbstractPlatform::class,
        );

        // SQL's LOCATE function works on 1-based positions, while PHP's strpos works on 0-based positions.
        // So we have to make them compatible if an offset is given.
        if ($offset > 0) {
            $offset -= 1;
        }

        $pos = strpos($str, $substr, $offset);

        if ($pos !== false) {
            return $pos + 1;
        }

        return 0;
    }
}
