<?php

namespace Guzzle\Service\Builder;

use Guzzle\Common\AbstractHasDispatcher;
use Guzzle\Service\ClientInterface;
use Guzzle\Service\Exception\ServiceBuilderException;
use Guzzle\Service\Exception\ServiceNotFoundException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * {@inheritdoc}
 *
 * Clients and data can be set, retrieved, and removed by accessing the service builder like an associative array.
 */
class ServiceBuilder extends AbstractHasDispatcher implements ServiceBuilderInterface, \ArrayAccess, \Serializable
{
    /** @var array Service builder configuration data */
    protected $builderConfig = array();

    /** @var array Instantiated client objects */
    protected $clients = array();

    /** @var ServiceBuilderLoader Cached instance of the service builder loader */
    protected static $cachedFactory;

    /** @var array Plugins to attach to each client created by the service builder */
    protected $plugins = array();

    /**
     * Create a new ServiceBuilder using configuration data sourced from an
     * array, .js|.json or .php file.
     *
     * @param array|string $config           The full path to an .json|.js or .php file, or an associative array
     * @param array        $globalParameters Array of global parameters to pass to every service as it is instantiated.
     *
     * @return ServiceBuilderInterface
     * @throws ServiceBuilderException if a file cannot be opened
     * @throws ServiceNotFoundException when trying to extend a missing client
     */
    public static function factory($config = null, array $globalParameters = array())
    {
        // @codeCoverageIgnoreStart
        if (!static::$cachedFactory) {
            static::$cachedFactory = new ServiceBuilderLoader();
        }
        // @codeCoverageIgnoreEnd

        return self::$cachedFactory->load($config, $globalParameters);
    }

    /**
     * @param array $serviceBuilderConfig Service configuration settings:
     *     - name: Name of the service
     *     - class: Client class to instantiate using a factory method
     *     - params: array of key value pair configuration settings for the builder
     */
    public function __construct(array $serviceBuilderConfig = array())
    {
        $this->builderConfig = $serviceBuilderConfig;
    }

    public static function getAllEvents()
    {
        return array('service_builder.create_client');
    }

    public function unserialize($serialized)
    {
        $this->builderConfig = json_decode($serialized, true);
    }

    public function serialize()
    {
        return json_encode($this->builderConfig);
    }

    /**
     * Attach a plugin to every client created by the builder
     *
     * @param EventSubscriberInterface $plugin Plugin to attach to each client
     *
     * @return self
     */
    public function addGlobalPlugin(EventSubscriberInterface $plugin)
    {
        $this->plugins[] = $plugin;

        return $this;
    }

    /**
     * Get data from the service builder without triggering the building of a service
     *
     * @param string $name Name of the service to retrieve
     *
     * @return array|null
     */
    public function getData($name)
    {
        return isset($this->builderConfig[$name]) ? $this->builderConfig[$name] : null;
    }

    public function get($name, $throwAway = false)
    {
        if (!isset($this->builderConfig[$name])) {

            // Check to see if arbitrary data is being referenced
            if (isset($this->clients[$name])) {
                return $this->clients[$name];
            }

            // Check aliases and return a match if found
            foreach ($this->builderConfig as $actualName => $config) {
                if (isset($config['alias']) && $config['alias'] == $name) {
                    return $this->get($actualName, $throwAway);
                }
            }
            throw new ServiceNotFoundException('No service is registered as ' . $name);
        }

        if (!$throwAway && isset($this->clients[$name])) {
            return $this->clients[$name];
        }

        $builder =& $this->builderConfig[$name];

        // Convert references to the actual client
        foreach ($builder['params'] as &$v) {
            if (is_string($v) && substr($v, 0, 1) == '{' && substr($v, -1) == '}') {
                $v = $this->get(trim($v, '{} '));
            }
        }

        // Get the configured parameters and merge in any parameters provided for throw-away clients
        $config = $builder['params'];
        if (is_array($throwAway)) {
            $config = $throwAway + $config;
        }

        $client = $builder['class']::factory($config);

        if (!$throwAway) {
            $this->clients[$name] = $client;
        }

        if ($client instanceof ClientInterface) {
            foreach ($this->plugins as $plugin) {
                $client->addSubscriber($plugin);
            }
            // Dispatch an event letting listeners know a client was created
            $this->dispatch('service_builder.create_client', array('client' => $client));
        }

        return $client;
    }

    public function set($key, $service)
    {
        if (is_array($service) && isset($service['class']) && isset($service['params'])) {
            $this->builderConfig[$key] = $service;
        } else {
            $this->clients[$key] = $service;
        }

        return $this;
    }

    public function offsetSet($offset, $value)
    {
        $this->set($offset, $value);
    }

    public function offsetUnset($offset)
    {
        unset($this->builderConfig[$offset]);
        unset($this->clients[$offset]);
    }

    public function offsetExists($offset)
    {
        return isset($this->builderConfig[$offset]) || isset($this->clients[$offset]);
    }

    public function offsetGet($offset)
    {
        return $this->get($offset);
    }
}
