<?php
namespace Aws\Common;

/**
 * Provides endpoints based on a rules configuration file.
 */
class RulesEndpointProvider
{
    /** @var array */
    private $patterns;

    /**
     * @param array $patterns Hash of endpoint patterns mapping to endpoint
     *                        configurations.
     */
    public function __construct(array $patterns)
    {
        $this->patterns = $patterns;
    }

    /**
     * Creates and returns the default RulesEndpointProvider based on the
     * public rule sets.
     *
     * @return self
     */
    public static function fromDefaults()
    {
        return new self(require __DIR__ . '/Resources/public-endpoints.php');
    }

    public function __invoke(array $args = array())
    {
        if (!isset($args['service'])) {
            throw new \InvalidArgumentException('Requires a "service" value');
        }

        if (!isset($args['region'])) {
            throw new \InvalidArgumentException('Requires a "region" value');
        }

        foreach ($this->getKeys($args['region'], $args['service']) as $key) {
            if (isset($this->patterns['endpoints'][$key])) {
                return $this->expand($this->patterns['endpoints'][$key], $args);
            }
        }

        throw new \RuntimeException('Could not resolve endpoint');
    }

    private function expand(array $config, array $args)
    {
        $scheme = isset($args['scheme']) ? $args['scheme'] : 'https';
        $config['endpoint'] = $scheme . '://' . str_replace(
            array('{service}', '{region}'),
            array($args['service'], $args['region']),
            $config['endpoint']
        );

        return $config;
    }

    private function getKeys($region, $service)
    {
        return array("$region/$service", "$region/*", "*/$service", "*/*");
    }
}
