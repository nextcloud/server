<?php
namespace Aws;

interface ConfigurationProviderInterface
{
    /**
     * Create a default config provider
     *
     * @param array $config
     * @return callable
     */
    public static function defaultProvider(array $config = []);
}
