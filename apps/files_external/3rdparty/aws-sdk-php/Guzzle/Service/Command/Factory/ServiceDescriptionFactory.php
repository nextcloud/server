<?php

namespace Guzzle\Service\Command\Factory;

use Guzzle\Service\Description\ServiceDescriptionInterface;
use Guzzle\Inflection\InflectorInterface;

/**
 * Command factory used to create commands based on service descriptions
 */
class ServiceDescriptionFactory implements FactoryInterface
{
    /** @var ServiceDescriptionInterface */
    protected $description;

    /** @var InflectorInterface */
    protected $inflector;

    /**
     * @param ServiceDescriptionInterface $description Service description
     * @param InflectorInterface          $inflector   Optional inflector to use if the command is not at first found
     */
    public function __construct(ServiceDescriptionInterface $description, InflectorInterface $inflector = null)
    {
        $this->setServiceDescription($description);
        $this->inflector = $inflector;
    }

    /**
     * Change the service description used with the factory
     *
     * @param ServiceDescriptionInterface $description Service description to use
     *
     * @return FactoryInterface
     */
    public function setServiceDescription(ServiceDescriptionInterface $description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Returns the service description
     *
     * @return ServiceDescriptionInterface
     */
    public function getServiceDescription()
    {
        return $this->description;
    }

    public function factory($name, array $args = array())
    {
        $command = $this->description->getOperation($name);

        // If a command wasn't found, then try to uppercase the first letter and try again
        if (!$command) {
            $command = $this->description->getOperation(ucfirst($name));
            // If an inflector was passed, then attempt to get the command using snake_case inflection
            if (!$command && $this->inflector) {
                $command = $this->description->getOperation($this->inflector->snake($name));
            }
        }

        if ($command) {
            $class = $command->getClass();
            return new $class($args, $command, $this->description);
        }
    }
}
