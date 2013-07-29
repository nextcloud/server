<?php

namespace Guzzle\Service\Command;

use Guzzle\Common\Collection;
use Guzzle\Common\Exception\InvalidArgumentException;
use Guzzle\Http\Message\Response;
use Guzzle\Http\Message\RequestInterface;
use Guzzle\Service\Exception\CommandException;
use Guzzle\Service\Description\OperationInterface;
use Guzzle\Service\ClientInterface;
use Guzzle\Common\ToArrayInterface;

/**
 * A command object that contains parameters that can be modified and accessed like an array and turned into an array
 */
interface CommandInterface extends \ArrayAccess, ToArrayInterface
{
    /**
     * Get the short form name of the command
     *
     * @return string
     */
    public function getName();

    /**
     * Get the API operation information about the command
     *
     * @return OperationInterface
     */
    public function getOperation();

    /**
     * Execute the command and return the result
     *
     * @return mixed Returns the result of {@see CommandInterface::execute}
     * @throws CommandException if a client has not been associated with the command
     */
    public function execute();

    /**
     * Get the client object that will execute the command
     *
     * @return ClientInterface|null
     */
    public function getClient();

    /**
     * Set the client object that will execute the command
     *
     * @param ClientInterface $client The client object that will execute the command
     *
     * @return self
     */
    public function setClient(ClientInterface $client);

    /**
     * Get the request object associated with the command
     *
     * @return RequestInterface
     * @throws CommandException if the command has not been executed
     */
    public function getRequest();

    /**
     * Get the response object associated with the command
     *
     * @return Response
     * @throws CommandException if the command has not been executed
     */
    public function getResponse();

    /**
     * Get the result of the command
     *
     * @return Response By default, commands return a Response object unless overridden in a subclass
     * @throws CommandException if the command has not been executed
     */
    public function getResult();

    /**
     * Set the result of the command
     *
     * @param mixed $result Result to set
     *
     * @return self
     */
    public function setResult($result);

    /**
     * Returns TRUE if the command has been prepared for executing
     *
     * @return bool
     */
    public function isPrepared();

    /**
     * Returns TRUE if the command has been executed
     *
     * @return bool
     */
    public function isExecuted();

    /**
     * Prepare the command for executing and create a request object.
     *
     * @return RequestInterface Returns the generated request
     * @throws CommandException if a client object has not been set previously or in the prepare()
     */
    public function prepare();

    /**
     * Get the object that manages the request headers that will be set on any outbound requests from the command
     *
     * @return Collection
     */
    public function getRequestHeaders();

    /**
     * Specify a callable to execute when the command completes
     *
     * @param mixed $callable Callable to execute when the command completes. The callable must accept a
     *                        {@see CommandInterface} object as the only argument.
     * @return self
     * @throws InvalidArgumentException
     */
    public function setOnComplete($callable);
}
