<?php

namespace Guzzle\Service\Command;

use Guzzle\Common\Collection;
use Guzzle\Common\Exception\InvalidArgumentException;
use Guzzle\Http\Message\RequestInterface;
use Guzzle\Http\Curl\CurlHandle;
use Guzzle\Service\Client;
use Guzzle\Service\ClientInterface;
use Guzzle\Service\Description\Operation;
use Guzzle\Service\Description\OperationInterface;
use Guzzle\Service\Description\ValidatorInterface;
use Guzzle\Service\Description\SchemaValidator;
use Guzzle\Service\Exception\CommandException;
use Guzzle\Service\Exception\ValidationException;

/**
 * Command object to handle preparing and processing client requests and responses of the requests
 */
abstract class AbstractCommand extends Collection implements CommandInterface
{
    // @deprecated: Option used to specify custom headers to add to the generated request
    const HEADERS_OPTION = 'command.headers';
    // @deprecated: Option used to add an onComplete method to a command
    const ON_COMPLETE = 'command.on_complete';
    // @deprecated: Option used to change the entity body used to store a response
    const RESPONSE_BODY = 'command.response_body';

    // Option used to add request options to the request created by a command
    const REQUEST_OPTIONS = 'command.request_options';
    // command values to not count as additionalParameters
    const HIDDEN_PARAMS = 'command.hidden_params';
    // Option used to disable any pre-sending command validation
    const DISABLE_VALIDATION = 'command.disable_validation';
    // Option used to override how a command result will be formatted
    const RESPONSE_PROCESSING = 'command.response_processing';
    // Different response types that commands can use
    const TYPE_RAW = 'raw';
    const TYPE_MODEL = 'model';
    const TYPE_NO_TRANSLATION = 'no_translation';

    /** @var ClientInterface Client object used to execute the command */
    protected $client;

    /** @var RequestInterface The request object associated with the command */
    protected $request;

    /** @var mixed The result of the command */
    protected $result;

    /** @var OperationInterface API information about the command */
    protected $operation;

    /** @var mixed callable */
    protected $onComplete;

    /** @var ValidatorInterface Validator used to prepare and validate properties against a JSON schema */
    protected $validator;

    /**
     * @param array|Collection   $parameters Collection of parameters to set on the command
     * @param OperationInterface $operation Command definition from description
     */
    public function __construct($parameters = array(), OperationInterface $operation = null)
    {
        parent::__construct($parameters);
        $this->operation = $operation ?: $this->createOperation();
        foreach ($this->operation->getParams() as $name => $arg) {
            $currentValue = $this[$name];
            $configValue = $arg->getValue($currentValue);
            // If default or static values are set, then this should always be updated on the config object
            if ($currentValue !== $configValue) {
                $this[$name] = $configValue;
            }
        }

        $headers = $this[self::HEADERS_OPTION];
        if (!$headers instanceof Collection) {
            $this[self::HEADERS_OPTION] = new Collection((array) $headers);
        }

        // You can set a command.on_complete option in your parameters to set an onComplete callback
        if ($onComplete = $this['command.on_complete']) {
            unset($this['command.on_complete']);
            $this->setOnComplete($onComplete);
        }

        // Set the hidden additional parameters
        if (!$this[self::HIDDEN_PARAMS]) {
            $this[self::HIDDEN_PARAMS] = array(
                self::HEADERS_OPTION,
                self::RESPONSE_PROCESSING,
                self::HIDDEN_PARAMS,
                self::REQUEST_OPTIONS
            );
        }

        $this->init();
    }

    /**
     * Custom clone behavior
     */
    public function __clone()
    {
        $this->request = null;
        $this->result = null;
    }

    /**
     * Execute the command in the same manner as calling a function
     *
     * @return mixed Returns the result of {@see AbstractCommand::execute}
     */
    public function __invoke()
    {
        return $this->execute();
    }

    public function getName()
    {
        return $this->operation->getName();
    }

    /**
     * Get the API command information about the command
     *
     * @return OperationInterface
     */
    public function getOperation()
    {
        return $this->operation;
    }

    public function setOnComplete($callable)
    {
        if (!is_callable($callable)) {
            throw new InvalidArgumentException('The onComplete function must be callable');
        }

        $this->onComplete = $callable;

        return $this;
    }

    public function execute()
    {
        if (!$this->client) {
            throw new CommandException('A client must be associated with the command before it can be executed.');
        }

        return $this->client->execute($this);
    }

    public function getClient()
    {
        return $this->client;
    }

    public function setClient(ClientInterface $client)
    {
        $this->client = $client;

        return $this;
    }

    public function getRequest()
    {
        if (!$this->request) {
            throw new CommandException('The command must be prepared before retrieving the request');
        }

        return $this->request;
    }

    public function getResponse()
    {
        if (!$this->isExecuted()) {
            $this->execute();
        }

        return $this->request->getResponse();
    }

    public function getResult()
    {
        if (!$this->isExecuted()) {
            $this->execute();
        }

        if (null === $this->result) {
            $this->process();
            // Call the onComplete method if one is set
            if ($this->onComplete) {
                call_user_func($this->onComplete, $this);
            }
        }

        return $this->result;
    }

