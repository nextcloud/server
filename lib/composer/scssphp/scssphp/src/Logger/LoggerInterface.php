<?php

/**
 * SCSSPHP
 *
 * @copyright 2012-2020 Leaf Corcoran
 *
 * @license http://opensource.org/licenses/MIT MIT
 *
 * @link http://scssphp.github.io/scssphp
 */

namespace ScssPhp\ScssPhp\Logger;

/**
 * Interface implemented by loggers for warnings and debug messages.
 *
 * The official Sass implementation recommends that loggers report the
 * messages immediately rather than waiting for the end of the
 * compilation, to provide a better debugging experience when the
 * compilation does not end (error or infinite loop after the warning
 * for instance).
 */
interface LoggerInterface
{
    /**
     * Emits a warning with the given message.
     *
     * If $deprecation is true, it indicates that this is a deprecation
     * warning. Implementations should surface all this information to
     * the end user.
     *
     * @param string $message
     * @param bool  $deprecation
     *
     * @return void
     */
    public function warn($message, $deprecation = false);

    /**
     * Emits a debugging message.
     *
     * @param string $message
     *
     * @return void
     */
    public function debug($message);
}
