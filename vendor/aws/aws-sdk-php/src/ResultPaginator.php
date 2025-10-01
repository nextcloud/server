<?php
namespace Aws;

use GuzzleHttp\Promise;

/**
 * Iterator that yields each page of results of a pageable operation.
 */
class ResultPaginator implements \Iterator
{
    /** @var AwsClientInterface Client performing operations. */
    private $client;

    /** @var string Name of the operation being paginated. */
    private $operation;

    /** @var array Args for the operation. */
    private $args;

    /** @var array Configuration for the paginator. */
    private $config;

    /** @var Result Most recent result from the client. */
    private $result;

    /** @var string|array Next token to use for pagination. */
    private $nextToken;

    /** @var int Number of operations/requests performed. */
    private $requestCount = 0;

    /**
     * @param AwsClientInterface $client
     * @param string             $operation
     * @param array              $args
     * @param array              $config
     */
    public function __construct(
        AwsClientInterface $client,
        $operation,
        array $args,
        array $config
    ) {
        $this->client = $client;
        $this->operation = $operation;
        $this->args = $args;
        $this->config = $config;
        MetricsBuilder::appendMetricsCaptureMiddleware(
            $this->client->getHandlerList(),
            MetricsBuilder::PAGINATOR
        );
    }

    /**
     * Runs a paginator asynchronously and uses a callback to handle results.
     *
     * The callback should have the signature: function (Aws\Result $result).
     * A non-null return value from the callback will be yielded by the
     * promise. This means that you can return promises from the callback that
     * will need to be resolved before continuing iteration over the remaining
     * items, essentially merging in other promises to the iteration. The last
     * non-null value returned by the callback will be the result that fulfills
     * the promise to any downstream promises.
     *
     * @param callable $handleResult Callback for handling each page of results.
     *                               The callback accepts the result that was
     *                               yielded as a single argument. If the
     *                               callback returns a promise, the promise
     *                               will be merged into the coroutine.
     *
     * @return Promise\Promise
     */
    public function each(callable $handleResult)
    {
        return Promise\Coroutine::of(function () use ($handleResult) {
            $nextToken = null;
            do {
                $command = $this->createNextCommand($this->args, $nextToken);
                $result = (yield $this->client->executeAsync($command));
                $nextToken = $this->determineNextToken($result);
                $retVal = $handleResult($result);
                if ($retVal !== null) {
                    yield Promise\Create::promiseFor($retVal);
                }
            } while ($nextToken);
        });
    }

    /**
     * Returns an iterator that iterates over the values of applying a JMESPath
     * search to each result yielded by the iterator as a flat sequence.
     *
     * @param string $expression JMESPath expression to apply to each result.
     *
     * @return \Iterator
     */
    public function search($expression)
    {
        // Apply JMESPath expression on each result, but as a flat sequence.
        return flatmap($this, function (Result $result) use ($expression) {
            return (array) $result->search($expression);
        });
    }

    /**
     * @return Result
     */
    #[\ReturnTypeWillChange]
    public function current()
    {
        return $this->valid() ? $this->result : false;
    }

    /**
     * @return mixed
     */
    #[\ReturnTypeWillChange]
    public function key()
    {
        return $this->valid() ? $this->requestCount - 1 : null;
    }

    /**
     * @return void
     */
    #[\ReturnTypeWillChange]
    public function next()
    {
        $this->result = null;
    }

    /**
     * @return bool
     */
    #[\ReturnTypeWillChange]
    public function valid()
    {
        if ($this->result) {
            return true;
        }

        if ($this->nextToken || !$this->requestCount) {
            //Forward/backward paging can result in a case where the last page's nextforwardtoken
            //is the same as the one that came before it.  This can cause an infinite loop.
            $hasBidirectionalPaging = $this->config['output_token'] === 'nextForwardToken';
            if ($hasBidirectionalPaging && $this->nextToken) {
                $tokenKey = $this->config['input_token'];
                $previousToken = $this->nextToken[$tokenKey];
            }

            $this->result = $this->client->execute(
                $this->createNextCommand($this->args, $this->nextToken)
            );

            $this->nextToken = $this->determineNextToken($this->result);

            if (isset($previousToken)
                && $previousToken === $this->nextToken[$tokenKey]
            ) {
                return false;
            }

            $this->requestCount++;
            return true;
        }

        return false;
    }

    /**
     * @return void
     */
    #[\ReturnTypeWillChange]
    public function rewind()
    {
        $this->requestCount = 0;
        $this->nextToken = null;
        $this->result = null;
    }

    private function createNextCommand(array $args, ?array $nextToken = null)
    {
        return $this->client->getCommand($this->operation, array_merge($args, ($nextToken ?: [])));
    }

    private function determineNextToken(Result $result)
    {
        if (!$this->config['output_token']) {
            return null;
        }

        if ($this->config['more_results']
            && !$result->search($this->config['more_results'])
        ) {
            return null;
        }

        $nextToken = is_scalar($this->config['output_token'])
            ? [$this->config['input_token'] => $this->config['output_token']]
            : array_combine($this->config['input_token'], $this->config['output_token']);

        return array_filter(array_map(function ($outputToken) use ($result) {
            return $result->search($outputToken);
        }, $nextToken));
    }
}
