<?php
namespace Aws\Api;

/**
 * Represents an API operation.
 */
class Operation extends AbstractModel
{
    private $input;
    private $output;
    private $errors;
    private $staticContextParams = [];
    private $contextParams;
    private $operationContextParams = [];

    public function __construct(array $definition, ShapeMap $shapeMap)
    {
        $definition['type'] = 'structure';

        if (!isset($definition['http']['method'])) {
            $definition['http']['method'] = 'POST';
        }

        if (!isset($definition['http']['requestUri'])) {
            $definition['http']['requestUri'] = '/';
        }

        if (isset($definition['staticContextParams'])) {
            $this->staticContextParams = $definition['staticContextParams'];
        }

        if (isset($definition['operationContextParams'])) {
            $this->operationContextParams = $definition['operationContextParams'];
        }

        parent::__construct($definition, $shapeMap);
        $this->contextParams = $this->setContextParams();
    }

    /**
     * Returns an associative array of the HTTP attribute of the operation:
     *
     * - method: HTTP method of the operation
     * - requestUri: URI of the request (can include URI template placeholders)
     *
     * @return array
     */
    public function getHttp()
    {
        return $this->definition['http'];
    }

    /**
     * Get the input shape of the operation.
     *
     * @return StructureShape
     */
    public function getInput()
    {
        if (!$this->input) {
            if ($input = $this['input']) {
                $this->input = $this->shapeFor($input);
            } else {
                $this->input = new StructureShape([], $this->shapeMap);
            }
        }

        return $this->input;
    }

    /**
     * Get the output shape of the operation.
     *
     * @return StructureShape
     */
    public function getOutput()
    {
        if (!$this->output) {
            if ($output = $this['output']) {
                $this->output = $this->shapeFor($output);
            } else {
                $this->output = new StructureShape([], $this->shapeMap);
            }
        }

        return $this->output;
    }

    /**
     * Get an array of operation error shapes.
     *
     * @return Shape[]
     */
    public function getErrors()
    {
        if ($this->errors === null) {
            if ($errors = $this['errors']) {
                foreach ($errors as $key => $error) {
                    $errors[$key] = $this->shapeFor($error);
                }
                $this->errors = $errors;
            } else {
                $this->errors = [];
            }
        }

        return $this->errors;
    }

    /**
     * Gets static modeled static values used for
     * endpoint resolution.
     *
     * @return array
     */
    public function getStaticContextParams()
    {
        return $this->staticContextParams;
    }

    /**
     * Gets definition of modeled dynamic values used
     * for endpoint resolution
     *
     * @return array
     */
    public function getContextParams()
    {
        return $this->contextParams;
    }

    /**
     * Gets definition of modeled dynamic values used
     * for endpoint resolution
     *
     * @return array
     */
    public function getOperationContextParams(): array
    {
        return $this->operationContextParams;
    }

    private function setContextParams()
    {
        $members = $this->getInput()->getMembers();
        $contextParams = [];

        foreach($members as $name => $shape) {
            if (!empty($contextParam = $shape->getContextParam())) {
                $contextParams[$contextParam['name']] = [
                    'shape' => $name,
                    'type' => $shape->getType()
                ];
            }
        }
        return $contextParams;
    }
}
