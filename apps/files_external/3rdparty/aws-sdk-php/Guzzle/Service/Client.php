<?php

namespace Guzzle\Service;

use Guzzle\Common\Collection;
use Guzzle\Common\Exception\InvalidArgumentException;
use Guzzle\Common\Exception\BadMethodCallException;
use Guzzle\Common\Version;
use Guzzle\Inflection\InflectorInterface;
use Guzzle\Inflection\Inflector;
use Guzzle\Http\Client as HttpClient;
use Guzzle\Http\Exception\MultiTransferException;
use Guzzle\Service\Exception\CommandTransferException;
use Guzzle\Http\Message\RequestInterface;
use Guzzle\Service\Command\CommandInterface;
use Guzzle\Service\Command\Factory\CompositeFactory;
use Guzzle\Service\Command\Factory\FactoryInterface as CommandFactoryInterface;
use Guzzle\Service\Resource\ResourceIteratorClassFactory;
use Guzzle\Service\Resource\ResourceIteratorFactoryInterface;
use Guzzle\Service\Description\ServiceDescriptionInterface;

/**
 * Client object for executing commands on a web service.
 */
class Client extends HttpClient implements ClientInterface
{
    const COMMAND_PARAMS = 'command.params';

    /** @var ServiceDescriptionInterface Description of the service and possible commands */
    protected $serviceDescription;

    /** @var CommandFactoryInterface */
    protected $commandFactory;

    /** @var ResourceIteratorFactoryInterface */
    protected $resourceIteratorFactory;

    /** @var InflectorInterface Inflector associated with the service/client */
    protected $inflector;

    /**
     * Basic factory method to create a new client. Extend this method in subclasses to build more complex clients.
     *
     * @param array|Collection $config Configuration data
     *
     * @return Client
     */
    public static function factory($config = array())
    {
        return new static(isset($config['base_url']) ? $config['base_url'] : null, $config);
    }

    public static function getAllEvents()
    {
        return array_merge(HttpClient::getAllEvents(), array(
            'client.command.create',
            'command.before_prepare',
            'command.after_prepare',
            'command.before_send',
            'command.after_send',
            'command.parse_response'
        ));
    }

    /**
     * Magic method used to retrieve a command
     *
     * @param string $method Name of the command object to instantiate
     * @param array  $args   Arguments to pass to the command
     *
     * @return mixed Returns the result of the command
     * @throws BadMethodCallException when a command is not found
     */
    public function __call($method, $args)
    {
        return $this->getCommand($method, isset($args[0]) ? $args[0] : array())->getResult();
    }

    public function getCommand($name, array $args = array())
    {
        // Add global client options to the command
        if ($options = $this->getConfig(self::COMMAND_PARAMS)) {
            $args += $options;
        }

        if (!($command = $this->getCommandFactory()->factory($name, $args))) {
            throw new InvalidArgumentException("Command was not found matching {$name}");
        }

        $command->setClient($this);
        $this->dispatch('client.command.create', array('client' => $this, 'command' => $command));

        return $command;
    }

    /**
     * Set the command factory used to create commands by name
     *
     * @param CommandFactoryInterface $factory Command factory
     *
     * @return self
     */
    public function setCommandFactory(CommandFactoryInterface $factory)
    {
        $this->commandFactory = $factory;

        return $this;
    }

    /**
     * Set the resource iterator factory associated with the client
     *
     * @param ResourceIteratorFactoryInterface $factory Resource iterator factory
     *
     * @return self
     */
    public function setResourceIteratorFactory(ResourceIteratorFactoryInterface $factory)
    {
        $this->resourceIteratorFactory = $factory;

        return $this;
    }

    public function getIterator($command, array $commandOptions = null, array $iteratorOptions = array())
    {
        if (!($command instanceof CommandInterface)) {
            $command = $this->getCommand($command, $commandOptions ?: array());
        }

        return $this->getResourceIteratorFactory()->build($command, $iteratorOptions);
    }

