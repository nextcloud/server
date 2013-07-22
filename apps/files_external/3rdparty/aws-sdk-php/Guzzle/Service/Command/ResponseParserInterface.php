<?php

namespace Guzzle\Service\Command;

/**
 * Parses the HTTP response of a command and sets the appropriate result on a command object
 */
interface ResponseParserInterface
{
    /**
     * Parse the HTTP response received by the command and update the command's result contents
     *
     * @param CommandInterface $command Command to parse and update
     *
     * @return mixed Returns the result to set on the command
     */
    public function parse(CommandInterface $command);
}
