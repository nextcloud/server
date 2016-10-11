<?php

namespace Guzzle\Service;

use Guzzle\Common\FromConfigInterface;
use Guzzle\Common\Exception\InvalidArgumentException;
use Guzzle\Http\ClientInterface as HttpClientInterface;
use Guzzle\Service\Exception\CommandTransferException;
use Guzzle\Service\Command\CommandInterface;
use Guzzle\Service\Description\ServiceDescriptionInterface;
use Guzzle\Service\Resource\ResourceIteratorInterface;

/**
 * Client interface for executing commands on a web service.
 */
interface ClientInterface extends HttpClientInterface, FromConfigInterface
{
    /**
     * Get a command by name. First, the client will see if it has a service description and if the service description
     * defines a command by the supplied name. If no dynamic command is found, the client will look for a concrete
     * command class exists matching the name supplied. If neither are found, an InvalidArgumentException is thrown.
     *
     * @param string $name Name of the command to retrieve
     * @param array  $args Arguments to pass to the command
     *
     * @return CommandInterface
     * @throws InvalidArgumentException if no command can be found by name
     */
    public function getCommand($name, array $args = array());

    /**
     * Execute one or more commands
     *
     * @param CommandInterface|array|Traversable $command Command, array of commands or Traversable object containing commands to execute
     *
     * @return mixed Returns the result of the executed command or an array of commands if executing multiple commands
     * @throws InvalidArgumentException if an invalid command is passed
     * @throws CommandTransferException if an exception is encountered when transferring multiple commands
     */
    public function execute($command);

    /**
     * Set the service description of the client
     *
     * @param ServiceDescriptionInterface $service Service description
     *
     * @return ClientInterface
     */
    public function setDescription(ServiceDescriptionInterface $service);

    /**
     * Get the service description of the client
     *
     * @return ServiceDescriptionInterface|null
     */
    public function getDescription();

    /**
     * Get a resource iterator from the client.
     *
     * @param string|CommandInterface $command         Command class or command name.
     * @param array                   $commandOptions  Command options used when creating commands.
     * @param array                   $iteratorOptions Iterator options passed to the iterator when it is instantiated.
     *
     * @return ResourceIteratorInterface
     */
    public function getIterator($command, array $commandOptions = null, array $iteratorOptions = array());
}
