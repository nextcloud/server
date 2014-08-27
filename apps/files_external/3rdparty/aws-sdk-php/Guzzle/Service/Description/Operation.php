<?php

namespace Guzzle\Service\Description;

use Guzzle\Common\Exception\InvalidArgumentException;

/**
 * Data object holding the information of an API command
 */
class Operation implements OperationInterface
{
    /** @var string Default command class to use when none is specified */
    const DEFAULT_COMMAND_CLASS = 'Guzzle\\Service\\Command\\OperationCommand';

    /** @var array Hashmap of properties that can be specified. Represented as a hash to speed up constructor. */
    protected static $properties = array(
        'name' => true, 'httpMethod' => true, 'uri' => true, 'class' => true, 'responseClass' => true,
        'responseType' => true, 'responseNotes' => true, 'notes' => true, 'summary' => true, 'documentationUrl' => true,
        'deprecated' => true, 'data' => true, 'parameters' => true, 'additionalParameters' => true,
        'errorResponses' => true
    );

    /** @var array Parameters */
    protected $parameters = array();

    /** @var Parameter Additional parameters schema */
    protected $additionalParameters;

    /** @var string Name of the command */
    protected $name;

    /** @var string HTTP method */
    protected $httpMethod;

    /** @var string This is a short summary of what the operation does */
    protected $summary;

    /** @var string A longer text field to explain the behavior of the operation. */
    protected $notes;

    /** @var string Reference URL providing more information about the operation */
    protected $documentationUrl;

    /** @var string HTTP URI of the command */
    protected $uri;

    /** @var string Class of the command object */
    protected $class;

    /** @var string This is what is returned from the method */
    protected $responseClass;

    /** @var string Type information about the response */
    protected $responseType;

    /** @var string Information about the response returned by the operation */
    protected $responseNotes;

    /** @var bool Whether or not the command is deprecated */
    protected $deprecated;

    /** @var array Array of errors that could occur when running the command */
    protected $errorResponses;

    /** @var ServiceDescriptionInterface */
    protected $description;

    /** @var array Extra operation information */
    protected $data;

    /**
     * Builds an Operation object using an array of configuration data:
     * - name:               (string) Name of the command
     * - httpMethod:         (string) HTTP method of the operation
     * - uri:                (string) URI template that can create a relative or absolute URL
     * - class:              (string) Concrete class that implements this command
     * - parameters:         (array) Associative array of parameters for the command. {@see Parameter} for information.
     * - summary:            (string) This is a short summary of what the operation does
     * - notes:              (string) A longer text field to explain the behavior of the operation.
     * - documentationUrl:   (string) Reference URL providing more information about the operation
     * - responseClass:      (string) This is what is returned from the method. Can be a primitive, PSR-0 compliant
     *                       class name, or model.
     * - responseNotes:      (string) Information about the response returned by the operation
     * - responseType:       (string) One of 'primitive', 'class', 'model', or 'documentation'. If not specified, this
     *                       value will be automatically inferred based on whether or not there is a model matching the
     *                       name, if a matching PSR-0 compliant class name is found, or set to 'primitive' by default.
     * - deprecated:         (bool) Set to true if this is a deprecated command
     * - errorResponses:     (array) Errors that could occur when executing the command. Array of hashes, each with a
     *                       'code' (the HTTP response code), 'phrase' (response reason phrase or description of the
     *                       error), and 'class' (a custom exception class that would be thrown if the error is
     *                       encountered).
     * - data:               (array) Any extra data that might be used to help build or serialize the operation
     * - additionalParameters: (null|array) Parameter schema to use when an option is passed to the operation that is
     *                                      not in the schema
     *
     * @param array                       $config      Array of configuration data
     * @param ServiceDescriptionInterface $description Service description used to resolve models if $ref tags are found
     */
    public function __construct(array $config = array(), ServiceDescriptionInterface $description = null)
    {
        $this->description = $description;

        // Get the intersection of the available properties and properties set on the operation
        foreach (array_intersect_key($config, self::$properties) as $key => $value) {
            $this->{$key} = $value;
        }

        $this->class = $this->class ?: self::DEFAULT_COMMAND_CLASS;
        $this->deprecated = (bool) $this->deprecated;
        $this->errorResponses = $this->errorResponses ?: array();
        $this->data = $this->data ?: array();

        if (!$this->responseClass) {
            $this->responseClass = 'array';
            $this->responseType = 'primitive';
        } elseif ($this->responseType) {
            // Set the response type to perform validation
            $this->setResponseType($this->responseType);
        } else {
            // A response class was set and no response type was set, so guess what the type is
            $this->inferResponseType();
        }

        // Parameters need special handling when adding
        if ($this->parameters) {
            foreach ($this->parameters as $name => $param) {
                if ($param instanceof Parameter) {
                    $param->setName($name)->setParent($this);
                } elseif (is_array($param)) {
                    $param['name'] = $name;
                    $this->addParam(new Parameter($param, $this->description));
                }
            }
        }

        if ($this->additionalParameters) {
            if ($this->additionalParameters instanceof Parameter) {
                $this->additionalParameters->setParent($this);
            } elseif (is_array($this->additionalParameters)) {
                $this->setadditionalParameters(new Parameter($this->additionalParameters, $this->description));
            }
        }
    }

