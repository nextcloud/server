<?php

namespace Guzzle\Service\Resource;

use Guzzle\Common\Exception\InvalidArgumentException;
use Guzzle\Service\Command\CommandInterface;

/**
 * Abstract resource iterator factory implementation
 */
abstract class AbstractResourceIteratorFactory implements ResourceIteratorFactoryInterface
{
    public function build(CommandInterface $command, array $options = array())
    {
        if (!$this->canBuild($command)) {
            throw new InvalidArgumentException('Iterator was not found for ' . $command->getName());
        }

        $className = $this->getClassName($command);

        return new $className($command, $options);
    }

    public function canBuild(CommandInterface $command)
    {
        return (bool) $this->getClassName($command);
    }

    /**
     * Get the name of the class to instantiate for the command
     *
     * @param CommandInterface $command Command that is associated with the iterator
     *
     * @return string
     */
    abstract protected function getClassName(CommandInterface $command);
}
