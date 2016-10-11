<?php

namespace Guzzle\Service\Command\LocationVisitor\Request;

use Guzzle\Service\Command\CommandInterface;
use Guzzle\Http\Message\RequestInterface;
use Guzzle\Service\Description\Parameter;

abstract class AbstractRequestVisitor implements RequestVisitorInterface
{
    /**
     * @codeCoverageIgnore
     */
    public function after(CommandInterface $command, RequestInterface $request) {}

    /**
     * @codeCoverageIgnore
     */
    public function visit(CommandInterface $command, RequestInterface $request, Parameter $param, $value) {}

    /**
     * Prepare (filter and set desired name for request item) the value for request.
     *
     * @param mixed                                     $value
     * @param \Guzzle\Service\Description\Parameter     $param
     *
     * @return array|mixed
     */
    protected function prepareValue($value, Parameter $param)
    {
        return is_array($value)
            ? $this->resolveRecursively($value, $param)
            : $param->filter($value);
    }

    /**
     * Map nested parameters into the location_key based parameters
     *
     * @param array     $value Value to map
     * @param Parameter $param Parameter that holds information about the current key
     *
     * @return array Returns the mapped array
     */
    protected function resolveRecursively(array $value, Parameter $param)
    {
        foreach ($value as $name => &$v) {
            switch ($param->getType()) {
                case 'object':
                    if ($subParam = $param->getProperty($name)) {
                        $key = $subParam->getWireName();
                        $value[$key] = $this->prepareValue($v, $subParam);
                        if ($name != $key) {
                            unset($value[$name]);
                        }
                    } elseif ($param->getAdditionalProperties() instanceof Parameter) {
                        $v = $this->prepareValue($v, $param->getAdditionalProperties());
                    }
                    break;
                case 'array':
                    if ($items = $param->getItems()) {
                        $v = $this->prepareValue($v, $items);
                    }
                    break;
            }
        }

        return $param->filter($value);
    }
}
