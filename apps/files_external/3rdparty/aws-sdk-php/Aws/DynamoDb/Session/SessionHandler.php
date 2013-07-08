<?php
/**
 * Copyright 2010-2013 Amazon.com, Inc. or its affiliates. All Rights Reserved.
 *
 * Licensed under the Apache License, Version 2.0 (the "License").
 * You may not use this file except in compliance with the License.
 * A copy of the License is located at
 *
 * http://aws.amazon.com/apache2.0
 *
 * or in the "license" file accompanying this file. This file is distributed
 * on an "AS IS" BASIS, WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either
 * express or implied. See the License for the specific language governing
 * permissions and limitations under the License.
 */

namespace Aws\DynamoDb\Session;

use Aws\Common\Enum\UaString as Ua;
use Aws\DynamoDb\DynamoDbClient;
use Aws\DynamoDb\Session\LockingStrategy\LockingStrategyInterface;
use Aws\DynamoDb\Session\LockingStrategy\LockingStrategyFactory;
use Aws\DynamoDb\Model\BatchRequest\WriteRequestBatch;
use Aws\DynamoDb\Model\BatchRequest\DeleteRequest;

/**
 * Provides an interface for using Amazon DynamoDB as a session store by hooking into PHP's session handler hooks. Once
 * registered, You may use the native `$_SESSION` superglobal and session functions, and the sessions will be stored
 * automatically in DynamoDB. DynamoDB is a great session storage solution due to its speed, scalability, and fault
 * tolerance.
 *
 * For maximum performance, we recommend that you keep the size of your sessions small. Items greater than 1KB require
 * more throughput in DynamoDB. Also, in this version of the session handler, session locking is turned off by default
 * since it can drive up latencies and costs under high traffic and poor session management, especially when using ajax.
 * Only turn it on if you need it.
 *
 * By far, the most expensive operation is garbage collection. Therefore, we encourage you to carefully consider your
 * session garbage collection strategy. We recommend that you change the `session.gc_probability` ini setting to 0 so
 * that garbage collection is not triggered randomly. You should consider using a cron job or similar scheduling
 * technique for triggering garbage collection at appropriate times.
 */
class SessionHandler
{
    /**
     * @var DynamoDbClient The DynamoDB client
     */
    protected $client;

    /**
     * @var LockingStrategyInterface The locking strategy
     */
    protected $lockingStrategy;

    /**
     * @var SessionHandlerConfig The config for the handler and locking strategy
     */
    protected $config;

    /**
     * @var string The session save path
     */
    protected $savePath;

    /**
     * @var string The session name
     */
    protected $sessionName;

    /**
     * @var string Stores the serialized data that was read for tracking changes
     */
    protected $dataRead;

    /**
     * @var string Keeps track of the open session's ID
     */
    protected $openSessionId;

    /**
     * @var bool Keeps track of whether the session has been written
     */
    protected $sessionWritten;

    /**
     * Factory method to create a new DynamoDB Session Handler
     *
     * The configuration array accepts the following array keys and values:
     * - locking_strategy:         Locking strategy fused for doing session locking. Default: null
     * - dynamodb_client:          DynamoDbClient object used for performing DynamoDB operations
     * - table_name:               Name of the DynamoDB table in which to store the sessions. Default: "sessions"
     * - hash_key:                 Name of the hash key in the DynamoDB sessions table. Default: "id"
     * - session_lifetime:         Lifetime of an inactive session before it should be garbage collected.
     * - consistent_read:          Whether or not to use DynamoDB consistent reads for `GetItem`. Default: true
     * - automatic_gc:             Whether or not to use PHP's session auto garbage collection triggers.
     * - gc_batch_size:            Batch size used for removing expired sessions during garbage collection. Default: 25
     * - gc_operation_delay:       Delay between service operations during garbage collection
     * - max_lock_wait_time:       Maximum time (in seconds) to wait to acquire a lock before giving up
     * - min_lock_retry_microtime: Minimum time (in microseconds) to wait between attempts to acquire a lock
     * - max_lock_retry_microtime: Maximum time (in microseconds) to wait between attempts to acquire a lock
     *
     * @param array $config Configuration options
     *
     * @return SessionHandler
     */
    public static function factory(array $config = array())
    {
        // Setup session handler configuration and get the client
        $config = new SessionHandlerConfig($config);
        $client = $config->get('dynamodb_client');

        // Make sure locking strategy has been provided or provide a default
        $strategy = $config->get('locking_strategy');
        if (!($strategy instanceof LockingStrategyInterface)) {
            $factory  = new LockingStrategyFactory();
            $strategy = $factory->factory($strategy, $config);
        }

        // Return an instance of the session handler
        return new static($client, $strategy, $config);
    }

