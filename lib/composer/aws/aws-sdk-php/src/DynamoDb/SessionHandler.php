<?php
namespace Aws\DynamoDb;

/**
 * Provides an interface for using Amazon DynamoDB as a session store by hooking
 * into PHP's session handler hooks. Once registered, You may use the native
 * `$_SESSION` superglobal and session functions, and the sessions will be
 * stored automatically in DynamoDB. DynamoDB is a great session storage
 * solution due to its speed, scalability, and fault tolerance.
 *
 * For maximum performance, we recommend that you keep the size of your sessions
 * small. Locking is disabled by default, since it can drive up latencies and
 * costs under high traffic. Only turn it on if you need it.
 *
 * By far, the most expensive operation is garbage collection. Therefore, we
 * encourage you to carefully consider your session garbage collection strategy.
 * Note: the DynamoDB Session Handler does not allow garbage collection to be
 * triggered randomly. You must run garbage collection manually or through other
 * automated means using a cron job or similar scheduling technique.
 */
class SessionHandler implements \SessionHandlerInterface
{
    /** @var SessionConnectionInterface Session save logic.*/
    private $connection;

    /** @var string Session save path. */
    private $savePath;

    /** @var string Session name. */
    private $sessionName;

    /** @var string The last known session ID */
    private $openSessionId = '';

    /** @var string Stores serialized data for tracking changes. */
    private $dataRead = '';

    /** @var bool Keeps track of whether the session has been written. */
    private $sessionWritten = false;

    /**
     * Creates a new DynamoDB Session Handler.
     *
     * The configuration array accepts the following array keys and values:
     * - table_name:                    Name of table to store the sessions.
     * - hash_key:                      Name of hash key in table. Default: "id".
     * - data_attribute:                Name of the data attribute in table. Default: "data".
     * - session_lifetime:              Lifetime of inactive sessions expiration.
     * - session_lifetime_attribute:    Name of the session life time attribute in table. Default: "expires".
     * - consistent_read:               Whether or not to use consistent reads.
     * - batch_config:                  Batch options used for garbage collection.
     * - locking:                       Whether or not to use session locking.
     * - max_lock_wait_time:            Max time (s) to wait for lock acquisition.
     * - min_lock_retry_microtime:      Min time (µs) to wait between lock attempts.
     * - max_lock_retry_microtime:      Max time (µs) to wait between lock attempts.
     *
     * You can find the full list of parameters and defaults within the trait
     * Aws\DynamoDb\SessionConnectionConfigTrait
     *
     * @param DynamoDbClient $client Client for doing DynamoDB operations
     * @param array          $config Configuration for the Session Handler
     *
     * @return SessionHandler
     */
    public static function fromClient(DynamoDbClient $client, array $config = [])
    {
        $config += ['locking' => false];
        if ($config['locking']) {
            $connection = new LockingSessionConnection($client, $config);
        } else {
            $connection = new StandardSessionConnection($client, $config);
        }

        return new static($connection);
    }

    /**
     * @param SessionConnectionInterface $connection
     */
    public function __construct(SessionConnectionInterface $connection)
    {
        $this->connection = $connection;
    }

    /**
     * Register the DynamoDB session handler.
     *
     * @return bool Whether or not the handler was registered.
     * @codeCoverageIgnore
     */
    public function register()
    {
         return session_set_save_handler($this, true);
    }

    /**
     * Open a session for writing. Triggered by session_start().
     *
     * @param string $savePath    Session save path.
     * @param string $sessionName Session name.
     *
     * @return bool Whether or not the operation succeeded.
     */
     #[\ReturnTypeWillChange]
    public function open($savePath, $sessionName)
    {
        $this->savePath = $savePath;
        $this->sessionName = $sessionName;

        return true;
    }

    /**
     * Close a session from writing.
     *
     * @return bool Success
     */
     #[\ReturnTypeWillChange]
    public function close()
    {
        $id = session_id();
        // Make sure the session is unlocked and the expiration time is updated,
        // even if the write did not occur
        if ($this->openSessionId !== $id || !$this->sessionWritten) {
            $result = $this->connection->write($this->formatId($id), '', false);
            $this->sessionWritten = (bool) $result;
        }

        return $this->sessionWritten;
    }

    /**
     * Read a session stored in DynamoDB.
     *
     * @param string $id Session ID.
     *
     * @return string Session data.
     */
     #[\ReturnTypeWillChange]
    public function read($id)
    {
        $this->openSessionId = $id;
        // PHP expects an empty string to be returned from this method if no
        // data is retrieved
        $this->dataRead = '';

        // Get session data using the selected locking strategy
        $item = $this->connection->read($this->formatId($id));

        $dataAttribute = $this->connection->getDataAttribute();
        $sessionLifetimeAttribute = $this->connection->getSessionLifetimeAttribute();

        // Return the data if it is not expired. If it is expired, remove it
        if (isset($item[$sessionLifetimeAttribute]) && isset($item[$dataAttribute])) {
            $this->dataRead = $item[$dataAttribute];
            if ($item[$sessionLifetimeAttribute] <= time()) {
                $this->dataRead = '';
                $this->destroy($id);
            }
        }

        return $this->dataRead;
    }

    /**
     * Write a session to DynamoDB.
     *
     * @param string $id   Session ID.
     * @param string $data Serialized session data to write.
     *
     * @return bool Whether or not the operation succeeded.
     */
     #[\ReturnTypeWillChange]
    public function write($id, $data)
    {
        $changed = $id !== $this->openSessionId
            || $data !== $this->dataRead;
        $this->openSessionId = $id;

        // Write the session data using the selected locking strategy
        $this->sessionWritten = $this->connection
            ->write($this->formatId($id), $data, $changed);

        return $this->sessionWritten;
    }

    /**
     * Delete a session stored in DynamoDB.
     *
     * @param string $id Session ID.
     *
     * @return bool Whether or not the operation succeeded.
     */
     #[\ReturnTypeWillChange]
    public function destroy($id)
    {
        $this->openSessionId = $id;
        // Delete the session data using the selected locking strategy
        $this->sessionWritten
            = $this->connection->delete($this->formatId($id));

        return $this->sessionWritten;
    }

    /**
     * Satisfies the session handler interface, but does nothing. To do garbage
     * collection, you must manually call the garbageCollect() method.
     *
     * @param int $maxLifetime Ignored.
     *
     * @return bool Whether or not the operation succeeded.
     * @codeCoverageIgnore
     */
     #[\ReturnTypeWillChange]
    public function gc($maxLifetime)
    {
        // Garbage collection for a DynamoDB table must be triggered manually.
        return true;
    }

    /**
     * Triggers garbage collection on expired sessions.
     * @codeCoverageIgnore
     */
    public function garbageCollect()
    {
        $this->connection->deleteExpired();
    }

    /**
     * Prepend the session ID with the session name.
     *
     * @param string $id The session ID.
     *
     * @return string Prepared session ID.
     */
    private function formatId($id)
    {
        return trim($this->sessionName . '_' . $id, '_');
    }
}
