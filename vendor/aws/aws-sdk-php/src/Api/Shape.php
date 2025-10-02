<?php
namespace Aws\Api;

/**
 * Base class representing a modeled shape.
 */
class Shape extends AbstractModel
{
    /**
     * Get a concrete shape for the given definition.
     *
     * @param array    $definition
     * @param ShapeMap $shapeMap
     *
     * @return mixed
     * @throws \RuntimeException if the type is invalid
     */
    public static function create(array $definition, ShapeMap $shapeMap)
    {
        static $map = [
            'structure' => StructureShape::class,
            'map'       => MapShape::class,
            'list'      => ListShape::class,
            'timestamp' => TimestampShape::class,
            'integer'   => Shape::class,
            'double'    => Shape::class,
            'float'     => Shape::class,
            'long'      => Shape::class,
            'string'    => Shape::class,
            'byte'      => Shape::class,
            'character' => Shape::class,
            'blob'      => Shape::class,
            'boolean'   => Shape::class
        ];

        if (isset($definition['shape'])) {
            return $shapeMap->resolve($definition);
        }

        if (!isset($map[$definition['type']])) {
            throw new \RuntimeException('Invalid type: '
                . print_r($definition, true));
        }

        $type = $map[$definition['type']];

        return new $type($definition, $shapeMap);
    }

    /**
     * Get the type of the shape
     *
     * @return string
     */
    public function getType()
    {
        return $this->definition['type'];
    }

    /**
     * Get the name of the shape
     *
     * @return string
     */
    public function getName()
    {
        return $this->definition['name'];
    }

    /**
     * Get a context param definition.
     */
    public function getContextParam()
    {
        return $this->contextParam;
    }
}
