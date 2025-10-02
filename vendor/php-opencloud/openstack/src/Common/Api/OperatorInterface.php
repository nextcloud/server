<?php

declare(strict_types=1);

namespace OpenStack\Common\Api;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Promise\PromiseInterface;
use OpenStack\Common\Resource\ResourceInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * An operator is any resource or service that can invoke and send REST operations. In other words, it
 * is any class that can send requests and receive responses with a HTTP client. To do this
 * it needs two things: a {@see ClientInterface} for handling HTTP transactions and an {@see ApiInterface}
 * for handling how operations are created.
 */
interface OperatorInterface
{
    /**
     * @param ClientInterface $client The HTTP client responsible for handling HTTP transactions
     * @param ApiInterface    $api    The data API class that dictates how REST operations are structured
     */
    public function __construct(ClientInterface $client, ApiInterface $api);

    /**
     * A convenience method that assembles an operation and sends it to the remote API.
     *
     * @param array $definition The data that dictates how the operation works
     * @param array $userValues The user-defined values that populate the request
     */
    public function execute(array $definition, array $userValues = []): ResponseInterface;

    /**
     * A convenience method that assembles an operation and asynchronously sends it to the remote API.
     *
     * @param array $definition The data that dictates how the operation works
     * @param array $userValues The user-defined values that populate the request
     */
    public function executeAsync(array $definition, array $userValues = []): PromiseInterface;

    /**
     * Retrieves a populated Operation according to the definition and values provided. A
     * HTTP client is also injected into the object to allow it to communicate with the remote API.
     *
     * @param array $definition The data that dictates how the operation works
     */
    public function getOperation(array $definition): Operation;

    /**
     * @param string $class the name of the model class
     * @param mixed  $data  either a {@see ResponseInterface} or data array that will populate the newly
     *                      created model class
     */
    public function model(string $class, $data = null): ResourceInterface;
}
