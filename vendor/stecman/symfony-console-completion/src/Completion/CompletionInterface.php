<?php


namespace Stecman\Component\Symfony\Console\BashCompletion\Completion;

interface CompletionInterface
{
    // Sugar for indicating that a Completion should run for all command names and for all types
    // Intended to avoid meaningless null parameters in the constructors of implementing classes
    const ALL_COMMANDS = null;
    const ALL_TYPES = null;

    const TYPE_OPTION = 'option';
    const TYPE_ARGUMENT = 'argument';

    /**
     * Return the type of input (option/argument) completion should be run for
     *
     * @see \Symfony\Component\Console\Command\Command::addArgument
     * @see \Symfony\Component\Console\Command\Command::addOption
     * @return string - one of the CompletionInterface::TYPE_* constants
     */
    public function getType();

    /**
     * Return the name of the command completion should be run for
     * If the return value is CompletionInterface::ALL_COMMANDS, the completion will be run for any command name
     *
     * @see \Symfony\Component\Console\Command\Command::setName
     * @return string|null
     */
    public function getCommandName();

    /**
     * Return the option/argument name the completion should be run for
     * CompletionInterface::getType determines whether the target name refers to an option or an argument
     *
     * @return string
     */
    public function getTargetName();

    /**
     * Execute the completion
     *
     * @return string[] - an array of possible completion values
     */
    public function run();
}
