<?php

namespace Guzzle\Service\Resource;

use Guzzle\Common\AbstractHasDispatcher;
use Guzzle\Service\Command\CommandInterface;

abstract class ResourceIterator extends AbstractHasDispatcher implements ResourceIteratorInterface
{
    /** @var CommandInterface Command used to send requests */
    protected $command;

    /** @var CommandInterface First sent command */
    protected $originalCommand;

    /** @var array Currently loaded resources */
    protected $resources;

    /** @var int Total number of resources that have been retrieved */
    protected $retrievedCount = 0;

    /** @var int Total number of resources that have been iterated */
    protected $iteratedCount = 0;

    /** @var string NextToken/Marker for a subsequent request */
    protected $nextToken = false;

    /** @var int Maximum number of resources to fetch per request */
    protected $pageSize;

    /** @var int Maximum number of resources to retrieve in total */
    protected $limit;

    /** @var int Number of requests sent */
    protected $requestCount = 0;

    /** @var array Initial data passed to the constructor */
    protected $data = array();

    /** @var bool Whether or not the current value is known to be invalid */
    protected $invalid;

    public static function getAllEvents()
    {
        return array(
            // About to issue another command to get more results
            'resource_iterator.before_send',
            // Issued another command to get more results
            'resource_iterator.after_send'
        );
    }

    /**
     * @param CommandInterface $command Initial command used for iteration
     * @param array            $data    Associative array of additional parameters. You may specify any number of custom
     *     options for an iterator. Among these options, you may also specify the following values:
     *     - limit: Attempt to limit the maximum number of resources to this amount
     *     - page_size: Attempt to retrieve this number of resources per request
     */
    public function __construct(CommandInterface $command, array $data = array())
    {
        // Clone the command to keep track of the originating command for rewind
        $this->originalCommand = $command;

        // Parse options from the array of options
        $this->data = $data;
        $this->limit = array_key_exists('limit', $data) ? $data['limit'] : 0;
        $this->pageSize = array_key_exists('page_size', $data) ? $data['page_size'] : false;
    }

    /**
     * Get all of the resources as an array (Warning: this could issue a large number of requests)
     *
     * @return array
     */
    public function toArray()
    {
        return iterator_to_array($this, false);
    }

    public function setLimit($limit)
    {
        $this->limit = $limit;
        $this->resetState();

        return $this;
    }

    public function setPageSize($pageSize)
    {
        $this->pageSize = $pageSize;
        $this->resetState();

        return $this;
    }

    /**
     * Get an option from the iterator
     *
     * @param string $key Key of the option to retrieve
     *
     * @return mixed|null Returns NULL if not set or the value if set
     */
    public function get($key)
    {
        return array_key_exists($key, $this->data) ? $this->data[$key] : null;
    }

    /**
     * Set an option on the iterator
     *
     * @param string $key   Key of the option to set
     * @param mixed  $value Value to set for the option
     *
     * @return ResourceIterator
     */
    public function set($key, $value)
    {
        $this->data[$key] = $value;

        return $this;
    }

    public function current()
    {
        return $this->resources ? current($this->resources) : false;
    }

    public function key()
    {
        return max(0, $this->iteratedCount - 1);
    }

    public function count()
    {
        return $this->retrievedCount;
    }

    /**
     * Get the total number of requests sent
     *
     * @return int
     */
    public function getRequestCount()
    {
        return $this->requestCount;
    }

    /**
     * Rewind the Iterator to the first element and send the original command
     */
    public function rewind()
    {
        // Use the original command
        $this->command = clone $this->originalCommand;
        $this->resetState();
        $this->next();
    }

    public function valid()
    {
        return !$this->invalid && (!$this->resources || $this->current() || $this->nextToken)
            && (!$this->limit || $this->iteratedCount < $this->limit + 1);
    }

    public function next()
    {
        $this->iteratedCount++;

        // Check if a new set of resources needs to be retrieved
        $sendRequest = false;
        if (!$this->resources) {
            $sendRequest = true;
        } else {
            // iterate over the internal array
            $current = next($this->resources);
            $sendRequest = $current === false && $this->nextToken && (!$this->limit || $this->iteratedCount < $this->limit + 1);
        }

        if ($sendRequest) {

            $this->dispatch('resource_iterator.before_send', array(
                'iterator'  => $this,
                'resources' => $this->resources
            ));

            // Get a new command object from the original command
            $this->command = clone $this->originalCommand;
            // Send a request and retrieve the newly loaded resources
            $this->resources = $this->sendRequest();
            $this->requestCount++;

            // If no resources were found, then the last request was not needed
            // and iteration must stop
            if (empty($this->resources)) {
                $this->invalid = true;
            } else {
                // Add to the number of retrieved resources
                $this->retrievedCount += count($this->resources);
                // Ensure that we rewind to the beginning of the array
                reset($this->resources);
            }

            $this->dispatch('resource_iterator.after_send', array(
                'iterator'  => $this,
                'resources' => $this->resources
            ));
        }
    }

    /**
     * Retrieve the NextToken that can be used in other iterators.
     *
     * @return string Returns a NextToken
     */
    public function getNextToken()
    {
        return $this->nextToken;
    }

    /**
     * Returns the value that should be specified for the page size for a request that will maintain any hard limits,
     * but still honor the specified pageSize if the number of items retrieved + pageSize < hard limit
     *
     * @return int Returns the page size of the next request.
     */
    protected function calculatePageSize()
    {
        if ($this->limit && $this->iteratedCount + $this->pageSize > $this->limit) {
            return 1 + ($this->limit - $this->iteratedCount);
        }

        return (int) $this->pageSize;
    }

    /**
     * Reset the internal state of the iterator without triggering a rewind()
     */
    protected function resetState()
    {
        $this->iteratedCount = 0;
        $this->retrievedCount = 0;
        $this->nextToken = false;
        $this->resources = null;
        $this->invalid = false;
    }

    /**
     * Send a request to retrieve the next page of results. Hook for subclasses to implement.
     *
     * @return array Returns the newly loaded resources
     */
    abstract protected function sendRequest();
}
