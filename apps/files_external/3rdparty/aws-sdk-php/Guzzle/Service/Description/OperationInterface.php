<?php

namespace Guzzle\Service\Description;

use Guzzle\Common\ToArrayInterface;

/**
 * Interface defining data objects that hold the information of an API operation
 */
interface OperationInterface extends ToArrayInterface
{
    const TYPE_PRIMITIVE = 'primitive';
    const TYPE_CLASS = 'class';
    const TYPE_DOCUMENTATION = 'documentation';
    const TYPE_MODEL = 'model';

    /**
     * Get the service description that the operation belongs to
     *
     * @return ServiceDescriptionInterface|null
     */
    public function getServiceDescription();

    /**
     * Set the service description that the operation belongs to
     *
     * @param ServiceDescriptionInterface $description Service description
     *
     * @return self
     */
    public function setServiceDescription(ServiceDescriptionInterface $description);

    /**
     * Get the params of the operation
     *
     * @return array
     */
    public function getParams();

    /**
     * Returns an array of parameter names
     *
     * @return array
     */
    public function getParamNames();

    /**
     * Check if the operation has a specific parameter by name
     *
     * @param string $name Name of the param
     *
     * @return bool
     */
    public function hasParam($name);

    /**
     * Get a single parameter of the operation
     *
     * @param string $param Parameter to retrieve by name
     *
     * @return Parameter|null
     */
    public function getParam($param);

    /**
     * Get the HTTP method of the operation
     *
     * @return string|null
     */
    public function getHttpMethod();

    /**
     * Get the concrete operation class that implements this operation
     *
     * @return string
     */
    public function getClass();

    /**
     * Get the name of the operation
     *
     * @return string|null
     */
    public function getName();

    /**
     * Get a short summary of what the operation does
     *
     * @return string|null
     */
    public function getSummary();

    /**
     * Get a longer text field to explain the behavior of the operation
     *
     * @return string|null
     */
    public function getNotes();

    /**
     * Get the documentation URL of the operation
     *
     * @return string|null
     */
    public function getDocumentationUrl();

    /**
     * Get what is returned from the method. Can be a primitive, class name, or model. For example, the responseClass
     * could be 'array', which would inherently use a responseType of 'primitive'. Using a class name would set a
     * responseType of 'class'. Specifying a model by ID will use a responseType of 'model'.
     *
     * @return string|null
     */
    public function getResponseClass();

    /**
     * Get information about how the response is unmarshalled: One of 'primitive', 'class', 'model', or 'documentation'
     *
     * @return string
     */
    public function getResponseType();

    /**
     * Get notes about the response of the operation
     *
     * @return string|null
     */
    public function getResponseNotes();

    /**
     * Get whether or not the operation is deprecated
     *
     * @return bool
     */
    public function getDeprecated();

    /**
     * Get the URI that will be merged into the generated request
     *
     * @return string
     */
    public function getUri();

    /**
     * Get the errors that could be encountered when executing the operation
     *
     * @return array
     */
    public function getErrorResponses();

    /**
     * Get extra data from the operation
     *
     * @param string $name Name of the data point to retrieve
     *
     * @return mixed|null
     */
    public function getData($name);
}
