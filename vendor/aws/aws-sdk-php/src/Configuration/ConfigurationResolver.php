<?php

namespace Aws\Configuration;

class ConfigurationResolver
{
    const ENV_PROFILE = 'AWS_PROFILE';
    const ENV_CONFIG_FILE = 'AWS_CONFIG_FILE';

    public static $envPrefix = 'AWS_';

    /**
     * Generic configuration resolver that first checks for environment
     * variables, then checks for a specified profile in the environment-defined
     * config file location (env variable is 'AWS_CONFIG_FILE', file location
     * defaults to ~/.aws/config), then checks for the "default" profile in the
     * environment-defined config file location, and failing those uses a default
     * fallback value.
     *
     * @param string $key      Configuration key to be used when attempting
     *                         to retrieve value from the environment or ini file.
     * @param mixed $defaultValue
     * @param string $expectedType  The expected type of the retrieved value.
     * @param array $config additional configuration options.
     *
     * @return mixed
     */
    public static function resolve(
        $key,
        $defaultValue,
        $expectedType,
        $config = []
    )
    {
        $iniOptions = isset($config['ini_resolver_options'])
            ? $config['ini_resolver_options']
            : [];

        $envValue = self::env($key, $expectedType);
        if (!is_null($envValue)) {
            return $envValue;
        }

        if (!isset($config['use_aws_shared_config_files'])
            || $config['use_aws_shared_config_files'] != false
        ) {
            $iniValue = self::ini(
                $key,
                $expectedType,
                null,
                null,
                $iniOptions
            );
            if(!is_null($iniValue)) {
                return $iniValue;
            }
        }

        return $defaultValue;
    }

    /**
     * Resolves config values from environment variables.
     *
     * @param string $key      Configuration key to be used when attempting
     *                         to retrieve value from the environment.
     * @param string $expectedType  The expected type of the retrieved value.
     *
     * @return null | mixed
     */
    public static function env($key, $expectedType)
    {
        // Use config from environment variables, if available
        $envValue = getenv(self::$envPrefix . strtoupper($key));
        if (!empty($envValue)) {
            if ($expectedType) {
                $envValue = self::convertType($envValue, $expectedType);
            }
            return $envValue;
        }

        return null;
    }

    /**
     * Gets config values from a config file whose location
     * is specified by an environment variable 'AWS_CONFIG_FILE', defaulting to
     * ~/.aws/config if not specified
     *
     *
     * @param string $key      Configuration key to be used when attempting
     *                         to retrieve value from ini file.
     * @param string $expectedType  The expected type of the retrieved value.
     * @param string|null $profile  Profile to use. If not specified will use
     *                              the "default" profile.
     * @param string|null $filename If provided, uses a custom filename rather
     *                              than looking in the default directory.
     *
     * @return null | mixed
     */
    public static function ini(
        $key,
        $expectedType,
        $profile = null,
        $filename = null,
        $options = []
    ){
        $filename = $filename ?: (self::getDefaultConfigFilename());
        $profile = $profile ?: (getenv(self::ENV_PROFILE) ?: 'default');

        if (!@is_readable($filename)) {
            return null;
        }
        // Use INI_SCANNER_NORMAL instead of INI_SCANNER_TYPED for PHP 5.5 compatibility
        //TODO change after deprecation
        $data = @\Aws\parse_ini_file($filename, true, INI_SCANNER_NORMAL);

        if (isset($options['section'])
            && isset($options['subsection'])
            && isset($options['key']))
        {
            return self::retrieveValueFromIniSubsection(
                $data,
                $profile,
                $filename,
                $expectedType,
                $options
            );
        }

        if ($data === false
            || !isset($data[$profile])
            || !isset($data[$profile][$key])
        ) {
            return null;
        }

        // INI_SCANNER_NORMAL parses false-y values as an empty string
        if ($data[$profile][$key] === "") {
            if ($expectedType === 'bool') {
                $data[$profile][$key] = false;
            } elseif ($expectedType === 'int') {
                $data[$profile][$key] = 0;
            }
        }

        return self::convertType($data[$profile][$key], $expectedType);
    }

    /**
     * Gets the environment's HOME directory if available.
     *
     * @return null | string
     */
    private static function getHomeDir()
    {
        // On Linux/Unix-like systems, use the HOME environment variable
        if ($homeDir = getenv('HOME')) {
            return $homeDir;
        }

        // Get the HOMEDRIVE and HOMEPATH values for Windows hosts
        $homeDrive = getenv('HOMEDRIVE');
        $homePath = getenv('HOMEPATH');

        return ($homeDrive && $homePath) ? $homeDrive . $homePath : null;
    }

    /**
     * Gets default config file location from environment, falling back to aws
     * default location
     *
     * @return string
     */
    private static function getDefaultConfigFilename()
    {
        if ($filename = getenv(self::ENV_CONFIG_FILE)) {
            return $filename;
        }
        return self::getHomeDir() . '/.aws/config';
    }

    /**
     * Normalizes string values pulled out of ini files and
     * environment variables.
     *
     * @param string $value The value retrieved from the environment or
     *                      ini file.
     * @param $type $string The type that the value needs to be converted to.
     *
     * @return mixed
     */
    private static function convertType($value, $type)
    {
        if ($type === 'bool'
            && !is_null($convertedValue = \Aws\boolean_value($value))
        ) {
            return $convertedValue;
        }

        if ($type === 'int'
            && filter_var($value, FILTER_VALIDATE_INT)
        ) {
            $value = intVal($value);
        }
        return $value;
    }

    /**
     * Normalizes string values pulled out of ini files and
     * environment variables.
     *
     * @param array $data The data retrieved the ini file
     * @param string $profile The specified ini profile
     * @param string $filename The full path to the ini file
     * @param array $options Additional arguments passed to the configuration resolver
     *
     * @return mixed
     */
    private static function retrieveValueFromIniSubsection(
        $data,
        $profile,
        $filename,
        $expectedType,
        $options
    ){
        $section = $options['section'];
        if ($data === false
            || !isset($data[$profile][$section])
            || !isset($data["{$section} {$data[$profile][$section]}"])
        ) {
            return null;
        }

        $services_section = \Aws\parse_ini_section_with_subsections(
            $filename,
            "services {$data[$profile]['services']}"
        );

        if (!isset($services_section[$options['subsection']][$options['key']])
        ) {
            return null;
        }

        return self::convertType(
            $services_section[$options['subsection']][$options['key']],
            $expectedType
        );
    }
}
