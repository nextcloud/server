<?php
namespace Aws;

use Psr\Http\Message\RequestInterface;
use Aws\Exception\AwsException;

/**
 * Represents a history container that is required when using the history
 * middleware.
 */
class History implements \Countable, \IteratorAggregate
{
    private $maxEntries;
    private $entries = array();

    /**
     * @param int $maxEntries Maximum number of entries to store.
     */
    public function __construct($maxEntries = 10)
    {
        $this->maxEntries = $maxEntries;
    }

    /**
     * @return int
     */
    #[\ReturnTypeWillChange]
    public function count()
    {
        return count($this->entries);
    }

    #[\ReturnTypeWillChange]
    public function getIterator()
    {
        return new \ArrayIterator(array_values($this->entries));
    }

    /**
     * Get the last finished command seen by the history container.
     *
     * @return CommandInterface
     * @throws \LogicException if no commands have been seen.
     */
    public function getLastCommand()
    {
        if (!$this->entries) {
            throw new \LogicException('No commands received');
        }

        return end($this->entries)['command'];
    }

    /**
     * Get the last finished request seen by the history container.
     *
     * @return RequestInterface
     * @throws \LogicException if no requests have been seen.
     */
    public function getLastRequest()
    {
        if (!$this->entries) {
            throw new \LogicException('No requests received');
        }

        return end($this->entries)['request'];
    }

    /**
     * Get the last received result or exception.
     *
     * @return ResultInterface|AwsException
     * @throws \LogicException if no return values have been received.
     */
    public function getLastReturn()
    {
        if (!$this->entries) {
            throw new \LogicException('No entries');
        }

        $last = end($this->entries);

        if (isset($last['result'])) {
            return $last['result'];
        }

        if (isset($last['exception'])) {
            return $last['exception'];
        }

        throw new \LogicException('No return value for last entry.');
    }

    /**
     * Initiate an entry being added to the history.
     *
     * @param CommandInterface $cmd Command be executed.
     * @param RequestInterface $req Request being sent.
     *
     * @return string Returns the ticket used to finish the entry.
     */
    public function start(CommandInterface $cmd, RequestInterface $req)
    {
        $ticket = uniqid();
        $this->entries[$ticket] = [
            'command'   => $cmd,
            'request'   => $req,
            'result'    => null,
            'exception' => null,
        ];

        return $ticket;
    }

    /**
     * Finish adding an entry to the history container.
     *
     * @param string $ticket Ticket returned from the start call.
     * @param mixed  $result The result (an exception or AwsResult).
     */
    public function finish($ticket, $result)
    {
        if (!isset($this->entries[$ticket])) {
            throw new \InvalidArgumentException('Invalid history ticket');
        }

        if (isset($this->entries[$ticket]['result'])
            || isset($this->entries[$ticket]['exception'])
        ) {
            throw new \LogicException('History entry is already finished');
        }

        if ($result instanceof \Exception) {
            $this->entries[$ticket]['exception'] = $result;
        } else {
            $this->entries[$ticket]['result'] = $result;
        }

        if (count($this->entries) >= $this->maxEntries) {
            $this->entries = array_slice($this->entries, -$this->maxEntries, null, true);
        }
    }

    /**
     * Flush the history
     */
    public function clear()
    {
        $this->entries = [];
    }

    /**
     * Converts the history to an array.
     *
     * @return array
     */
    public function toArray()
    {
        return array_values($this->entries);
    }
}
