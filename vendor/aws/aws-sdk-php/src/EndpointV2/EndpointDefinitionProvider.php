<?php

namespace Aws\EndpointV2;

/**
 * Provides Endpoint-related artifacts used for endpoint resolution
 * and testing.
 */
class EndpointDefinitionProvider
{
    public static function getEndpointRuleset($service, $apiVersion, $baseDir = null)
    {
        return self::getData($service, $apiVersion, 'ruleset', $baseDir);
    }

    public static function getEndpointTests($service, $apiVersion, $baseDir = null)
    {
        return self::getData($service, $apiVersion, 'tests', $baseDir);
    }

    public static function getPartitions()
    {
        $basePath = __DIR__ . '/../data';
        $file = '/partitions.json';

        if (file_exists($basePath . $file . '.php')) {
           return require($basePath . $file . '.php');
        } else {
            return json_decode(file_get_contents($basePath . $file));
        }
    }

    private static function getData($service, $apiVersion, $type, $baseDir)
    {
        $basePath = $baseDir ? $baseDir :  __DIR__ . '/../data';
        $serviceDir = $basePath . "/{$service}";
        if (!is_dir($serviceDir)) {
            throw new \InvalidArgumentException(
                'Invalid service name.'
            );
        }

        if ($apiVersion === 'latest') {
            $apiVersion = self::getLatest($service);
        }

        $rulesetPath = $serviceDir . '/' . $apiVersion;
        if (!is_dir($rulesetPath)) {
            throw new \InvalidArgumentException(
                'Invalid api version.'
            );
        }
        $fileName = $type === 'tests' ? '/endpoint-tests-1' : '/endpoint-rule-set-1';

        if (file_exists($rulesetPath . $fileName . '.json.php')) {
            return require($rulesetPath . $fileName . '.json.php');
        } elseif (file_exists($rulesetPath . $fileName . '.json')) {
            return json_decode(file_get_contents($rulesetPath . $fileName . '.json'), true);
        } else {
            throw new \InvalidArgumentException(
                'Specified ' . $type . ' endpoint file for ' . $service . ' with api version ' . $apiVersion . ' does not exist.'
            );
        }
    }

    private static function getLatest($service)
    {
        $manifest = \Aws\manifest();
        return $manifest[$service]['versions']['latest'];
    }
}