    public function setResult($result)
    {
        $this->result = $result;

        return $this;
    }

    public function isPrepared()
    {
        return $this->request !== null;
    }

    public function isExecuted()
    {
        return $this->request !== null && $this->request->getState() == 'complete';
    }

    public function prepare()
    {
        if (!$this->isPrepared()) {
            if (!$this->client) {
                throw new CommandException('A client must be associated with the command before it can be prepared.');
            }

            // If no response processing value was specified, then attempt to use the highest level of processing
            if (!isset($this[self::RESPONSE_PROCESSING])) {
                $this[self::RESPONSE_PROCESSING] = self::TYPE_MODEL;
            }

            // Notify subscribers of the client that the command is being prepared
            $this->client->dispatch('command.before_prepare', array('command' => $this));

            // Fail on missing required arguments, and change parameters via filters
            $this->validate();
            // Delegate to the subclass that implements the build method
            $this->build();

            // Add custom request headers set on the command
            if ($headers = $this[self::HEADERS_OPTION]) {
                foreach ($headers as $key => $value) {
                    $this->request->setHeader($key, $value);
                }
            }

            // Add any curl options to the request
            if ($options = $this[Client::CURL_OPTIONS]) {
                $this->request->getCurlOptions()->overwriteWith(CurlHandle::parseCurlConfig($options));
            }

            // Set a custom response body
            if ($responseBody = $this[self::RESPONSE_BODY]) {
                $this->request->setResponseBody($responseBody);
            }

            $this->client->dispatch('command.after_prepare', array('command' => $this));
        }

        return $this->request;
    }

    /**
     * Set the validator used to validate and prepare command parameters and nested JSON schemas. If no validator is
     * set, then the command will validate using the default {@see SchemaValidator}.
     *
     * @param ValidatorInterface $validator Validator used to prepare and validate properties against a JSON schema
     *
     * @return self
     */
    public function setValidator(ValidatorInterface $validator)
    {
        $this->validator = $validator;

        return $this;
    }

    public function getRequestHeaders()
    {
        return $this[self::HEADERS_OPTION];
    }

    /**
     * Initialize the command (hook that can be implemented in subclasses)
     */
    protected function init() {}

    /**
     * Create the request object that will carry out the command
     */
    abstract protected function build();

    /**
     * Hook used to create an operation for concrete commands that are not associated with a service description
     *
     * @return OperationInterface
     */
    protected function createOperation()
    {
        return new Operation(array('name' => get_class($this)));
    }

    /**
     * Create the result of the command after the request has been completed.
     * Override this method in subclasses to customize this behavior
     */
    protected function process()
    {
        $this->result = $this[self::RESPONSE_PROCESSING] != self::TYPE_RAW
            ? DefaultResponseParser::getInstance()->parse($this)
            : $this->request->getResponse();
    }

    /**
     * Validate and prepare the command based on the schema and rules defined by the command's Operation object
     *
     * @throws ValidationException when validation errors occur
     */
    protected function validate()
    {
        // Do not perform request validation/transformation if it is disable
        if ($this[self::DISABLE_VALIDATION]) {
            return;
        }

        $errors = array();
        $validator = $this->getValidator();
        foreach ($this->operation->getParams() as $name => $schema) {
            $value = $this[$name];
            if (!$validator->validate($schema, $value)) {
                $errors = array_merge($errors, $validator->getErrors());
            } elseif ($value !== $this[$name]) {
                // Update the config value if it changed and no validation errors were encountered
                $this->data[$name] = $value;
            }
        }

        // Validate additional parameters
        $hidden = $this[self::HIDDEN_PARAMS];

        if ($properties = $this->operation->getAdditionalParameters()) {
            foreach ($this->toArray() as $name => $value) {
                // It's only additional if it isn't defined in the schema
                if (!$this->operation->hasParam($name) && !in_array($name, $hidden)) {
                    // Always set the name so that error messages are useful
                    $properties->setName($name);
                    if (!$validator->validate($properties, $value)) {
                        $errors = array_merge($errors, $validator->getErrors());
                    } elseif ($value !== $this[$name]) {
                        $this->data[$name] = $value;
                    }
                }
            }
        }

        if (!empty($errors)) {
            $e = new ValidationException('Validation errors: ' . implode("\n", $errors));
            $e->setErrors($errors);
            throw $e;
        }
    }

    /**
     * Get the validator used to prepare and validate properties. If no validator has been set on the command, then
     * the default {@see SchemaValidator} will be used.
     *
     * @return ValidatorInterface
     */
    protected function getValidator()
    {
        if (!$this->validator) {
            $this->validator = SchemaValidator::getInstance();
        }

        return $this->validator;
    }

    /**
     * Get array of any validation errors
     * If no validator has been set then return false
     */
    public function getValidationErrors()
    {
        if (!$this->validator) {
            return false;
        }

        return $this->validator->getErrors();
    }
}
