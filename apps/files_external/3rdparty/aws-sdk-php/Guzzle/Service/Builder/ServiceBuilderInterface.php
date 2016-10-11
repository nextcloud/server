<?php

namespace Guzzle\Service\Builder;

use Guzzle\Service\Exception\ServiceNotFoundException;

/**
 * Service builder used to store and build clients or arbitrary data. Client configuration data can be supplied to tell
 * the service builder how to create and cache {@see \Guzzle\Service\ClientInterface} objects. Arbitrary data can be
 * supplied and accessed from a service builder. Arbitrary data and other clients can be referenced by name in client
 * configuration arrays to make them input for building other clients (e.g. "{key}").
 */
interface ServiceBuilderInterface
{
    /**
     * Get a ClientInterface object or arbitrary data from the service builder
     *
     * @param string     $name      Name of the registered service or data to retrieve
     * @param bool|array $throwAway Only pertains to retrieving client objects built using a configuration array.
     *                              Set to TRUE to not store the client for later retrieval from the ServiceBuilder.
     *                              If an array is specified, that data will overwrite the configured params of the
     *                              client if the client implements {@see \Guzzle\Common\FromConfigInterface} and will
     *                              not store the client for later retrieval.
     *
     * @return \Guzzle\Service\ClientInterface|mixed
     * @throws ServiceNotFoundException when a client or data cannot be found by the given name
     */
    public function get($name, $throwAway = false);

    /**
     * Register a service or arbitrary data by name with the service builder
     *
     * @param string $key     Name of the client or data to register
     * @param mixed  $service Client configuration array or arbitrary data to register. The client configuration array
     *                        must include a 'class' (string) and 'params' (array) key.
     *
     * @return ServiceBuilderInterface
     */
    public function set($key, $service);
}
