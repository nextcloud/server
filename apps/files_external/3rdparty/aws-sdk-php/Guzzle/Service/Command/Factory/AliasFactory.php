<?php

namespace Guzzle\Service\Command\Factory;

use Guzzle\Common\Exception\InvalidArgumentException;
use Guzzle\Service\ClientInterface;

/**
 * Command factory used when you need to provide aliases to commands
 */
class AliasFactory implements FactoryInterface
{
    /** @var array Associative array mapping command aliases to the aliased command */
    protected $aliases;

    /** @var ClientInterface Client used to retry using aliases */
    protected $client;

    /**
     * @param ClientInterface $client  Client used to retry with the alias
     * @param array           $aliases Associative array mapping aliases to the alias
     */
    public function __construct(ClientInterface $client, array $aliases)
    {
        $this->client = $client;
        $this->aliases = $aliases;
    }

    public function factory($name, array $args = array())
    {
        if (isset($this->aliases[$name])) {
            try {
                return $this->client->getCommand($this->aliases[$name], $args);
            } catch (InvalidArgumentException $e) {
                return null;
            }
        }
    }
}
