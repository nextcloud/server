<?php
namespace Aws\Multipart;

use Aws\AwsClientInterface as Client;
use Aws\CommandInterface;
use Aws\CommandPool;
use Aws\Exception\AwsException;
use Aws\Exception\MultipartUploadException;
use Aws\Result;
use Aws\ResultInterface;
use GuzzleHttp\Promise;
use GuzzleHttp\Promise\PromiseInterface;
use InvalidArgumentException as IAE;
use Psr\Http\Message\RequestInterface;

/**
 * Encapsulates the execution of a multipart upload to S3 or Glacier.
 *
 * @internal
 */
abstract class AbstractUploadManager implements Promise\PromisorInterface
{
    const DEFAULT_CONCURRENCY = 5;

    /** @var array Default values for base multipart configuration */
    private static $defaultConfig = [
        'part_size'           => null,
        'state'               => null,
        'concurrency'         => self::DEFAULT_CONCURRENCY,
        'prepare_data_source' => null,
        'before_initiate'     => null,
        'before_upload'       => null,
        'before_complete'     => null,
        'exception_class'     => MultipartUploadException::class,
    ];

    /** @var Client Client used for the upload. */
    protected $client;

    /** @var array Configuration used to perform the upload. */
    protected $config;

    /** @var array Service-specific information about the upload workflow. */
    protected $info;

    /** @var PromiseInterface Promise that represents the multipart upload. */
    protected $promise;

    /** @var UploadState State used to manage the upload. */
    protected $state;

    /** @var bool Configuration used to indicate if upload progress will be displayed. */
    protected $displayProgress;

    /**
     * @param Client $client
     * @param array  $config
     */
    public function __construct(Client $client, array $config = [])
    {
        $this->client = $client;
        $this->info = $this->loadUploadWorkflowInfo();
        $this->config = $config + self::$defaultConfig;
        $this->state = $this->determineState();

        if (isset($config['display_progress'])
            && is_bool($config['display_progress'])
        ) {
            $this->displayProgress = $config['display_progress'];
        }
    }

    /**
     * Returns the current state of the upload
     *
     * @return UploadState
     */
    public function getState()
    {
        return $this->state;
    }

    /**
     * Upload the source using multipart upload operations.
     *
     * @return Result The result of the CompleteMultipartUpload operation.
     * @throws \LogicException if the upload is already complete or aborted.
     * @throws MultipartUploadException if an upload operation fails.
     */
    public function upload()
    {
        return $this->promise()->wait();
    }

    /**
     * Upload the source asynchronously using multipart upload operations.
     *
     * @return PromiseInterface
     */
    public function promise(): PromiseInterface
    {
        if ($this->promise) {
            return $this->promise;
        }

        return $this->promise = Promise\Coroutine::of(function () {
            // Initiate the upload.
            if ($this->state->isCompleted()) {
                throw new \LogicException('This multipart upload has already '
                    . 'been completed or aborted.'
                );
            }

            if (!$this->state->isInitiated()) {
                // Execute the prepare callback.
                if (is_callable($this->config["prepare_data_source"])) {
                    $this->config["prepare_data_source"]();
                }

                $result = (yield $this->execCommand('initiate', $this->getInitiateParams()));
                $this->state->setUploadId(
                    $this->info['id']['upload_id'],
                    $result[$this->info['id']['upload_id']]
                );
                $this->state->setStatus(UploadState::INITIATED);
            }

            // Create a command pool from a generator that yields UploadPart
            // commands for each upload part.
            $resultHandler = $this->getResultHandler($errors);
            $commands = new CommandPool(
                $this->client,
                $this->getUploadCommands($resultHandler),
                [
                    'concurrency' => $this->config['concurrency'],
                    'before'      => $this->config['before_upload'],
                ]
            );

            // Execute the pool of commands concurrently, and process errors.
            yield $commands->promise();
            if ($errors) {
                throw new $this->config['exception_class']($this->state, $errors);
            }

            // Complete the multipart upload.
            yield $this->execCommand('complete', $this->getCompleteParams());
            $this->state->setStatus(UploadState::COMPLETED);
        })->otherwise($this->buildFailureCatch());
    }

    private function transformException($e)
    {
        // Throw errors from the operations as a specific Multipart error.
        if ($e instanceof AwsException) {
            $e = new $this->config['exception_class']($this->state, $e);
        }
        throw $e;
    }