    /**
     * Constructs a new DynamoDB Session Handler
     *
     * @param DynamoDbClient           $client   Client for doing DynamoDB operations
     * @param LockingStrategyInterface $strategy Locking strategy for performing session locking logic
     * @param SessionHandlerConfig     $config   Configuration options for the session handler
     */
    public function __construct(
        DynamoDbClient $client,
        LockingStrategyInterface $strategy,
        SessionHandlerConfig $config
    ) {
        $this->client          = $client;
        $this->lockingStrategy = $strategy;
        $this->config          = $config;
    }

    /**
     * Destruct the session handler and make sure the session gets written
     *
     * NOTE: It is usually better practice to call `session_write_close()` manually in your application as soon as
     * session modifications are complete. This is especially true if session locking is enabled.
     *
     * @link http://php.net/manual/en/function.session-set-save-handler.php#refsect1-function.session-set-save-handler-notes
     */
    public function __destruct()
    {
        session_write_close();
    }

    /**
     * Register the DynamoDB session handler.
     *
     * Uses the PHP-provided method to register this class as a session handler.
     *
     * @return bool Whether or not the handler was registered
     */
    public function register()
    {
        // Set garbage collection probability based on config
        $autoGarbageCollection = $this->config->get('automatic_gc') ? '1' : '0';
        ini_set('session.gc_probability', $autoGarbageCollection);

        // Register the session handler
        return session_set_save_handler(
            array($this, 'open'),
            array($this, 'close'),
            array($this, 'read'),
            array($this, 'write'),
            array($this, 'destroy'),
            array($this, 'gc')
        );
    }

    /**
     * Checks if the session is open and writable
     *
     * @return bool Whether or not the session is open for writing
     */
    public function isSessionOpen()
    {
        return (bool) $this->openSessionId;
    }

    /**
     * Checks if the session has been written
     *
     * @return bool Whether or not the session has been written
     */
    public function isSessionWritten()
    {
        return $this->sessionWritten;
    }

    /**
     * Creates a table in DynamoDB for session storage according to provided configuration options.
     *
     * Note: This is a one-time operation. It may be better to do this via the AWS management console ahead of time.
     *
     * @param int $readCapacityUnits  RCUs for table read throughput
     * @param int $writeCapacityUnits WCUs table write throughput
     *
     * @return array The command result
     */
    public function createSessionsTable($readCapacityUnits, $writeCapacityUnits)
    {
        $tableName = $this->config->get('table_name');
        $hashKey = $this->config->get('hash_key');

        $params = array(
            'TableName' => $tableName,
            'ProvisionedThroughput' => array(
                'ReadCapacityUnits'  => (int) $readCapacityUnits,
                'WriteCapacityUnits' => (int) $writeCapacityUnits,
            ),
            Ua::OPTION => Ua::SESSION
        );

        if ($this->client->getApiVersion() < '2012-08-10') {
            $params['KeySchema'] = array(
                'HashKeyElement' => array(
                    'AttributeName' => $hashKey,
                    'AttributeType' => 'S',
                )
            );
        } else {
            $params['AttributeDefinitions'] = array(
                array(
                    'AttributeName' => $hashKey,
                    'AttributeType' => 'S'
                )
            );
            $params['KeySchema'] = array(
                array(
                    'AttributeName' => $hashKey,
                    'KeyType' => 'HASH'
                )
            );
        }

        $result = $this->client->getCommand('CreateTable', $params)->execute();

        $this->client->waitUntil('table_exists', array('TableName' => $tableName));

        return $result;
    }

    /**
     * Open a session for writing. Triggered by session_start()
     *
     * Part of the standard PHP session handler interface
     *
     * @param string $savePath    The session save path
     * @param string $sessionName The session name
     *
     * @return bool Whether or not the operation succeeded
     */
    public function open($savePath, $sessionName)
    {
        $this->savePath      = $savePath;
        $this->sessionName   = $sessionName;
        $this->openSessionId = session_id();

        return $this->isSessionOpen();
    }

    /**
     * Close a session from writing
     *
     * Part of the standard PHP session handler interface
     *
     * @return bool Success
     */
    public function close()
    {
        // Make sure the session is unlocked and the expiration time is updated, even if the write did not occur
        if (!$this->isSessionWritten()) {
            $id     = $this->formatId($this->openSessionId);
            $result = $this->lockingStrategy->doWrite($id, '', false);
            $this->sessionWritten = (bool) $result;
        }

        $this->openSessionId = null;

        return $this->isSessionWritten();
    }

