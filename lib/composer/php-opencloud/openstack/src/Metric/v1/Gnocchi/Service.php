<?php

declare(strict_types=1);

namespace OpenStack\Metric\v1\Gnocchi;

use OpenStack\Common\Service\AbstractService;
use OpenStack\Metric\v1\Gnocchi\Models\Metric;
use OpenStack\Metric\v1\Gnocchi\Models\Resource;
use OpenStack\Metric\v1\Gnocchi\Models\ResourceType;

/**
 * Gnocci Metric v1 Service class.
 *
 * @property Api $api
 */
class Service extends AbstractService
{
    /**
     * Retrieves a collection of \OpenStack\Metric\v1\Gnocchi\Models\ResourceType type in a generator format.
     */
    public function listResourceTypes(): \Generator
    {
        return $this->model(ResourceType::class)->enumerate($this->api->getResourceTypes(), []);
    }

    /**
     * Retrieves a collection of \OpenStack\Metric\v1\Gnocchi\Models\Resource type in a generator format.
     *
     * @param array $options {@see \OpenStack\Metric\v1\Gnocchi\Api::getResources}
     */
    public function listResources(array $options = []): \Generator
    {
        $this->injectGenericType($options);

        return $this->model(Resource::class)->enumerate($this->api->getResources(), $options);
    }

    /**
     * Retrieves a Resource object and populates its unique identifier object. This operation will not perform a GET or
     * HEAD request by default; you will need to call retrieve() if you want to pull in remote state from the API.
     */
    public function getResource(array $options = []): Resource
    {
        $this->injectGenericType($options);

        /** @var resource $resource */
        $resource = $this->model(Resource::class);
        $resource->populateFromArray($options);

        return $resource;
    }

    /**
     * Retrieves a collection of \OpenStack\Metric\v1\Gnocchi\Models\Resource type in a generator format.
     *
     * @param array $options {@see \OpenStack\Metric\v1\Gnocchi\Api::searchResources}
     */
    public function searchResources(array $options = []): \Generator
    {
        $this->injectGenericType($options);

        /**
         * $options['criteria'] must send as STRING
         * This will check input $options and perform json_encode if needed.
         */
        if (isset($options['criteria']) && !is_string($options['criteria'])) {
            $options['criteria'] = json_encode($options['criteria']);
        }

        /*
         * We need to manually add content-type header to this request
         * since searchResources method sends RAW request body.
         */
        $options['contentType'] = 'application/json';

        return $this->model(Resource::class)->enumerate($this->api->searchResources(), $options);
    }

    /**
     * Retrieves a Metric object and populates its unique identifier object. This operation will not perform a GET or
     * HEAD request by default; you will need to call retrieve() if you want to pull in remote state from the API.
     */
    public function getMetric(string $id): Metric
    {
        /** @var Metric $metric */
        $metric = $this->model(Metric::class);
        $metric->populateFromArray(['id' => $id]);

        return $metric;
    }

    /**
     * Retrieves a collection of Metric type in a generator format.
     *
     * @param array $options {@see \OpenStack\Metric\v1\Gnocchi\Api::getMetrics}
     */
    public function listMetrics(array $options = []): \Generator
    {
        return $this->model(Metric::class)->enumerate($this->api->getMetrics(), $options);
    }

    /**
     * If options does not have type, this will inject $options['type'] = 'generic'.
     *
     * @internal
     */
    private function injectGenericType(array &$options)
    {
        if (empty($options) || !isset($options['type'])) {
            $options['type'] = Resource::RESOURCE_TYPE_GENERIC;
        }
    }
}
