<?php

namespace Guzzle\Service;

/**
 * Interface used for loading configuration data (service descriptions, service builder configs, etc)
 *
 * If a loaded configuration data sets includes a top level key containing an 'includes' section, then the data in the
 * file will extend the merged result of all of the included config files.
 */
interface ConfigLoaderInterface
{
    /**
     * Loads configuration data and returns an array of the loaded result
     *
     * @param mixed $config  Data to load (filename or array of data)
     * @param array $options Array of options to use when loading
     *
     * @return mixed
     */
    public function load($config, array $options = array());
}
