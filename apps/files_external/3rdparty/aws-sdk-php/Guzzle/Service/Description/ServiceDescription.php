<?php

namespace Guzzle\Service\Description;

use Guzzle\Common\Exception\InvalidArgumentException;
use Guzzle\Common\ToArrayInterface;

/**
 * A ServiceDescription stores service information based on a service document
 */
class ServiceDescription implements ServiceDescriptionInterface, ToArrayInterface
{
    /** @var array Array of {@see OperationInterface} objects */
    protected $operations = array();

    /** @var array Array of API models */
    protected $models = array();

    /** @var string Name of the API */
    protected $name;

    /** @var string API version */
    protected $apiVersion;

    /** @var string Summary of the API */
    protected $description;

    /** @var array Any extra API data */
    protected $extraData = array();

    /** @var ServiceDescriptionLoader Factory used in factory method */
    protected static $descriptionLoader;

    /** @var string baseUrl/basePath */
    protected $baseUrl;

    /**
     * {@inheritdoc}
     * @param string|array $config  File to build or array of operation information
     * @param array        $options Service description factory options
     *
     * @return self
     */
    public static function factory($config, array $options = array())
    {
        // @codeCoverageIgnoreStart
        if (!self::$descriptionLoader) {
            self::$descriptionLoader = new ServiceDescriptionLoader();
        }
        // @codeCoverageIgnoreEnd

        return self::$descriptionLoader->load($config, $options);
    }

    /**
     * @param array $config Array of configuration data
     */
    public function __construct(array $config = array())
    {
        $this->fromArray($config);
    }

    public function serialize()
    {
        return json_encode($this->toArray());
    }

    public function unserialize($json)
    {
        $this->operations = array();
        $this->fromArray(json_decode($json, true));
    }

    public function toArray()
    {
        $result = array(
            'name'        => $this->name,
            'apiVersion'  => $this->apiVersion,
            'baseUrl'     => $this->baseUrl,
            'description' => $this->description
        ) + $this->extraData;
        $result['operations'] = array();
        foreach ($this->getOperations() as $name => $operation) {
            $result['operations'][$operation->getName() ?: $name] = $operation->toArray();
        }
        if (!empty($this->models)) {
            $result['models'] = array();
            foreach ($this->models as $id => $model) {
                $result['models'][$id] = $model instanceof Parameter ? $model->toArray(): $model;
            }
        }

        return array_filter($result);
    }

    public function getBaseUrl()
    {
        return $this->baseUrl;
    }

    /**
     * Set the baseUrl of the description
     *
     * @param string $baseUrl Base URL of each operation
     *
     * @return self
     */
    public function setBaseUrl($baseUrl)
    {
        $this->baseUrl = $baseUrl;

        return $this;
    }

    public function getOperations()
    {
        foreach (array_keys($this->operations) as $name) {
            $this->getOperation($name);
        }

        return $this->operations;
    }

    public function hasOperation($name)
    {
        return isset($this->operations[$name]);
    }

    public function getOperation($name)
    {
        // Lazily retrieve and build operations
        if (!isset($this->operations[$name])) {
            return null;
        }

        if (!($this->operations[$name] instanceof Operation)) {
            $this->operations[$name] = new Operation($this->operations[$name], $this);
        }

        return $this->operations[$name];
    }

    /**
     * Add a operation to the service description
     *
     * @param OperationInterface $operation Operation to add
     *
     * @return self
     */
    public function addOperation(OperationInterface $operation)
    {
        $this->operations[$operation->getName()] = $operation->setServiceDescription($this);

        return $this;
    }

    public function getModel($id)
    {
        if (!isset($this->models[$id])) {
            return null;
        }

        if (!($this->models[$id] instanceof Parameter)) {
            $this->models[$id] = new Parameter($this->models[$id] + array('name' => $id), $this);
        }

        return $this->models[$id];
    }

    public function getModels()
    {
        // Ensure all models are converted into parameter objects
        foreach (array_keys($this->models) as $id) {
            $this->getModel($id);
        }

        return $this->models;
    }

    public function hasModel($id)
    {
        return isset($this->models[$id]);
    }

    /**
     * Add a model to the service description
     *
     * @param Parameter $model Model to add
     *
     * @return self
     */
    public function addModel(Parameter $model)
    {
        $this->models[$model->getName()] = $model;

        return $this;
    }

    public function getApiVersion()
    {
        return $this->apiVersion;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getDescription()
    {
        return $this->description;
    }

    public function getData($key)
    {
        return isset($this->extraData[$key]) ? $this->extraData[$key] : null;
    }

    public function setData($key, $value)
    {
        $this->extraData[$key] = $value;

        return $this;
    }

    /**
     * Initialize the state from an array
     *
     * @param array $config Configuration data
     * @throws InvalidArgumentException
     */
    protected function fromArray(array $config)
    {
        // Keep a list of default keys used in service descriptions that is later used to determine extra data keys
        static $defaultKeys = array('name', 'models', 'apiVersion', 'baseUrl', 'description');
        // Pull in the default configuration values
        foreach ($defaultKeys as $key) {
            if (isset($config[$key])) {
                $this->{$key} = $config[$key];
            }
        }

        // Account for the Swagger name for Guzzle's baseUrl
        if (isset($config['basePath'])) {
            $this->baseUrl = $config['basePath'];
        }

        // Ensure that the models and operations properties are always arrays
        $this->models = (array) $this->models;
        $this->operations = (array) $this->operations;

        // We want to add operations differently than adding the other properties
        $defaultKeys[] = 'operations';

        // Create operations for each operation
        if (isset($config['operations'])) {
            foreach ($config['operations'] as $name => $operation) {
                if (!($operation instanceof Operation) && !is_array($operation)) {
                    throw new InvalidArgumentException('Invalid operation in service description: '
                        . gettype($operation));
                }
                $this->operations[$name] = $operation;
            }
        }

        // Get all of the additional properties of the service description and store them in a data array
        foreach (array_diff(array_keys($config), $defaultKeys) as $key) {
            $this->extraData[$key] = $config[$key];
        }
    }
}
