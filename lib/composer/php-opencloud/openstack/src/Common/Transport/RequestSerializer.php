<?php

declare(strict_types=1);

namespace OpenStack\Common\Transport;

use OpenStack\Common\Api\Operation;
use OpenStack\Common\Api\Parameter;

class RequestSerializer
{
    private $jsonSerializer;

    public function __construct(JsonSerializer $jsonSerializer = null)
    {
        $this->jsonSerializer = $jsonSerializer ?: new JsonSerializer();
    }

    public function serializeOptions(Operation $operation, array $userValues = []): array
    {
        $options = ['headers' => []];

        foreach ($userValues as $paramName => $paramValue) {
            if (null === ($schema = $operation->getParam($paramName))) {
                continue;
            }

            $this->callStockingMethod($schema, $paramValue, $options);
        }

        if (!empty($options['json'])) {
            if ($key = $operation->getJsonKey()) {
                $options['json'] = [$key => $options['json']];
            }
            if (false !== strpos(json_encode($options['json']), '\/')) {
                $options['body']                    = json_encode($options['json'], JSON_UNESCAPED_SLASHES);
                $options['headers']['Content-Type'] = 'application/json';
                unset($options['json']);
            }
        }

        return $options;
    }

    private function callStockingMethod(Parameter $schema, $paramValue, array &$options)
    {
        $location = $schema->getLocation();

        $methods = ['query', 'header', 'json', 'raw'];
        if (!in_array($location, $methods)) {
            return;
        }

        $method = sprintf('stock%s', ucfirst($location));
        $this->$method($schema, $paramValue, $options);
    }

    private function stockQuery(Parameter $schema, $paramValue, array &$options)
    {
        $options['query'][$schema->getName()] = $paramValue;
    }

    private function stockHeader(Parameter $schema, $paramValue, array &$options)
    {
        $paramName = $schema->getName();

        if (false !== stripos($paramName, 'metadata')) {
            return $this->stockMetadataHeader($schema, $paramValue, $options);
        }

        $options['headers'] += is_scalar($paramValue) ? [$schema->getPrefixedName() => $paramValue] : [];
    }

    private function stockMetadataHeader(Parameter $schema, $paramValue, array &$options)
    {
        foreach ($paramValue as $key => $keyVal) {
            $schema = $schema->getItemSchema() ?: new Parameter(['prefix' => $schema->getPrefix(), 'name' => $key]);
            $this->stockHeader($schema, $keyVal, $options);
        }
    }

    private function stockJson(Parameter $schema, $paramValue, array &$options)
    {
        $json            = isset($options['json']) ? $options['json'] : [];
        $options['json'] = $this->jsonSerializer->stockJson($schema, $paramValue, $json);
    }

    private function stockRaw(Parameter $schema, $paramValue, array &$options)
    {
        $options['body'] = $paramValue;
    }
}
