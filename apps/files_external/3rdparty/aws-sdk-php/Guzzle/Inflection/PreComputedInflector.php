<?php

namespace Guzzle\Inflection;

/**
 * Decorator used to add pre-computed inflection mappings to an inflector
 */
class PreComputedInflector implements InflectorInterface
{
    /** @var array Array of pre-computed inflections */
    protected $mapping = array(
        'snake' => array(),
        'camel' => array()
    );

    /** @var InflectorInterface Decorated inflector */
    protected $decoratedInflector;

    /**
     * @param InflectorInterface $inflector Inflector being decorated
     * @param array              $snake     Hash of pre-computed camel to snake
     * @param array              $camel     Hash of pre-computed snake to camel
     * @param bool               $mirror    Mirror snake and camel reflections
     */
    public function __construct(InflectorInterface $inflector, array $snake = array(), array $camel = array(), $mirror = false)
    {
        if ($mirror) {
            $camel = array_merge(array_flip($snake), $camel);
            $snake = array_merge(array_flip($camel), $snake);
        }

        $this->decoratedInflector = $inflector;
        $this->mapping = array(
            'snake' => $snake,
            'camel' => $camel
        );
    }

    public function snake($word)
    {
        return isset($this->mapping['snake'][$word])
            ? $this->mapping['snake'][$word]
            : $this->decoratedInflector->snake($word);
    }

    /**
     * Converts strings from snake_case to upper CamelCase
     *
     * @param string $word Value to convert into upper CamelCase
     *
     * @return string
     */
    public function camel($word)
    {
        return isset($this->mapping['camel'][$word])
            ? $this->mapping['camel'][$word]
            : $this->decoratedInflector->camel($word);
    }
}
