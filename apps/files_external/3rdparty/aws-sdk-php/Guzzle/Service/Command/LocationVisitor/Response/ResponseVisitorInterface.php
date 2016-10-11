<?php

namespace Guzzle\Service\Command\LocationVisitor\Response;

use Guzzle\Http\Message\Response;
use Guzzle\Service\Description\Parameter;
use Guzzle\Service\Command\CommandInterface;

/**
 * Location visitor used to parse values out of a response into an associative array
 */
interface ResponseVisitorInterface
{
    /**
     * Called before visiting all parameters. This can be used for seeding the result of a command with default
     * data (e.g. populating with JSON data in the response then adding to the parsed data).
     *
     * @param CommandInterface $command Command being visited
     * @param array            $result  Result value to update if needed (e.g. parsing XML or JSON)
     */
    public function before(CommandInterface $command, array &$result);

    /**
     * Called after visiting all parameters
     *
     * @param CommandInterface $command Command being visited
     */
    public function after(CommandInterface $command);

    /**
     * Called once for each parameter being visited that matches the location type
     *
     * @param CommandInterface $command  Command being visited
     * @param Response         $response Response being visited
     * @param Parameter        $param    Parameter being visited
     * @param mixed            $value    Result associative array value being updated by reference
     * @param mixed            $context  Parsing context
     */
    public function visit(
        CommandInterface $command,
        Response $response,
        Parameter $param,
        &$value,
        $context =  null
    );
}
