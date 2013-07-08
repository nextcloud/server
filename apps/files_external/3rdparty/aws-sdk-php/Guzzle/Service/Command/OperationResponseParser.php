<?php

namespace Guzzle\Service\Command;

use Guzzle\Http\Message\Response;
use Guzzle\Service\Command\LocationVisitor\VisitorFlyweight;
use Guzzle\Service\Command\LocationVisitor\Response\ResponseVisitorInterface;
use Guzzle\Service\Description\Parameter;
use Guzzle\Service\Description\OperationInterface;
use Guzzle\Service\Description\Operation;
use Guzzle\Service\Exception\ResponseClassException;
use Guzzle\Service\Resource\Model;

/**
 * Response parser that attempts to marshal responses into an associative array based on models in a service description
 */
class OperationResponseParser extends DefaultResponseParser
{
    /** @var VisitorFlyweight $factory Visitor factory */
    protected $factory;

    /** @var self */
    protected static $instance;

    /**
     * @return self
     * @codeCoverageIgnore
     */
    public static function getInstance()
    {
        if (!static::$instance) {
            static::$instance = new static(VisitorFlyweight::getInstance());
        }

        return static::$instance;
    }

    /**
     * @param VisitorFlyweight $factory Factory to use when creating visitors
     */
    public function __construct(VisitorFlyweight $factory)
    {
        $this->factory = $factory;
    }

    /**
     * Add a location visitor to the command
     *
     * @param string                   $location Location to associate with the visitor
     * @param ResponseVisitorInterface $visitor  Visitor to attach
     *
     * @return self
     */
    public function addVisitor($location, ResponseVisitorInterface $visitor)
    {
        $this->factory->addResponseVisitor($location, $visitor);

        return $this;
    }

    protected function handleParsing(CommandInterface $command, Response $response, $contentType)
    {
        $operation = $command->getOperation();
        $type = $operation->getResponseType();
        $model = null;

        if ($type == OperationInterface::TYPE_MODEL) {
            $model = $operation->getServiceDescription()->getModel($operation->getResponseClass());
        } elseif ($type == OperationInterface::TYPE_CLASS) {
            $responseClassInterface = __NAMESPACE__ . '\ResponseClassInterface';
            $className = $operation->getResponseClass();
            if (!class_exists($className)) {
                throw new ResponseClassException("{$className} does not exist");
            } elseif (!method_exists($className, 'fromCommand')) {
                throw new ResponseClassException("{$className} must implement {$responseClassInterface}");
            }
            return $className::fromCommand($command);
        }

        if (!$model) {
            // Return basic processing if the responseType is not model or the model cannot be found
            return parent::handleParsing($command, $response, $contentType);
        } elseif ($command[AbstractCommand::RESPONSE_PROCESSING] != AbstractCommand::TYPE_MODEL) {
            // Returns a model with no visiting if the command response processing is not model
            return new Model(parent::handleParsing($command, $response, $contentType), $model);
        } else {
            return new Model($this->visitResult($model, $command, $response), $model);
        }
    }

    /**
     * Perform transformations on the result array
     *
     * @param Parameter        $model    Model that defines the structure
     * @param CommandInterface $command  Command that performed the operation
     * @param Response         $response Response received
     *
     * @return array Returns the array of result data
     */
    protected function visitResult(Parameter $model, CommandInterface $command, Response $response)
    {
        $foundVisitors = $result = array();
        $props = $model->getProperties();

        foreach ($props as $schema) {
            if ($location = $schema->getLocation()) {
                // Trigger the before method on the first found visitor of this type
                if (!isset($foundVisitors[$location])) {
                    $foundVisitors[$location] = $this->factory->getResponseVisitor($location);
                    $foundVisitors[$location]->before($command, $result);
                }
            }
        }

        // Visit additional properties when it is an actual schema
        if ($additional = $model->getAdditionalProperties()) {
            if ($additional instanceof Parameter) {
                // Only visit when a location is specified
                if ($location = $additional->getLocation()) {
                    if (!isset($foundVisitors[$location])) {
                        $foundVisitors[$location] = $this->factory->getResponseVisitor($location);
                        $foundVisitors[$location]->before($command, $result);
                    }
                    // Only traverse if an array was parsed from the before() visitors
                    if (is_array($result)) {
                        // Find each additional property
                        foreach (array_keys($result) as $key) {
                            // Check if the model actually knows this property. If so, then it is not additional
                            if (!$model->getProperty($key)) {
                                // Set the name to the key so that we can parse it with each visitor
                                $additional->setName($key);
                                $foundVisitors[$location]->visit($command, $response, $additional, $result);
                            }
                        }
                        // Reset the additionalProperties name to null
                        $additional->setName(null);
                    }
                }
            }
        }

        // Apply the parameter value with the location visitor
        foreach ($props as $schema) {
            if ($location = $schema->getLocation()) {
                $foundVisitors[$location]->visit($command, $response, $schema, $result);
            }
        }

        // Call the after() method of each found visitor
        foreach ($foundVisitors as $visitor) {
            $visitor->after($command);
        }

        return $result;
    }
}
