<?php

namespace Guzzle\Service\Command\LocationVisitor\Response;

use Guzzle\Http\Message\Response;
use Guzzle\Service\Description\Parameter;
use Guzzle\Service\Command\CommandInterface;

/**
 * Location visitor used to add a particular header of a response to a key in the result
 */
class HeaderVisitor extends AbstractResponseVisitor
{
    public function visit(
        CommandInterface $command,
        Response $response,
        Parameter $param,
        &$value,
        $context =  null
    ) {
        if ($param->getType() == 'object' && $param->getAdditionalProperties() instanceof Parameter) {
            $this->processPrefixedHeaders($response, $param, $value);
        } else {
            $value[$param->getName()] = $param->filter((string) $response->getHeader($param->getWireName()));
        }
    }

    /**
     * Process a prefixed header array
     *
     * @param Response  $response Response that contains the headers
     * @param Parameter $param    Parameter object
     * @param array     $value    Value response array to modify
     */
    protected function processPrefixedHeaders(Response $response, Parameter $param, &$value)
    {
        // Grab prefixed headers that should be placed into an array with the prefix stripped
        if ($prefix = $param->getSentAs()) {
            $container = $param->getName();
            $len = strlen($prefix);
            // Find all matching headers and place them into the containing element
            foreach ($response->getHeaders()->toArray() as $key => $header) {
                if (stripos($key, $prefix) === 0) {
                    // Account for multi-value headers
                    $value[$container][substr($key, $len)] = count($header) == 1 ? end($header) : $header;
                }
            }
        }
    }
}
