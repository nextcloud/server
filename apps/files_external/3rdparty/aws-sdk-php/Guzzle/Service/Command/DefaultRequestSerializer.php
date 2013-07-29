<?php

namespace Guzzle\Service\Command;

use Guzzle\Http\Message\RequestInterface;
use Guzzle\Http\Url;
use Guzzle\Service\Command\LocationVisitor\Request\RequestVisitorInterface;
use Guzzle\Service\Command\LocationVisitor\VisitorFlyweight;
use Guzzle\Service\Description\OperationInterface;
use Guzzle\Service\Description\Parameter;

/**
 * Default request serializer that transforms command options and operation parameters into a request
 */
class DefaultRequestSerializer implements RequestSerializerInterface
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
        if (!self::$instance) {
            self::$instance = new self(VisitorFlyweight::getInstance());
        }

        return self::$instance;
    }

    /**
     * @param VisitorFlyweight $factory Factory to use when creating visitors
     */
    public function __construct(VisitorFlyweight $factory)
    {
        $this->factory = $factory;
    }

    /**
     * Add a location visitor to the serializer
     *
     * @param string                   $location Location to associate with the visitor
     * @param RequestVisitorInterface  $visitor  Visitor to attach
     *
     * @return self
     */
    public function addVisitor($location, RequestVisitorInterface $visitor)
    {
        $this->factory->addRequestVisitor($location, $visitor);

        return $this;
    }

    public function prepare(CommandInterface $command)
    {
        $request = $this->createRequest($command);
        // Keep an array of visitors found in the operation
        $foundVisitors = array();
        $operation = $command->getOperation();

        // Add arguments to the request using the location attribute
        foreach ($operation->getParams() as $name => $arg) {
            /** @var $arg \Guzzle\Service\Description\Parameter */
            $location = $arg->getLocation();
            // Skip 'uri' locations because they've already been processed
            if ($location && $location != 'uri') {
                // Instantiate visitors as they are detected in the properties
                if (!isset($foundVisitors[$location])) {
                    $foundVisitors[$location] = $this->factory->getRequestVisitor($location);
                }
                // Ensure that a value has been set for this parameter
                $value = $command[$name];
                if ($value !== null) {
                    // Apply the parameter value with the location visitor
                    $foundVisitors[$location]->visit($command, $request, $arg, $value);
                }
            }
        }

        // Serialize additional parameters
        if ($additional = $operation->getAdditionalParameters()) {
            if ($visitor = $this->prepareAdditionalParameters($operation, $command, $request, $additional)) {
                $foundVisitors[$additional->getLocation()] = $visitor;
            }
        }

        // Call the after method on each visitor found in the operation
        foreach ($foundVisitors as $visitor) {
            $visitor->after($command, $request);
        }

        return $request;
    }

    /**
     * Serialize additional parameters
     *
     * @param OperationInterface $operation  Operation that owns the command
     * @param CommandInterface   $command    Command to prepare
     * @param RequestInterface   $request    Request to serialize
     * @param Parameter          $additional Additional parameters
     *
     * @return null|RequestVisitorInterface
     */
    protected function prepareAdditionalParameters(
        OperationInterface $operation,
        CommandInterface $command,
        RequestInterface $request,
        Parameter $additional
    ) {
        if (!($location = $additional->getLocation())) {
            return;
        }

        $visitor = $this->factory->getRequestVisitor($location);
        $hidden = $command[$command::HIDDEN_PARAMS];

        foreach ($command->toArray() as $key => $value) {
            // Ignore values that are null or built-in command options
            if ($value !== null
                && !in_array($key, $hidden)
                && !$operation->hasParam($key)
            ) {
                $additional->setName($key);
                $visitor->visit($command, $request, $additional, $value);
            }
        }

        return $visitor;
    }

    /**
     * Create a request for the command and operation
     *
     * @param CommandInterface $command Command to create a request for
     *
     * @return RequestInterface
     */
    protected function createRequest(CommandInterface $command)
    {
        $operation = $command->getOperation();
        $client = $command->getClient();
        $options = $command[AbstractCommand::REQUEST_OPTIONS] ?: array();

        // If the command does not specify a template, then assume the base URL of the client
        if (!($uri = $operation->getUri())) {
            return $client->createRequest($operation->getHttpMethod(), $client->getBaseUrl(), null, null, $options);
        }

        // Get the path values and use the client config settings
        $variables = array();
        foreach ($operation->getParams() as $name => $arg) {
            if ($arg->getLocation() == 'uri') {
                if (isset($command[$name])) {
                    $variables[$name] = $arg->filter($command[$name]);
                    if (!is_array($variables[$name])) {
                        $variables[$name] = (string) $variables[$name];
                    }
                }
            }
        }

        return $client->createRequest($operation->getHttpMethod(), array($uri, $variables), null, null, $options);
    }
}
