<?php

namespace OpenStack\Common\Resource;

use OpenStack\Common\Api\OperatorInterface;
use OpenStack\Common\Api\OperatorTrait;
use OpenStack\Common\Transport\Utils;
use Psr\Http\Message\ResponseInterface;

abstract class OperatorResource extends AbstractResource implements OperatorInterface
{
    use OperatorTrait;

    const DEFAULT_MARKER_KEY = 'id';

    /**
     * The key that indicates how the API nests resource collections. For example, when
     * performing a GET, it could respond with ``{"servers": [{}, {}]}``. In this case, "servers"
     * is the resources key, since the array of servers is nested inside.
     *
     * @var string
     */
    protected $resourcesKey;

    /**
     * Indicates which attribute of the current resource should be used for pagination markers.
     *
     * @var string
     */
    protected $markerKey;

    /**
     * Will create a new instance of this class with the current HTTP client and API injected in. This
     * is useful when enumerating over a collection since multiple copies of the same resource class
     * are needed.
     */
    public function newInstance(): OperatorResource
    {
        return new static($this->client, $this->api);
    }

    /**
     * @return \GuzzleHttp\Psr7\Uri:null
     */
    protected function getHttpBaseUrl()
    {
        return $this->client->getConfig('base_uri');
    }

    /**
     * @return mixed
     */
    public function executeWithState(array $definition)
    {
        return $this->execute($definition, $this->getAttrs(array_keys($definition['params'])));
    }

    private function getResourcesKey(): string
    {
        $resourcesKey = $this->resourcesKey;

        if (!$resourcesKey) {
            $class        = substr(static::class, strrpos(static::class, '\\') + 1);
            $resourcesKey = strtolower(preg_replace('/([a-z])([A-Z])/', '$1_$2', $class)).'s';
        }

        return $resourcesKey;
    }

    /**
     * {@inheritdoc}
     */
    public function enumerate(array $def, array $userVals = [], callable $mapFn = null): \Generator
    {
        $operation = $this->getOperation($def);

        $requestFn = function ($marker) use ($operation, $userVals) {
            if ($marker) {
                $userVals['marker'] = $marker;
            }

            return $this->sendRequest($operation, $userVals);
        };

        $resourceFn = function (array $data) {
            $resource = $this->newInstance();
            $resource->populateFromArray($data);

            return $resource;
        };

        $opts = [
            'limit'        => isset($userVals['limit']) ? $userVals['limit'] : null,
            'resourcesKey' => $this->getResourcesKey(),
            'markerKey'    => $this->markerKey,
            'mapFn'        => $mapFn,
        ];

        $iterator = new Iterator($opts, $requestFn, $resourceFn);

        return $iterator();
    }

    public function extractMultipleInstances(ResponseInterface $response, string $key = null): array
    {
        $key           = $key ?: $this->getResourcesKey();
        $resourcesData = Utils::jsonDecode($response)[$key];

        $resources = [];

        foreach ($resourcesData as $resourceData) {
            $resources[] = $this->newInstance()->populateFromArray($resourceData);
        }

        return $resources;
    }

    protected function getService()
    {
        $class   = static::class;
        $service = substr($class, 0, strpos($class, 'Models') - 1).'\\Service';

        return new $service($this->client, $this->api);
    }

    /**
     * {@inheritdoc}
     */
    public function model(string $class, $data = null): ResourceInterface
    {
        $model = new $class($this->client, $this->api);

        // @codeCoverageIgnoreStart
        if (!$model instanceof ResourceInterface) {
            throw new \RuntimeException(sprintf('%s does not implement %s', $class, ResourceInterface::class));
        }
        // @codeCoverageIgnoreEnd

        if ($data instanceof ResponseInterface) {
            $model->populateFromResponse($data);
        } elseif (is_array($data)) {
            $model->populateFromArray($data);
        }

        return $model;
    }
}
