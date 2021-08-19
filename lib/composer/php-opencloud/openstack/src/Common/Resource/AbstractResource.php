<?php

declare(strict_types=1);

namespace OpenStack\Common\Resource;

use OpenStack\Common\Transport\Serializable;
use OpenStack\Common\Transport\Utils;
use Psr\Http\Message\ResponseInterface;

/**
 * Represents a top-level abstraction of a remote API resource. Usually a resource represents a discrete
 * entity such as a Server, Container, Load Balancer. Apart from a representation of state, a resource can
 * also execute RESTFul operations on itself (updating, deleting, listing) or on other models.
 */
abstract class AbstractResource implements ResourceInterface, Serializable
{
    /**
     * The JSON key that indicates how the API nests singular resources. For example, when
     * performing a GET, it could respond with ``{"server": {"id": "12345"}}``. In this case,
     * "server" is the resource key, since the essential state of the server is nested inside.
     *
     * @var string
     */
    protected $resourceKey;

    /**
     * An array of aliases that will be checked when the resource is being populated. For example,.
     *
     * 'FOO_BAR' => 'fooBar'
     *
     * will extract FOO_BAR from the response, and save it as 'fooBar' in the resource.
     *
     * @var array
     */
    protected $aliases = [];

    /**
     * Populates the current resource from a response object.
     *
     * @return AbstractResource
     */
    public function populateFromResponse(ResponseInterface $response)
    {
        if (0 === strpos($response->getHeaderLine('Content-Type'), 'application/json')) {
            $json = Utils::jsonDecode($response);
            if (!empty($json)) {
                $this->populateFromArray(Utils::flattenJson($json, $this->resourceKey));
            }
        }

        return $this;
    }

    /**
     * Populates the current resource from a data array.
     *
     * @return mixed|void
     */
    public function populateFromArray(array $array)
    {
        $aliases = $this->getAliases();

        foreach ($array as $key => $val) {
            $alias = $aliases[$key] ?? false;

            if ($alias instanceof Alias) {
                $key = $alias->propertyName;
                $val = $alias->getValue($this, $val);
            }

            if (property_exists($this, $key)) {
                $this->{$key} = $val;
            }
        }

        return $this;
    }

    /**
     * Constructs alias objects.
     *
     * @return Alias[]
     */
    protected function getAliases(): array
    {
        $aliases = [];

        foreach ((array) $this->aliases as $alias => $property) {
            $aliases[$alias] = new Alias($property);
        }

        return $aliases;
    }

    /**
     * Internal method which retrieves the values of provided keys.
     *
     * @return array
     */
    protected function getAttrs(array $keys)
    {
        $output = [];

        foreach ($keys as $key) {
            if (property_exists($this, $key) && $this->$key !== null) {
                $output[$key] = $this->$key;
            }
        }

        return $output;
    }

    public function model(string $class, $data = null): ResourceInterface
    {
        $model = new $class();

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

    public function serialize(): \stdClass
    {
        $output = new \stdClass();

        foreach ((new \ReflectionClass($this))->getProperties(\ReflectionProperty::IS_PUBLIC) as $property) {
            $name = $property->getName();
            $val  = $this->{$name};

            $fn = function ($val) {
                if ($val instanceof Serializable) {
                    return $val->serialize();
                } elseif ($val instanceof \DateTimeImmutable) {
                    return $val->format('c');
                } else {
                    return $val;
                }
            };

            if (is_array($val)) {
                foreach ($val as $sk => $sv) {
                    $val[$sk] = $fn($sv);
                }
            }

            $output->{$name} = $fn($val);
        }

        return $output;
    }
}
