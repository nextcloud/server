<?php

namespace Safe;

use Safe\Exceptions\UopzException;

/**
 * Makes class extend parent
 *
 * @param string $class The name of the class to extend
 * @param string $parent The name of the class to inherit
 * @throws UopzException
 *
 */
function uopz_extend(string $class, string $parent): void
{
    error_clear_last();
    $result = \uopz_extend($class, $parent);
    if ($result === false) {
        throw UopzException::createFromPhpError();
    }
}


/**
 * Makes class implement interface
 *
 * @param string $class
 * @param string $interface
 * @throws UopzException
 *
 */
function uopz_implement(string $class, string $interface): void
{
    error_clear_last();
    $result = \uopz_implement($class, $interface);
    if ($result === false) {
        throw UopzException::createFromPhpError();
    }
}
