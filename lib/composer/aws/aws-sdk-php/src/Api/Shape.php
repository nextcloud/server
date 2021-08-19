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
            'structure' => 'Aws\Api\StructureShape',
            'map'       => 'Aws\Api\MapShape',
            'list'      => 'Aws\Api\ListShape',
            'timestamp' => 'Aws\Api\TimestampShape',
            'integer'   => 'Aws\Api\Shape',
            'double'    => 'Aws\Api\Shape',
            'float'     => 'Aws\Api\Shape',
            'long'      => 'Aws\Api\Shape',
            'string'    => 'Aws\Api\Shape',
            'byte'      => 'Aws\Api\Shape',
            'character' => 'Aws\Api\Shape',
            'blob'      => 'Aws\Api\Shape',
            'boolean'   => 'Aws\Api\Shape'
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
}
