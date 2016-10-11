<?php

namespace Aws\Common\Command;

use Guzzle\Service\Description\Operation;
use Guzzle\Service\Command\CommandInterface;
use Guzzle\Http\Message\Response;
use Guzzle\Service\Description\Parameter;
use Guzzle\Service\Command\LocationVisitor\Response\XmlVisitor;

/**
 * Class used for custom AWS XML response parsing of query services
 */
class XmlResponseLocationVisitor extends XmlVisitor
{
    /**
     * {@inheritdoc}
     */
    public function before(CommandInterface $command, array &$result)
    {
        parent::before($command, $result);

        // Unwrapped wrapped responses
        $operation = $command->getOperation();
        if ($operation->getServiceDescription()->getData('resultWrapped')) {
            $wrappingNode = $operation->getName() . 'Result';
            if (isset($result[$wrappingNode])) {
                $result = $result[$wrappingNode] + $result;
                unset($result[$wrappingNode]);
            }
        }
    }

    /**
     * Accounts for wrapper nodes
     * {@inheritdoc}
     */
    public function visit(
        CommandInterface $command,
        Response $response,
        Parameter $param,
        &$value,
        $context =  null
    ) {
        parent::visit($command, $response, $param, $value, $context);

        // Account for wrapper nodes (e.g. RDS, ElastiCache, etc)
        if ($param->getData('wrapper')) {
            $wireName = $param->getWireName();
            $value += $value[$wireName];
            unset($value[$wireName]);
        }
    }

    /**
     * Filter used when converting XML maps into associative arrays in service descriptions
     *
     * @param array  $value     Value to filter
     * @param string $entryName Name of each entry
     * @param string $keyName   Name of each key
     * @param string $valueName Name of each value
     *
     * @return array Returns the map of the XML data
     */
    public static function xmlMap($value, $entryName, $keyName, $valueName)
    {
        $result = array();
        foreach ($value as $entry) {
            $result[$entry[$keyName]] = $entry[$valueName];
        }

        return $result;
    }
}