    public function toArray()
    {
        $result = array();
        // Grab valid properties and filter out values that weren't set
        foreach (array_keys(self::$properties) as $check) {
            if ($value = $this->{$check}) {
                $result[$check] = $value;
            }
        }
        // Remove the name property
        unset($result['name']);
        // Parameters need to be converted to arrays
        $result['parameters'] = array();
        foreach ($this->parameters as $key => $param) {
            $result['parameters'][$key] = $param->toArray();
        }
        // Additional parameters need to be cast to an array
        if ($this->additionalParameters instanceof Parameter) {
            $result['additionalParameters'] = $this->additionalParameters->toArray();
        }

        return $result;
    }

    public function getServiceDescription()
    {
        return $this->description;
    }

    public function setServiceDescription(ServiceDescriptionInterface $description)
    {
        $this->description = $description;

        return $this;
    }

    public function getParams()
    {
        return $this->parameters;
    }

    public function getParamNames()
    {
        return array_keys($this->parameters);
    }

    public function hasParam($name)
    {
        return isset($this->parameters[$name]);
    }

    public function getParam($param)
    {
        return isset($this->parameters[$param]) ? $this->parameters[$param] : null;
    }

    /**
     * Add a parameter to the command
     *
     * @param Parameter $param Parameter to add
     *
     * @return self
     */
    public function addParam(Parameter $param)
    {
        $this->parameters[$param->getName()] = $param;
        $param->setParent($this);

        return $this;
    }

    /**
     * Remove a parameter from the command
     *
     * @param string $name Name of the parameter to remove
     *
     * @return self
     */
    public function removeParam($name)
    {
        unset($this->parameters[$name]);

        return $this;
    }

    public function getHttpMethod()
    {
        return $this->httpMethod;
    }

    /**
     * Set the HTTP method of the command
     *
     * @param string $httpMethod Method to set
     *
     * @return self
     */
    public function setHttpMethod($httpMethod)
    {
        $this->httpMethod = $httpMethod;

        return $this;
    }

    public function getClass()
    {
        return $this->class;
    }

    /**
     * Set the concrete class of the command
     *
     * @param string $className Concrete class name
     *
     * @return self
     */
    public function setClass($className)
    {
        $this->class = $className;

        return $this;
    }

    public function getName()
    {
        return $this->name;
    }

    /**
     * Set the name of the command
     *
     * @param string $name Name of the command
     *
     * @return self
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    public function getSummary()
    {
        return $this->summary;
    }

    /**
     * Set a short summary of what the operation does
     *
     * @param string $summary Short summary of the operation
     *
     * @return self
     */
    public function setSummary($summary)
    {
        $this->summary = $summary;

        return $this;
    }

    public function getNotes()
    {
        return $this->notes;
    }

    /**
     * Set a longer text field to explain the behavior of the operation.
     *
     * @param string $notes Notes on the operation
     *
     * @return self
     */
    public function setNotes($notes)
    {
        $this->notes = $notes;

        return $this;
    }

    public function getDocumentationUrl()
    {
        return $this->documentationUrl;
    }

    /**
     * Set the URL pointing to additional documentation on the command
     *
     * @param string $docUrl Documentation URL
     *
     * @return self
     */
    public function setDocumentationUrl($docUrl)
    {
        $this->documentationUrl = $docUrl;

        return $this;
    }

