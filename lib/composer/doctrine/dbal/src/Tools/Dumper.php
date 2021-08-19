<?php

namespace Doctrine\DBAL\Tools;

use ArrayIterator;
use ArrayObject;
use DateTimeInterface;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Persistence\Proxy;
use stdClass;

use function array_keys;
use function assert;
use function class_exists;
use function count;
use function end;
use function explode;
use function extension_loaded;
use function get_class;
use function html_entity_decode;
use function ini_set;
use function is_array;
use function is_object;
use function is_string;
use function ob_get_clean;
use function ob_start;
use function strip_tags;
use function strlen;
use function strrpos;
use function substr;
use function var_dump;

/**
 * Static class used to dump the variable to be used on output.
 * Simplified port of Util\Debug from doctrine/common.
 *
 * @internal
 */
final class Dumper
{
    /**
     * Private constructor (prevents instantiation).
     *
     * @codeCoverageIgnore
     */
    private function __construct()
    {
    }

    /**
     * Returns a dump of the public, protected and private properties of $var.
     *
     * @link https://xdebug.org/
     *
     * @param mixed $var      The variable to dump.
     * @param int   $maxDepth The maximum nesting level for object properties.
     */
    public static function dump($var, int $maxDepth = 2): string
    {
        $html = ini_set('html_errors', '1');
        assert(is_string($html));

        if (extension_loaded('xdebug')) {
            ini_set('xdebug.var_display_max_depth', (string) $maxDepth);
        }

        $var = self::export($var, $maxDepth);

        ob_start();
        var_dump($var);

        try {
            $output = ob_get_clean();
            assert(is_string($output));

            return strip_tags(html_entity_decode($output));
        } finally {
            ini_set('html_errors', $html);
        }
    }

    /**
     * @param mixed $var
     *
     * @return mixed
     */
    public static function export($var, int $maxDepth)
    {
        $return = null;
        $isObj  = is_object($var);

        if ($var instanceof Collection) {
            $var = $var->toArray();
        }

        if ($maxDepth === 0) {
            return is_object($var) ? get_class($var)
                : (is_array($var) ? 'Array(' . count($var) . ')' : $var);
        }

        if (is_array($var)) {
            $return = [];

            foreach ($var as $k => $v) {
                $return[$k] = self::export($v, $maxDepth - 1);
            }

            return $return;
        }

        if (! $isObj) {
            return $var;
        }

        $return = new stdClass();
        if ($var instanceof DateTimeInterface) {
            $return->__CLASS__ = get_class($var);
            $return->date      = $var->format('c');
            $return->timezone  = $var->getTimezone()->getName();

            return $return;
        }

        $return->__CLASS__ = self::getClass($var);

        if ($var instanceof Proxy) {
            $return->__IS_PROXY__          = true;
            $return->__PROXY_INITIALIZED__ = $var->__isInitialized();
        }

        if ($var instanceof ArrayObject || $var instanceof ArrayIterator) {
            $return->__STORAGE__ = self::export($var->getArrayCopy(), $maxDepth - 1);
        }

        return self::fillReturnWithClassAttributes($var, $return, $maxDepth);
    }

    /**
     * Fill the $return variable with class attributes
     * Based on obj2array function from {@see https://secure.php.net/manual/en/function.get-object-vars.php#47075}
     *
     * @param object $var
     *
     * @return mixed
     */
    private static function fillReturnWithClassAttributes($var, stdClass $return, int $maxDepth)
    {
        $clone = (array) $var;

        foreach (array_keys($clone) as $key) {
            $aux  = explode("\0", $key);
            $name = end($aux);
            if ($aux[0] === '') {
                $name .= ':' . ($aux[1] === '*' ? 'protected' : $aux[1] . ':private');
            }

            $return->$name = self::export($clone[$key], $maxDepth - 1);
        }

        return $return;
    }

    /**
     * @param object $object
     */
    private static function getClass($object): string
    {
        $class = get_class($object);

        if (! class_exists(Proxy::class)) {
            return $class;
        }

        $pos = strrpos($class, '\\' . Proxy::MARKER . '\\');

        if ($pos === false) {
            return $class;
        }

        return substr($class, $pos + strlen(Proxy::MARKER) + 2);
    }
}
