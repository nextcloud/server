<?php
namespace Aws;

/**
 * Loads JSON files and compiles them into PHP arrays.
 *
 * @internal Please use json_decode instead.
 * @deprecated
 */
class JsonCompiler
{
    const CACHE_ENV = 'AWS_PHP_CACHE_DIR';

    /**
     * Loads a JSON file from cache or from the JSON file directly.
     *
     * @param string $path Path to the JSON file to load.
     *
     * @return mixed
     */
    public function load($path)
    {
        return load_compiled_json($path);
    }
}
