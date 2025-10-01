<?php

declare(strict_types=1);

namespace OpenStack\Metric\v1\Gnocchi;

use OpenStack\Common\Api\AbstractApi;

class Api extends AbstractApi
{
    private $pathPrefix = 'v1';

    public function __construct()
    {
        $this->params = new Params();
    }

    public function getResources(): array
    {
        return [
            'path'   => $this->pathPrefix.'/resource/{type}',
            'method' => 'GET',
            'params' => [
                'limit'  => $this->params->limit(),
                'marker' => $this->params->marker(),
                'sort'   => $this->params->sort(),
                'type'   => $this->params->resourceType(),
            ],
        ];
    }

    public function getResource(): array
    {
        return [
            'path'   => $this->pathPrefix.'/resource/{type}/{id}',
            'method' => 'GET',
            'params' => [
                'id'   => $this->params->idUrl('resource'),
                'type' => $this->params->resourceType(),
            ],
        ];
    }

    public function searchResources(): array
    {
        return [
            'path'   => $this->pathPrefix.'/search/resource/{type}',
            'method' => 'POST',
            'params' => [
                'limit'       => $this->params->limit(),
                'marker'      => $this->params->marker(),
                'sort'        => $this->params->sort(),
                'type'        => $this->params->resourceType(),
                'criteria'    => $this->params->criteria(),
                'contentType' => $this->params->headerContentType(),
            ],
        ];
    }

    public function getResourceTypes(): array
    {
        return [
            'path'   => $this->pathPrefix.'/resource_type',
            'method' => 'GET',
            'params' => [],
        ];
    }

    public function getMetric(): array
    {
        return [
            'path'   => $this->pathPrefix.'/metric/{id}',
            'method' => 'GET',
            'params' => [
                'id' => $this->params->idUrl('metric'),
            ],
        ];
    }

    public function getMetrics(): array
    {
        return [
            'path'   => $this->pathPrefix.'/metric',
            'method' => 'GET',
            'params' => [
                'limit'  => $this->params->limit(),
                'marker' => $this->params->marker(),
                'sort'   => $this->params->sort(),
            ],
        ];
    }

    public function getResourceMetrics(): array
    {
        return [
            'path'   => $this->pathPrefix.'/resource/generic/{resourceId}/metric',
            'method' => 'GET',
            'params' => [
                'resourceId' => $this->params->idUrl('metric'),
            ],
        ];
    }

    public function getResourceMetric(): array
    {
        return [
            'path'   => $this->pathPrefix.'/resource/{type}/{resourceId}/metric/{metric}',
            'method' => 'GET',
            'params' => [
                'resourceId' => $this->params->idUrl('resource'),
                'metric'     => $this->params->idUrl('metric'),
                'type'       => $this->params->resourceType(),
            ],
        ];
    }

    public function getResourceMetricMeasures(): array
    {
        return [
            'path'   => $this->pathPrefix.'/resource/{type}/{resourceId}/metric/{metric}/measures',
            'method' => 'GET',
            'params' => [
                'resourceId'  => $this->params->idUrl('resource'),
                'metric'      => $this->params->idUrl('metric'),
                'type'        => $this->params->resourceType(),
                'granularity' => $this->params->granularity(),
                'aggregation' => $this->params->aggregation(),
                'start'       => $this->params->measureStart(),
                'stop'        => $this->params->measureStop(),
            ],
        ];
    }
}
