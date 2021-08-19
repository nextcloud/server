<?php

declare(strict_types=1);

namespace OpenStack\Common\Api;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Promise\Promise;
use GuzzleHttp\Promise\PromiseInterface;
use OpenStack\Common\Resource\ResourceInterface;
use OpenStack\Common\Transport\RequestSerializer;
use OpenStack\Common\Transport\Utils;
use Psr\Http\Message\ResponseInterface;

trait OperatorTrait
{
    /** @var ClientInterface */
    protected $client;

    /** @var ApiInterface */
    protected $api;

    /**
     * {@inheritdoc}
     */
    public function __construct(ClientInterface $client, ApiInterface $api)
    {
        $this->client = $client;
        $this->api    = $api;
    }

    /**
     * Magic method for dictating how objects are rendered when var_dump is called.
     * For the benefit of users, extremely verbose and heavy properties (such as HTTP clients) are
     * removed to provide easier access to normal state, such as resource attributes.
     *
     * @codeCoverageIgnore
     *
     * @return array
     */
    public function __debugInfo()
    {
        $excludedVars = ['client', 'errorBuilder', 'api'];

        $output = [];

        foreach (get_object_vars($this) as $key => $val) {
            if (!in_array($key, $excludedVars)) {
                $output[$key] = $val;
            }
        }

        return $output;
    }

    /**
     * Magic method which intercepts async calls, finds the sequential version, and wraps it in a
     * {@see Promise} object. In order for this to happen, the called methods need to be in the
     * following format: `createAsync`, where `create` is the sequential method being wrapped.
     *
     * @param $methodName the name of the method being invoked
     * @param $args       the arguments to be passed to the sequential method
     *
     * @throws \RuntimeException If method does not exist
     *
     * @return Promise
     */
    public function __call($methodName, $args)
    {
        $e = function ($name) {
            return new \RuntimeException(sprintf('%s::%s is not defined', get_class($this), $name));
        };

        if ('Async' === substr($methodName, -5)) {
            $realMethod = substr($methodName, 0, -5);
            if (!method_exists($this, $realMethod)) {
                throw $e($realMethod);
            }

            $promise = new Promise(
                function () use (&$promise, $realMethod, $args) {
                    $value = call_user_func_array([$this, $realMethod], $args);
                    $promise->resolve($value);
                }
            );

            return $promise;
        }

        throw $e($methodName);
    }

    /**
     * {@inheritdoc}
     */
    public function getOperation(array $definition): Operation
    {
        return new Operation($definition);
    }

    /**
     * @return mixed
     *
     * @throws \Exception
     */
    protected function sendRequest(Operation $operation, array $userValues = [], bool $async = false)
    {
        $operation->validate($userValues);

        $options = (new RequestSerializer())->serializeOptions($operation, $userValues);
        $method  = $async ? 'requestAsync' : 'request';

        $uri     = Utils::uri_template($operation->getPath(), $userValues);

        if (array_key_exists('requestOptions', $userValues)) {
            $options += $userValues['requestOptions'];
        }

        return $this->client->$method($operation->getMethod(), $uri, $options);
    }

    /**
     * {@inheritdoc}
     */
    public function execute(array $definition, array $userValues = []): ResponseInterface
    {
        return $this->sendRequest($this->getOperation($definition), $userValues);
    }

    /**
     * {@inheritdoc}
     */
    public function executeAsync(array $definition, array $userValues = []): PromiseInterface
    {
        return $this->sendRequest($this->getOperation($definition), $userValues, true);
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
