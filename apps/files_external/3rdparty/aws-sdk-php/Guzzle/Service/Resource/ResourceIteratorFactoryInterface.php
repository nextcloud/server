<?php

namespace Guzzle\Service\Resource;

use Guzzle\Service\Command\CommandInterface;

/**
 * Factory for creating {@see ResourceIteratorInterface} objects
 */
interface ResourceIteratorFactoryInterface
{
    /**
     * Create a resource iterator
     *
     * @param CommandInterface $command Command to create an iterator for
     * @param array                 $options Iterator options that are exposed as data.
     *
     * @return ResourceIteratorInterface
     */
    public function build(CommandInterface $command, array $options = array());

    /**
     * Check if the factory can create an iterator
     *
     * @param CommandInterface $command Command to create an iterator for
     *
     * @return bool
     */
    public function canBuild(CommandInterface $command);
}
