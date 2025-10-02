<?php


namespace Stecman\Component\Symfony\Console\BashCompletion;

use Stecman\Component\Symfony\Console\BashCompletion\Completion\CompletionInterface;

class Completion implements CompletionInterface
{
    /**
     * The type of input (option/argument) the completion should be run for
     *
     * @see CompletionInterface::ALL_TYPES
     * @var string
     */
    protected $type;

    /**
     * The command name the completion should be run for
     *
     * @see CompletionInterface::ALL_COMMANDS
     * @var string|null
     */
    protected $commandName;

    /**
     * The option/argument name the completion should be run for
     *
     * @var string
     */
    protected $targetName;

    /**
     * Array of values to return, or a callback to generate completion results with
     * The callback can be in any form accepted by call_user_func.
     *
     * @var callable|array
     */
    protected $completion;

    /**
     * Create a Completion with the command name set to CompletionInterface::ALL_COMMANDS
     *
     * @deprecated - This will be removed in 1.0.0 as it is redundant and isn't any more concise than what it implements.
     *
     * @param string $targetName
     * @param string $type
     * @param array|callable $completion
     * @return Completion
     */
    public static function makeGlobalHandler($targetName, $type, $completion)
    {
        return new Completion(CompletionInterface::ALL_COMMANDS, $targetName, $type, $completion);
    }

    /**
     * @param string $commandName
     * @param string $targetName
     * @param string $type
     * @param array|callable $completion
     */
    public function __construct($commandName, $targetName, $type, $completion)
    {
        $this->commandName = $commandName;
        $this->targetName = $targetName;
        $this->type = $type;
        $this->completion = $completion;
    }

    /**
     * Return the stored completion, or the results returned from the completion callback
     *
     * @return array
     */
    public function run()
    {
        if ($this->isCallable()) {
            return call_user_func($this->completion);
        }

        return $this->completion;
    }

    /**
     * Get type of input (option/argument) the completion should be run for
     *
     * @see CompletionInterface::ALL_TYPES
     * @return string|null
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set type of input (option/argument) the completion should be run for
     *
     * @see CompletionInterface::ALL_TYPES
     * @param string|null $type
     */
    public function setType($type)
    {
        $this->type = $type;
    }

    /**
     * Get the command name the completion should be run for
     *
     * @see CompletionInterface::ALL_COMMANDS
     * @return string|null
     */
    public function getCommandName()
    {
        return $this->commandName;
    }

    /**
     * Set the command name the completion should be run for
     *
     * @see CompletionInterface::ALL_COMMANDS
     * @param string|null $commandName
     */
    public function setCommandName($commandName)
    {
        $this->commandName = $commandName;
    }

    /**
     * Set the option/argument name the completion should be run for
     *
     * @see setType()
     * @return string
     */
    public function getTargetName()
    {
        return $this->targetName;
    }

    /**
     * Get the option/argument name the completion should be run for
     *
     * @see getType()
     * @param string $targetName
     */
    public function setTargetName($targetName)
    {
        $this->targetName = $targetName;
    }

    /**
     * Return the array or callback configured for for the Completion
     *
     * @return array|callable
     */
    public function getCompletion()
    {
        return $this->completion;
    }

    /**
     * Set the array or callback to return/run when Completion is run
     *
     * @see run()
     * @param array|callable $completion
     */
    public function setCompletion($completion)
    {
        $this->completion = $completion;
    }

    /**
     * Check if the configured completion value is a callback function
     *
     * @return bool
     */
    public function isCallable()
    {
        return is_callable($this->completion);
    }
}
