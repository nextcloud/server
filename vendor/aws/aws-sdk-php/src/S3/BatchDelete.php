<?php
namespace Aws\S3;

use Aws\AwsClientInterface;
use Aws\S3\Exception\DeleteMultipleObjectsException;
use GuzzleHttp\Promise;
use GuzzleHttp\Promise\PromisorInterface;
use GuzzleHttp\Promise\PromiseInterface;

/**
 * Efficiently deletes many objects from a single Amazon S3 bucket using an
 * iterator that yields keys. Deletes are made using the DeleteObjects API
 * operation.
 *
 *     $s3 = new Aws\S3\Client([
 *         'region' => 'us-west-2',
 *         'version' => 'latest'
 *     ]);
 *
 *     $listObjectsParams = ['Bucket' => 'foo', 'Prefix' => 'starts/with/'];
 *     $delete = Aws\S3\BatchDelete::fromListObjects($s3, $listObjectsParams);
 *     // Asynchronously delete
 *     $promise = $delete->promise();
 *     // Force synchronous completion
 *     $delete->delete();
 *
 * When using one of the batch delete creational static methods, you can supply
 * an associative array of options:
 *
 * - before: Function invoked before executing a command. The function is
 *   passed the command that is about to be executed. This can be useful
 *   for logging, adding custom request headers, etc.
 * - batch_size: The size of each delete batch. Defaults to 1000.
 *
 * @link http://docs.aws.amazon.com/AmazonS3/latest/API/multiobjectdeleteapi.html
 */
class BatchDelete implements PromisorInterface
{
    private $bucket;
    /** @var AwsClientInterface */
    private $client;
    /** @var callable */
    private $before;
    /** @var PromiseInterface */
    private $cachedPromise;
    /** @var callable */
    private $promiseCreator;
    private $batchSize = 1000;
    private $queue = [];

    /**
     * Creates a BatchDelete object from all of the paginated results of a
     * ListObjects operation. Each result that is returned by the ListObjects
     * operation will be deleted.
     *
     * @param AwsClientInterface $client            AWS Client to use.
     * @param array              $listObjectsParams ListObjects API parameters
     * @param array              $options           BatchDelete options.
     *
     * @return BatchDelete
     */
    public static function fromListObjects(
        AwsClientInterface $client,
        array $listObjectsParams,
        array $options = []
    ) {
        $iter = $client->getPaginator('ListObjects', $listObjectsParams);
        $bucket = $listObjectsParams['Bucket'];
        $fn = function (BatchDelete $that) use ($iter) {
            return $iter->each(function ($result) use ($that) {
                $promises = [];
                if (is_array($result['Contents'])) {
                    foreach ($result['Contents'] as $object) {
                        if ($promise = $that->enqueue($object)) {
                            $promises[] = $promise;
                        }
                    }
                }
                return $promises ? Promise\Utils::all($promises) : null;
            });
        };

        return new self($client, $bucket, $fn, $options);
    }

    /**
     * Creates a BatchDelete object from an iterator that yields results.
     *
     * @param AwsClientInterface $client  AWS Client to use to execute commands
     * @param string             $bucket  Bucket where the objects are stored
     * @param \Iterator          $iter    Iterator that yields assoc arrays
     * @param array              $options BatchDelete options
     *
     * @return BatchDelete
     */
    public static function fromIterator(
        AwsClientInterface $client,
        $bucket,
        \Iterator $iter,
        array $options = []
    ) {
        $fn = function (BatchDelete $that) use ($iter) {
            return Promise\Coroutine::of(function () use ($that, $iter) {
                foreach ($iter as $obj) {
                    if ($promise = $that->enqueue($obj)) {
                        yield $promise;
                    }
                }
            });
        };

        return new self($client, $bucket, $fn, $options);
    }

    /**
     * @return PromiseInterface
     */
    public function promise(): PromiseInterface
    {
        if (!$this->cachedPromise) {
            $this->cachedPromise = $this->createPromise();
        }

        return $this->cachedPromise;
    }

    /**
     * Synchronously deletes all of the objects.
     *
     * @throws DeleteMultipleObjectsException on error.
     */
    public function delete()
    {
        $this->promise()->wait();
    }

    /**
     * @param AwsClientInterface $client    Client used to transfer the requests
     * @param string             $bucket    Bucket to delete from.
     * @param callable           $promiseFn Creates a promise.
     * @param array              $options   Hash of options used with the batch
     *
     * @throws \InvalidArgumentException if the provided batch_size is <= 0
     */
    private function __construct(
        AwsClientInterface $client,
        $bucket,
        callable $promiseFn,
        array $options = []
    ) {
        $this->client = $client;
        $this->bucket = $bucket;
        $this->promiseCreator = $promiseFn;

        if (isset($options['before'])) {
            if (!is_callable($options['before'])) {
                throw new \InvalidArgumentException('before must be callable');
            }
            $this->before = $options['before'];
        }

        if (isset($options['batch_size'])) {
            if ($options['batch_size'] <= 0) {
                throw new \InvalidArgumentException('batch_size is not > 0');
            }
            $this->batchSize = min($options['batch_size'], 1000);
        }
    }

    private function enqueue(array $obj)
    {
        $this->queue[] = $obj;
        return count($this->queue) >= $this->batchSize
            ? $this->flushQueue()
            : null;
    }

    private function flushQueue()
    {
        static $validKeys = ['Key' => true, 'VersionId' => true];

        if (count($this->queue) === 0) {
            return null;
        }

        $batch = [];
        while ($obj = array_shift($this->queue)) {
            $batch[] = array_intersect_key($obj, $validKeys);
        }

        $command = $this->client->getCommand('DeleteObjects', [
            'Bucket' => $this->bucket,
            'Delete' => ['Objects' => $batch]
        ]);

        if ($this->before) {
            call_user_func($this->before, $command);
        }

        return $this->client->executeAsync($command)
            ->then(function ($result) {
                if (!empty($result['Errors'])) {
                    throw new DeleteMultipleObjectsException(
                        $result['Deleted'] ?: [],
                        $result['Errors']
                    );
                }
                return $result;
            });
    }

    /**
     * Returns a promise that will clean up any references when it completes.
     *
     * @return PromiseInterface
     */
    private function createPromise()
    {
        // Create the promise
        $promise = call_user_func($this->promiseCreator, $this);
        $this->promiseCreator = null;

        // Cleans up the promise state and references.
        $cleanup = function () {
            $this->before = $this->client = $this->queue = null;
        };

        // When done, ensure cleanup and that any remaining are processed.
        return $promise->then(
            function () use ($cleanup)  {
                return Promise\Create::promiseFor($this->flushQueue())
                    ->then($cleanup);
            },
            function ($reason) use ($cleanup)  {
                $cleanup();
                return Promise\Create::rejectionFor($reason);
            }
        );
    }
}
