<?php

namespace Guzzle\Service\Exception;

use Guzzle\Http\Exception\MultiTransferException;
use Guzzle\Service\Command\CommandInterface;

/**
 * Exception thrown when transferring commands in parallel
 */
class CommandTransferException extends MultiTransferException
{
    protected $successfulCommands = array();
    protected $failedCommands = array();

    /**
     * Creates a new CommandTransferException from a MultiTransferException
     *
     * @param MultiTransferException $e Exception to base a new exception on
     *
     * @return self
     */
    public static function fromMultiTransferException(MultiTransferException $e)
    {
        $ce = new self($e->getMessage(), $e->getCode(), $e->getPrevious());

        return $ce->setExceptions($e->getIterator()->getArrayCopy())
            ->setSuccessfulRequests($e->getSuccessfulRequests())
            ->setFailedRequests($e->getFailedRequests());
    }

    /**
     * Get all of the commands in the transfer
     *
     * @return array
     */
    public function getAllCommands()
    {
        return array_merge($this->successfulCommands, $this->failedCommands);
    }

    /**
     * Add to the array of successful commands
     *
     * @param CommandInterface $command Successful command
     *
     * @return self
     */
    public function addSuccessfulCommand(CommandInterface $command)
    {
        $this->successfulCommands[] = $command;

        return $this;
    }

    /**
     * Add to the array of failed commands
     *
     * @param CommandInterface $command Failed command
     *
     * @return self
     */
    public function addFailedCommand(CommandInterface $command)
    {
        $this->failedCommands[] = $command;

        return $this;
    }

    /**
     * Get an array of successful commands
     *
     * @return array
     */
    public function getSuccessfulCommands()
    {
        return $this->successfulCommands;
    }

    /**
     * Get an array of failed commands
     *
     * @return array
     */
    public function getFailedCommands()
    {
        return $this->failedCommands;
    }
}
