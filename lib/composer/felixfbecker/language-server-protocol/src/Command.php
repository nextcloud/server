<?php

namespace LanguageServerProtocol;

/**
 * Represents a reference to a command. Provides a title which will be used to represent a command in the UI and,
 * optionally, an array of arguments which will be passed to the command handler function when invoked.
 */
class Command
{
    /**
     * Title of the command, like `save`.
     *
     * @var string
     */
    public $title;

    /**
     * The identifier of the actual command handler.
     *
     * @var string
     */
    public $command;

    /**
     * Arguments that the command handler should be
     * invoked with.
     *
     * @var mixed[]|null
     */
    public $arguments;

    public function __construct(string $title = null, string $command = null, array $arguments = null)
    {
        $this->title = $title;
        $this->command = $command;
        $this->arguments = $arguments;
    }
}