    public function execute($command)
    {
        if ($command instanceof CommandInterface) {
            $this->send($this->prepareCommand($command));
            $this->dispatch('command.after_send', array('command' => $command));
            return $command->getResult();
        } elseif (is_array($command) || $command instanceof \Traversable) {
            return $this->executeMultiple($command);
        } else {
            throw new InvalidArgumentException('Command must be a command or array of commands');
        }
    }

    public function setDescription(ServiceDescriptionInterface $service)
    {
        $this->serviceDescription = $service;

        if ($this->getCommandFactory() && $this->getCommandFactory() instanceof CompositeFactory) {
            $this->commandFactory->add(new Command\Factory\ServiceDescriptionFactory($service));
        }

        // If a baseUrl was set on the description, then update the client
        if ($baseUrl = $service->getBaseUrl()) {
            $this->setBaseUrl($baseUrl);
        }

        return $this;
    }

    public function getDescription()
    {
        return $this->serviceDescription;
    }

    /**
     * Set the inflector used with the client
     *
     * @param InflectorInterface $inflector Inflection object
     *
     * @return self
     */
    public function setInflector(InflectorInterface $inflector)
    {
        $this->inflector = $inflector;

        return $this;
    }

    /**
     * Get the inflector used with the client
     *
     * @return self
     */
    public function getInflector()
    {
        if (!$this->inflector) {
            $this->inflector = Inflector::getDefault();
        }

        return $this->inflector;
    }

    /**
     * Prepare a command for sending and get the RequestInterface object created by the command
     *
     * @param CommandInterface $command Command to prepare
     *
     * @return RequestInterface
     */
    protected function prepareCommand(CommandInterface $command)
    {
        // Set the client and prepare the command
        $request = $command->setClient($this)->prepare();
        // Set the state to new if the command was previously executed
        $request->setState(RequestInterface::STATE_NEW);
        $this->dispatch('command.before_send', array('command' => $command));

        return $request;
    }

    /**
     * Execute multiple commands in parallel
     *
     * @param array|Traversable $commands Array of CommandInterface objects to execute
     *
     * @return array Returns an array of the executed commands
     * @throws Exception\CommandTransferException
     */
    protected function executeMultiple($commands)
    {
        $requests = array();
        $commandRequests = new \SplObjectStorage();

        foreach ($commands as $command) {
            $request = $this->prepareCommand($command);
            $commandRequests[$request] = $command;
            $requests[] = $request;
        }

        try {
            $this->send($requests);
            foreach ($commands as $command) {
                $this->dispatch('command.after_send', array('command' => $command));
            }
            return $commands;
        } catch (MultiTransferException $failureException) {
            // Throw a CommandTransferException using the successful and failed commands
            $e = CommandTransferException::fromMultiTransferException($failureException);

            // Remove failed requests from the successful requests array and add to the failures array
            foreach ($failureException->getFailedRequests() as $request) {
                if (isset($commandRequests[$request])) {
                    $e->addFailedCommand($commandRequests[$request]);
                    unset($commandRequests[$request]);
                }
            }

            // Always emit the command after_send events for successful commands
            foreach ($commandRequests as $success) {
                $e->addSuccessfulCommand($commandRequests[$success]);
                $this->dispatch('command.after_send', array('command' => $commandRequests[$success]));
            }

            throw $e;
        }
    }

    protected function getResourceIteratorFactory()
    {
        if (!$this->resourceIteratorFactory) {
            // Build the default resource iterator factory if one is not set
            $clientClass = get_class($this);
            $prefix = substr($clientClass, 0, strrpos($clientClass, '\\'));
            $this->resourceIteratorFactory = new ResourceIteratorClassFactory(array(
                "{$prefix}\\Iterator",
                "{$prefix}\\Model"
            ));
        }

        return $this->resourceIteratorFactory;
    }

    /**
     * Get the command factory associated with the client
     *
     * @return CommandFactoryInterface
     */
    protected function getCommandFactory()
    {
        if (!$this->commandFactory) {
            $this->commandFactory = CompositeFactory::getDefaultChain($this);
        }

        return $this->commandFactory;
    }

    /**
     * @deprecated
     * @codeCoverageIgnore
     */
    public function enableMagicMethods($isEnabled)
    {
        Version::warn(__METHOD__ . ' is deprecated');
    }
}