    /**
     * Read a session stored in DynamoDB
     *
     * Part of the standard PHP session handler interface
     *
     * @param string $id The session ID
     *
     * @return string The session data
     */
    public function read($id)
    {
        // PHP expects an empty string to be returned from this method if no
        // data is retrieved
        $this->dataRead = '';

        // Get session data using the selected locking strategy
        $item = $this->lockingStrategy->doRead($this->formatId($id));

        // Return the data if it is not expired. If it is expired, remove it
        if (isset($item['expires']) && isset($item['data'])) {
            $this->dataRead = $item['data'];
            if ($item['expires'] <= time()) {
                $this->dataRead = '';
                $this->destroy($id);
            }
        }

        return $this->dataRead;
    }

    /**
     * Write a session to DynamoDB
     *
     * Part of the standard PHP session handler interface
     *
     * @param string $id   The session ID
     * @param string $data The serialized session data to write
     *
     * @return bool Whether or not the operation succeeded
     */
    public function write($id, $data)
    {
        // Write the session data using the selected locking strategy
        $this->sessionWritten = $this->lockingStrategy->doWrite(
            $this->formatId($id),
            $data,
            ($data !== $this->dataRead)
        );

        return $this->isSessionWritten();
    }

    /**
     * Delete a session stored in DynamoDB
     *
     * Part of the standard PHP session handler interface
     *
     * @param string $id The session ID
     *
     * @return bool Whether or not the operation succeeded
     */
    public function destroy($id)
    {
        // Delete the session data using the selected locking strategy
        $this->sessionWritten = $this->lockingStrategy->doDestroy($this->formatId($id));

        return $this->isSessionWritten();
    }

    /**
     * Triggers garbage collection on expired sessions
     *
     * Part of the standard PHP session handler interface
     *
     * @param int $maxLifetime The value of `session.gc_maxlifetime`. Ignored
     *
     * @return bool
     */
    public function gc($maxLifetime)
    {
        try {
            $this->garbageCollect();

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Performs garbage collection on the sessions stored in the DynamoDB table
     *
     * If triggering garbage collection manually, use this method. If your garbage collection is triggered automatically
     * by php (not recommended), then use the `gc` method.
     */
    public function garbageCollect()
    {
        // Get relevant configuration data
        $delay     = (int) $this->config->get('gc_operation_delay');
        $batchSize = (int) $this->config->get('gc_batch_size');
        $tableName = $this->config->get('table_name');
        $hashKey   = $this->config->get('hash_key');
        $expires   = (string) time();
        $isOldApi  = ($this->client->getApiVersion() < '2012-08-10');

        // Instantiate and configure the WriteRequestBatch object that will be deleting the expired sessions
        if ($delay) {
            $delayFunction = function () use ($delay) {
                sleep($delay);
            };
            $deleteBatch = WriteRequestBatch::factory($this->client, $batchSize, $delayFunction);
        } else {
            $deleteBatch = WriteRequestBatch::factory($this->client, $batchSize);
        }

        // Setup a scan table iterator for finding expired session items
        $scanParams = array(
            'TableName' => $tableName,
            'AttributesToGet' => array(
                $this->config->get('hash_key')
            ),
            'ScanFilter' => array(
                'expires' => array(
                    'ComparisonOperator' => 'LT',
                    'AttributeValueList' => array(
                        array(
                            'N' => $expires
                        )
                    ),
                ),
                'lock' => array(
                    'ComparisonOperator' => 'NULL',
                )
            ),
            Ua::OPTION => Ua::SESSION
        );
        if (!$isOldApi) {
            $scanParams['Select'] = 'SPECIFIC_ATTRIBUTES';
        }

        // Create a scan table iterator for finding expired session items
        $tableScanner = $this->client->getIterator('Scan', $scanParams);

        // If a delay has been set, then attach the delay function to execute after each scan operation
        if (isset($delayFunction)) {
            $tableScanner->getEventDispatcher()->addListener('resource_iterator.after_send', $delayFunction);
        }

        // Perform scan and batch delete operations as needed
        $keyName = $isOldApi ? 'HashKeyElement' : $hashKey;
        foreach ($tableScanner as $item) {
            // @codeCoverageIgnoreStart
            $deleteBatch->add(new DeleteRequest(array($keyName => $item[$hashKey]), $tableName));
            // @codeCoverageIgnoreEnd
        }

        // Delete any remaining items
        $deleteBatch->flush();
    }

    /**
     * Prepend the session ID with the session name
     *
     * @param string $id The session ID
     *
     * @return string Prepared session ID
     */
    protected function formatId($id)
    {
        return trim($this->sessionName . '_' . $id, '_');
    }
}
