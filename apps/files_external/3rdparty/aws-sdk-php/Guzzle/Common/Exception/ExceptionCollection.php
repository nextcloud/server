<?php

namespace Guzzle\Common\Exception;

/**
 * Collection of exceptions
 */
class ExceptionCollection extends \Exception implements GuzzleException, \IteratorAggregate, \Countable
{
    /** @var array Array of Exceptions */
    protected $exceptions = array();

    /**
     * Set all of the exceptions
     *
     * @param array $exceptions Array of exceptions
     *
     * @return self
     */
    public function setExceptions(array $exceptions)
    {
        $this->exceptions = array();
        foreach ($exceptions as $exception) {
            $this->add($exception);
        }

        return $this;
    }

    /**
     * Add exceptions to the collection
     *
     * @param ExceptionCollection|\Exception $e Exception to add
     *
     * @return ExceptionCollection;
     */
    public function add($e)
    {
        if ($this->message) {
            $this->message .= "\n";
        }

        if ($e instanceof self) {
            $this->message .= '(' . get_class($e) . ")";
            foreach (explode("\n", $e->getMessage()) as $message) {
                $this->message .= "\n    {$message}";
            }
        } elseif ($e instanceof \Exception) {
            $this->exceptions[] = $e;
            $this->message .= '(' . get_class($e) . ') ' . $e->getMessage();
        }

        return $this;
    }

    /**
     * Get the total number of request exceptions
     *
     * @return int
     */
    public function count()
    {
        return count($this->exceptions);
    }

    /**
     * Allows array-like iteration over the request exceptions
     *
     * @return \ArrayIterator
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->exceptions);
    }

    /**
     * Get the first exception in the collection
     *
     * @return \Exception
     */
    public function getFirst()
    {
        return $this->exceptions ? $this->exceptions[0] : null;
    }
}
