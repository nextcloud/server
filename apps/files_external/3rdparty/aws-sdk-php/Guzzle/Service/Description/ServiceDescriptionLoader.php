<?php

namespace Guzzle\Service\Description;

use Guzzle\Service\AbstractConfigLoader;
use Guzzle\Service\Exception\DescriptionBuilderException;

/**
 * Loader for service descriptions
 */
class ServiceDescriptionLoader extends AbstractConfigLoader
{
    protected function build($config, array $options)
    {
        $operations = array();
        if (!empty($config['operations'])) {
            foreach ($config['operations'] as $name => $op) {
                $name = $op['name'] = isset($op['name']) ? $op['name'] : $name;
                // Extend other operations
                if (!empty($op['extends'])) {
                    $this->resolveExtension($name, $op, $operations);
                }
                $op['parameters'] = isset($op['parameters']) ? $op['parameters'] : array();
                $operations[$name] = $op;
            }
        }

        return new ServiceDescription(array(
            'apiVersion'  => isset($config['apiVersion']) ? $config['apiVersion'] : null,
            'baseUrl'     => isset($config['baseUrl']) ? $config['baseUrl'] : null,
            'description' => isset($config['description']) ? $config['description'] : null,
            'operations'  => $operations,
            'models'      => isset($config['models']) ? $config['models'] : null
        ) + $config);
    }

    /**
     * @param string $name       Name of the operation
     * @param array  $op         Operation value array
     * @param array  $operations Currently loaded operations
     * @throws DescriptionBuilderException when extending a non-existent operation
     */
    protected function resolveExtension($name, array &$op, array &$operations)
    {
        $resolved = array();
        $original = empty($op['parameters']) ? false: $op['parameters'];
        $hasClass = !empty($op['class']);
        foreach ((array) $op['extends'] as $extendedCommand) {
            if (empty($operations[$extendedCommand])) {
                throw new DescriptionBuilderException("{$name} extends missing operation {$extendedCommand}");
            }
            $toArray = $operations[$extendedCommand];
            $resolved = empty($resolved)
                ? $toArray['parameters']
                : array_merge($resolved, $toArray['parameters']);

            $op = $op + $toArray;
            if (!$hasClass && isset($toArray['class'])) {
                $op['class'] = $toArray['class'];
            }
        }
        $op['parameters'] = $original ? array_merge($resolved, $original) : $resolved;
    }
}
