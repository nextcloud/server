<?php

declare(strict_types=1);

namespace OpenStack\Common\Resource;

use OpenStack\Common\Transport\Utils;

class Iterator
{
    private $requestFn;
    private $resourceFn;
    private $limit;
    private $count;
    private $resourcesKey;
    private $markerKey;
    private $mapFn;
    private $currentMarker;

    public function __construct(array $options, callable $requestFn, callable $resourceFn)
    {
        $this->limit = isset($options['limit']) ? $options['limit'] : false;
        $this->count = 0;

        if (isset($options['resourcesKey'])) {
            $this->resourcesKey = $options['resourcesKey'];
        }

        if (isset($options['markerKey'])) {
            $this->markerKey = $options['markerKey'];
        }

        if (isset($options['mapFn']) && is_callable($options['mapFn'])) {
            $this->mapFn = $options['mapFn'];
        }

        $this->requestFn  = $requestFn;
        $this->resourceFn = $resourceFn;
    }

    private function fetchResources()
    {
        if ($this->shouldNotSendAnotherRequest()) {
            return false;
        }

        $response = call_user_func($this->requestFn, $this->currentMarker);

        $json = Utils::flattenJson(Utils::jsonDecode($response), $this->resourcesKey);

        if (204 === $response->getStatusCode() || empty($json)) {
            return false;
        }

        return $json;
    }

    private function assembleResource(array $data)
    {
        $resource = call_user_func($this->resourceFn, $data);

        // Invoke user-provided fn if provided
        if ($this->mapFn) {
            call_user_func_array($this->mapFn, [&$resource]);
        }

        // Update marker if operation supports it
        if ($this->markerKey) {
            $this->currentMarker = $resource->{$this->markerKey};
        }

        return $resource;
    }

    private function totalReached()
    {
        return $this->limit && $this->count >= $this->limit;
    }

    private function shouldNotSendAnotherRequest()
    {
        return $this->totalReached() || ($this->count > 0 && !$this->markerKey);
    }

    public function __invoke()
    {
        while ($resources = $this->fetchResources()) {
            foreach ($resources as $resourceData) {
                if ($this->totalReached()) {
                    break;
                }

                ++$this->count;

                yield $this->assembleResource($resourceData);
            }
        }
    }
}
