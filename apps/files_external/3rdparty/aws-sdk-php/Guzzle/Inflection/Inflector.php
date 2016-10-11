<?php

namespace Guzzle\Inflection;

/**
 * Default inflection implementation
 */
class Inflector implements InflectorInterface
{
    /** @var InflectorInterface */
    protected static $default;

    /**
     * Get the default inflector object that has support for caching
     *
     * @return MemoizingInflector
     */
    public static function getDefault()
    {
        // @codeCoverageIgnoreStart
        if (!self::$default) {
            self::$default = new MemoizingInflector(new self());
        }
        // @codeCoverageIgnoreEnd

        return self::$default;
    }

    public function snake($word)
    {
        return ctype_lower($word) ? $word : strtolower(preg_replace('/(.)([A-Z])/', "$1_$2", $word));
    }

    public function camel($word)
    {
        return str_replace(' ', '', ucwords(strtr($word, '_-', '  ')));
    }
}