    public function getResponseClass()
    {
        return $this->responseClass;
    }

    /**
     * Set what is returned from the method. Can be a primitive, class name, or model. For example: 'array',
     * 'Guzzle\\Foo\\Baz', or 'MyModelName' (to reference a model by ID).
     *
     * @param string $responseClass Type of response
     *
     * @return self
     */
    public function setResponseClass($responseClass)
    {
        $this->responseClass = $responseClass;
        $this->inferResponseType();

        return $this;
    }

    public function getResponseType()
    {
        return $this->responseType;
    }

    /**
     * Set qualifying information about the responseClass. One of 'primitive', 'class', 'model', or 'documentation'
     *
     * @param string $responseType Response type information
     *
     * @return self
     * @throws InvalidArgumentException
     */
    public function setResponseType($responseType)
    {
        static $types = array(
            self::TYPE_PRIMITIVE => true,
            self::TYPE_CLASS => true,
            self::TYPE_MODEL => true,
            self::TYPE_DOCUMENTATION => true
        );
        if (!isset($types[$responseType])) {
            throw new InvalidArgumentException('responseType must be one of ' . implode(', ', array_keys($types)));
        }

        $this->responseType = $responseType;

        return $this;
    }

    public function getResponseNotes()
    {
        return $this->responseNotes;
    }

    /**
     * Set notes about the response of the operation
     *
     * @param string $notes Response notes
     *
     * @return self
     */
    public function setResponseNotes($notes)
    {
        $this->responseNotes = $notes;

        return $this;
    }

    public function getDeprecated()
    {
        return $this->deprecated;
    }

    /**
     * Set whether or not the command is deprecated
     *
     * @param bool $isDeprecated Set to true to mark as deprecated
     *
     * @return self
     */
    public function setDeprecated($isDeprecated)
    {
        $this->deprecated = $isDeprecated;

        return $this;
    }

    public function getUri()
    {
        return $this->uri;
    }

    /**
     * Set the URI template of the command
     *
     * @param string $uri URI template to set
     *
     * @return self
     */
    public function setUri($uri)
    {
        $this->uri = $uri;

        return $this;
    }

    public function getErrorResponses()
    {
        return $this->errorResponses;
    }

    /**
     * Add an error to the command
     *
     * @param string $code   HTTP response code
     * @param string $reason HTTP response reason phrase or information about the error
     * @param string $class  Exception class associated with the error
     *
     * @return self
     */
    public function addErrorResponse($code, $reason, $class)
    {
        $this->errorResponses[] = array('code' => $code, 'reason' => $reason, 'class' => $class);

        return $this;
    }

    /**
     * Set all of the error responses of the operation
     *
     * @param array $errorResponses Hash of error name to a hash containing a code, reason, class
     *
     * @return self
     */
    public function setErrorResponses(array $errorResponses)
    {
        $this->errorResponses = $errorResponses;

        return $this;
    }

    public function getData($name)
    {
        return isset($this->data[$name]) ? $this->data[$name] : null;
    }

    /**
     * Set a particular data point on the operation
     *
     * @param string $name  Name of the data value
     * @param mixed  $value Value to set
     *
     * @return self
     */
    public function setData($name, $value)
    {
        $this->data[$name] = $value;

        return $this;
    }

    /**
     * Get the additionalParameters of the operation
     *
     * @return Parameter|null
     */
    public function getAdditionalParameters()
    {
        return $this->additionalParameters;
    }

    /**
     * Set the additionalParameters of the operation
     *
     * @param Parameter|null $parameter Parameter to set
     *
     * @return self
     */
    public function setAdditionalParameters($parameter)
    {
        if ($this->additionalParameters = $parameter) {
            $this->additionalParameters->setParent($this);
        }

        return $this;
    }

    /**
     * Infer the response type from the responseClass value
     */
    protected function inferResponseType()
    {
        static $primitives = array('array' => 1, 'boolean' => 1, 'string' => 1, 'integer' => 1, '' => 1);
        if (isset($primitives[$this->responseClass])) {
            $this->responseType = self::TYPE_PRIMITIVE;
        } elseif ($this->description && $this->description->hasModel($this->responseClass)) {
            $this->responseType = self::TYPE_MODEL;
        } else {
            $this->responseType = self::TYPE_CLASS;
        }
    }
}
