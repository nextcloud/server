<?php
namespace Aws\EndpointDiscovery;

class Configuration implements ConfigurationInterface
{
    private $cacheLimit;
    private $enabled;

    public function __construct($enabled, $cacheLimit = 1000)
    {
        $this->cacheLimit = filter_var($cacheLimit, FILTER_VALIDATE_INT);
        if ($this->cacheLimit == false || $this->cacheLimit < 1) {
            throw new \InvalidArgumentException(
                "'cache_limit' value must be a positive integer."
            );
        }

        // Unparsable $enabled flag errs on the side of disabling endpoint discovery
        $this->enabled = filter_var($enabled, FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * {@inheritdoc}
     */
    public function isEnabled()
    {
        return $this->enabled;
    }

    /**
     * {@inheritdoc}
     */
    public function getCacheLimit()
    {
        return $this->cacheLimit;
    }

    /**
     * {@inheritdoc}
     */
    public function toArray()
    {
        return [
            'enabled' => $this->isEnabled(),
            'cache_limit' => $this->getCacheLimit()
        ];
    }
}
