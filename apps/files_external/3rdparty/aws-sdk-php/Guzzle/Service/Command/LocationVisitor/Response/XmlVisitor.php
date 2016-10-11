<?php

namespace Guzzle\Service\Command\LocationVisitor\Response;

use Guzzle\Http\Message\Response;
use Guzzle\Service\Description\Parameter;
use Guzzle\Service\Command\CommandInterface;

/**
 * Location visitor used to marshal XML response data into a formatted array
 */
class XmlVisitor extends AbstractResponseVisitor
{
    public function before(CommandInterface $command, array &$result)
    {
        // Set the result of the command to the array conversion of the XML body
        $result = json_decode(json_encode($command->getResponse()->xml()), true);
    }

    public function visit(
        CommandInterface $command,
        Response $response,
        Parameter $param,
        &$value,
        $context =  null
    ) {
        $sentAs = $param->getWireName();
        $name = $param->getName();
        if (isset($value[$sentAs])) {
            $this->recursiveProcess($param, $value[$sentAs]);
            if ($name != $sentAs) {
                $value[$name] = $value[$sentAs];
                unset($value[$sentAs]);
            }
        }
    }

    /**
     * Recursively process a parameter while applying filters
     *
     * @param Parameter $param API parameter being processed
     * @param mixed     $value Value to validate and process. The value may change during this process.
     */
    protected function recursiveProcess(Parameter $param, &$value)
    {
        $type = $param->getType();

        if (!is_array($value)) {
            if ($type == 'array') {
                // Cast to an array if the value was a string, but should be an array
                $this->recursiveProcess($param->getItems(), $value);
                $value = array($value);
            }
        } elseif ($type == 'object') {
            $this->processObject($param, $value);
        } elseif ($type == 'array') {
            $this->processArray($param, $value);
        } elseif ($type == 'string' && gettype($value) == 'array') {
            $value = '';
        }

        if ($value !== null) {
            $value = $param->filter($value);
        }
    }

    /**
     * Process an array
     *
     * @param Parameter $param API parameter being parsed
     * @param mixed     $value Value to process
     */
    protected function processArray(Parameter $param, &$value)
    {
        // Convert the node if it was meant to be an array
        if (!isset($value[0])) {
            // Collections fo nodes are sometimes wrapped in an additional array. For example:
            // <Items><Item><a>1</a></Item><Item><a>2</a></Item></Items> should become:
            // array('Items' => array(array('a' => 1), array('a' => 2))
            // Some nodes are not wrapped. For example: <Foo><a>1</a></Foo><Foo><a>2</a></Foo>
            // should become array('Foo' => array(array('a' => 1), array('a' => 2))
            if ($param->getItems() && isset($value[$param->getItems()->getWireName()])) {
                // Account for the case of a collection wrapping wrapped nodes: Items => Item[]
                $value = $value[$param->getItems()->getWireName()];
                // If the wrapped node only had one value, then make it an array of nodes
                if (!isset($value[0]) || !is_array($value)) {
                    $value = array($value);
                }
            } elseif (!empty($value)) {
                // Account for repeated nodes that must be an array: Foo => Baz, Foo => Baz, but only if the
                // value is set and not empty
                $value = array($value);
            }
        }

        foreach ($value as &$item) {
            $this->recursiveProcess($param->getItems(), $item);
        }
    }

    /**
     * Process an object
     *
     * @param Parameter $param API parameter being parsed
     * @param mixed     $value Value to process
     */
    protected function processObject(Parameter $param, &$value)
    {
        // Ensure that the array is associative and not numerically indexed
        if (!isset($value[0]) && ($properties = $param->getProperties())) {
            $knownProperties = array();
            foreach ($properties as $property) {
                $name = $property->getName();
                $sentAs = $property->getWireName();
                $knownProperties[$name] = 1;
                if ($property->getData('xmlAttribute')) {
                    $this->processXmlAttribute($property, $value);
                } elseif (isset($value[$sentAs])) {
                    $this->recursiveProcess($property, $value[$sentAs]);
                    if ($name != $sentAs) {
                        $value[$name] = $value[$sentAs];
                        unset($value[$sentAs]);
                    }
                }
            }

            // Remove any unknown and potentially unsafe properties
            if ($param->getAdditionalProperties() === false) {
                $value = array_intersect_key($value, $knownProperties);
            }
        }
    }

    /**
     * Process an XML attribute property
     *
     * @param Parameter $property Property to process
     * @param array     $value    Value to process and update
     */
    protected function processXmlAttribute(Parameter $property, array &$value)
    {
        $sentAs = $property->getWireName();
        if (isset($value['@attributes'][$sentAs])) {
            $value[$property->getName()] = $value['@attributes'][$sentAs];
            unset($value['@attributes'][$sentAs]);
            if (empty($value['@attributes'])) {
                unset($value['@attributes']);
            }
        }
    }
}
