<?php
namespace Aws;

use Psr\Http\Message\UriInterface;
use GuzzleHttp\Promise\PromiseInterface;

/**
 * Represents an AWS client.
 */
interface AwsClientInterface
{
    /**
     * Creates and executes a command for an operation by name.
     *
     * Suffixing an operation name with "Async" will return a
     * promise that can be used to execute commands asynchronously.
     *
     * @param string $name      Name of the command to execute.
     * @param array  $arguments Arguments to pass to the getCommand method.
     *
     * @return ResultInterface
     * @throws \Exception
     */
    public function __call($name, array $arguments);

    /**
     * Create a command for an operation name.
     *
     * Special keys may be set on the command to control how it behaves,
     * including:
     *
     * - @http: Associative array of transfer specific options to apply to the
     *   request that is serialized for this command. Available keys include
     *   "proxy", "verify", "timeout", "connect_timeout", "debug", "delay", and
     *   "headers".
     *
     * @param string $name Name of the operation to use in the command
     * @param array  $args Arguments to pass to the command
     *
     * @return CommandInterface
     * @throws \InvalidArgumentException if no command can be found by name
     */
    public function getCommand($name, array $args = []);

    /**
     * Execute a single command.
     *
     * @param CommandInterface $command Command to execute
     *
     * @return ResultInterface
     * @throws \Exception
     */
    public function execute(CommandInterface $command);

    /**
     * Execute a command asynchronously.
     *
     * @param CommandInterface $command Command to execute
     *
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function executeAsync(CommandInterface $command);

    /**
     * Returns a promise that is fulfilled with an
     * {@see \Aws\Credentials\CredentialsInterface} object.
     *
     * If you need the credentials synchronously, then call the wait() method
     * on the returned promise.
     *
     * @return PromiseInterface
     */
    public function getCredentials();

    /**
     * Get the region to which the client is configured to send requests.
     *
     * @return string
     */
    public function getRegion();

    /**
     * Gets the default endpoint, or base URL, used by the client.
     *
     * @return UriInterface
     */
    public function getEndpoint();

    /**
     * Get the service description associated with the client.
     *
     * @return \Aws\Api\Service
     */
    public function getApi();

    /**
     * Get a client configuration value.
     *
     * @param string|null $option The option to retrieve. Pass null to retrieve
     *                            all options.
     * @return mixed|null
     */
    public function getConfig($option = null);

    /**
     * Get the handler list used to transfer commands.
     *
     * This list can be modified to add middleware or to change the underlying
     * handler used to send HTTP requests.
     *
     * @return HandlerList
     */
    public function getHandlerList();

    /**
     * Get a resource iterator for the specified operation.
     *
     * @param string $name Name of the iterator to retrieve.
     * @param array  $args Command arguments to use with each command.
     *
     * @return \Iterator
     * @throws \UnexpectedValueException if the iterator config is invalid.
     */
    public function getIterator($name, array $args = []);

    /**
     * Get a result paginator for the specified operation.
     *
     * @param string $name   Name of the operation used for iterator
     * @param array  $args   Command args to be used with each command
     *
     * @return \Aws\ResultPaginator
     * @throws \UnexpectedValueException if the iterator config is invalid.
     */
    public function getPaginator($name, array $args = []);

    /**
     * Wait until a resource is in a particular state.
     *
     * @param string|callable $name Name of the waiter that defines the wait
     *                              configuration and conditions.
     * @param array  $args          Args to be used with each command executed
     *                              by the waiter. Waiter configuration options
     *                              can be provided in an associative array in
     *                              the @waiter key.
     * @return void
     * @throws \UnexpectedValueException if the waiter is invalid.
     */
    public function waitUntil($name, array $args = []);

    /**
     * Get a waiter that waits until a resource is in a particular state.
     *
     * Retrieving a waiter can be useful when you wish to wait asynchronously:
     *
     *     $waiter = $client->getWaiter('foo', ['bar' => 'baz']);
     *     $waiter->promise()->then(function () { echo 'Done!'; });
     *
     * @param string|callable $name Name of the waiter that defines the wait
     *                              configuration and conditions.
     * @param array  $args          Args to be used with each command executed
     *                              by the waiter. Waiter configuration options
     *                              can be provided in an associative array in
     *                              the @waiter key.
     * @return \Aws\Waiter
     * @throws \UnexpectedValueException if the waiter is invalid.
     */
    public function getWaiter($name, array $args = []);
}