    private function buildFailureCatch()
    {
        if (interface_exists("Throwable")) {
            return function (\Throwable $e) {
                return $this->transformException($e);
            };
        } else {
            return function (\Exception $e) {
                return $this->transformException($e);
            };
        }
    }

    protected function getConfig()
    {
        return $this->config;
    }

    /**
     * Provides service-specific information about the multipart upload
     * workflow.
     *
     * This array of data should include the keys: 'command', 'id', and 'part_num'.
     *
     * @return array
     */
    abstract protected function loadUploadWorkflowInfo();

    /**
     * Determines the part size to use for upload parts.
     *
     * Examines the provided partSize value and the source to determine the
     * best possible part size.
     *
     * @throws \InvalidArgumentException if the part size is invalid.
     *
     * @return int
     */
    abstract protected function determinePartSize();

    /**
     * Uses information from the Command and Result to determine which part was
     * uploaded and mark it as uploaded in the upload's state.
     *
     * @param CommandInterface $command
     * @param ResultInterface  $result
     */
    abstract protected function handleResult(
        CommandInterface $command,
        ResultInterface $result
    );

    /**
     * Gets the service-specific parameters used to initiate the upload.
     *
     * @return array
     */
    abstract protected function getInitiateParams();

    /**
     * Gets the service-specific parameters used to complete the upload.
     *
     * @return array
     */
    abstract protected function getCompleteParams();

    /**
     * Based on the config and service-specific workflow info, creates a
     * `Promise` for an `UploadState` object.
     */
    private function determineState(): UploadState
    {
        // If the state was provided via config, then just use it.
        if ($this->config['state'] instanceof UploadState) {
            return $this->config['state'];
        }

        // Otherwise, construct a new state from the provided identifiers.
        $required = $this->info['id'];
        $id = [$required['upload_id'] => null];
        unset($required['upload_id']);
        foreach ($required as $key => $param) {
            if (!$this->config[$key]) {
                throw new IAE('You must provide a value for "' . $key . '" in '
                    . 'your config for the MultipartUploader for '
                    . $this->client->getApi()->getServiceFullName() . '.');
            }
            $id[$param] = $this->config[$key];
        }
        $state = new UploadState($id, $this->config);
        $state->setPartSize($this->determinePartSize());

        return $state;
    }

    /**
     * Executes a MUP command with all of the parameters for the operation.
     *
     * @param string $operation Name of the operation.
     * @param array  $params    Service-specific params for the operation.
     *
     * @return PromiseInterface
     */
    protected function execCommand($operation, array $params)
    {
        // Create the command.
        $command = $this->client->getCommand(
            $this->info['command'][$operation],
            $params + $this->state->getId()
        );

        // Execute the before callback.
        if (is_callable($this->config["before_{$operation}"])) {
            $this->config["before_{$operation}"]($command);
        }

        // Execute the command asynchronously and return the promise.
        return $this->client->executeAsync($command);
    }

    /**
     * Returns a middleware for processing responses of part upload operations.
     *
     * - Adds an onFulfilled callback that calls the service-specific
     *   handleResult method on the Result of the operation.
     * - Adds an onRejected callback that adds the error to an array of errors.
     * - Has a passedByRef $errors arg that the exceptions get added to. The
     *   caller should use that &$errors array to do error handling.
     *
     * @param array $errors Errors from upload operations are added to this.
     *
     * @return callable
     */
    protected function getResultHandler(&$errors = [])
    {
        return function (callable $handler) use (&$errors) {
            return function (
                CommandInterface $command,
                ?RequestInterface $request = null
            ) use ($handler, &$errors) {
                return $handler($command, $request)->then(
                    function (ResultInterface $result) use ($command) {
                        $this->handleResult($command, $result);
                        return $result;
                    },
                    function (AwsException $e) use (&$errors) {
                        $errors[$e->getCommand()[$this->info['part_num']]] = $e;
                        return new Result();
                    }
                );
            };
        };
    }

    /**
     * Creates a generator that yields part data for the upload's source.
     *
     * Yields associative arrays of parameters that are ultimately merged in
     * with others to form the complete parameters of a  command. This can
     * include the Body parameter, which is a limited stream (i.e., a Stream
     * object, decorated with a LimitStream).
     *
     * @param callable $resultHandler
     *
     * @return \Generator
     */
    abstract protected function getUploadCommands(callable $resultHandler);
}
