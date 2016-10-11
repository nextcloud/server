<?php

namespace Guzzle\Service\Resource;

use Guzzle\Common\Exception\InvalidArgumentException;
use Guzzle\Service\Command\CommandInterface;

/**
 * Factory that utilizes multiple factories for creating iterators
 */
class CompositeResourceIteratorFactory implements ResourceIteratorFactoryInterface
{
    /** @var array Array of factories */
    protected $factories;

    /** @param array $factories Array of factories used to instantiate iterators */
    public function __construct(array $factories)
    {
        $this->factories = $factories;
    }

    public function build(CommandInterface $command, array $options = array())
    {
        if (!($factory = $this->getFactory($command))) {
            throw new InvalidArgumentException('Iterator was not found for ' . $command->getName());
        }

        return $factory->build($command, $options);
    }

    public function canBuild(CommandInterface $command)
    {
        return $this->getFactory($command) !== false;
    }

    /**
     * Add a factory to the composite factory
     *
     * @param ResourceIteratorFactoryInterface $factory Factory to add
     *
     * @return self
     */
    public function addFactory(ResourceIteratorFactoryInterface $factory)
    {
        $this->factories[] = $factory;

        return $this;
    }

    /**
     * Get the factory that matches the command object
     *
     * @param CommandInterface $command Command retrieving the iterator for
     *
     * @return ResourceIteratorFactoryInterface|bool
     */
    protected function getFactory(CommandInterface $command)
    {
        foreach ($this->factories as $factory) {
            if ($factory->canBuild($command)) {
                return $factory;
            }
        }

        return false;
    }
}
