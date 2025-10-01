<?php
namespace Aws\Script\Composer;

use Composer\Script\Event;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;

class Composer
{

    public static function removeUnusedServicesInDev(Event $event, ?Filesystem $filesystem = null)
    {
        self::removeUnusedServicesWithConfig($event, $filesystem, true);
    }

    public static function removeUnusedServices(Event $event, ?Filesystem $filesystem = null)
    {
        self::removeUnusedServicesWithConfig($event, $filesystem, false);
    }

    private static function removeUnusedServicesWithConfig(Event $event, ?Filesystem $filesystem = null, $isDev = false)
    {
        if ($isDev && !$event->isDevMode()){
            return;
        }

        $composer = $event->getComposer();
        $extra = $composer->getPackage()->getExtra();
        $listedServices = isset($extra['aws/aws-sdk-php'])
            ? $extra['aws/aws-sdk-php']
            : [];

        if ($listedServices) {
            $serviceMapping = self::buildServiceMapping();
            self::verifyListedServices($serviceMapping, $listedServices);
            $filesystem = $filesystem ?: new Filesystem();
            $vendorPath = $composer->getConfig()->get('vendor-dir');
            self::removeServiceDirs(
                $event,
                $filesystem,
                $serviceMapping,
                $listedServices,
                $vendorPath
            );
        } else {
            throw new \InvalidArgumentException(
                'There are no services listed. Did you intend to use this script?'
            );
        }
    }

    public static function buildServiceMapping()
    {
        $serviceMapping = [];
        $manifest = require(__DIR__ . '/../../data/manifest.json.php');

        foreach ($manifest as $service => $attributes) {
            $serviceMapping[$attributes['namespace']] = $service;
        }

        return $serviceMapping;
    }

    private static function verifyListedServices($serviceMapping, $listedServices)
    {
        foreach ($listedServices as $serviceToKeep) {
            if (!isset($serviceMapping[$serviceToKeep])) {
                throw new \InvalidArgumentException(
                    "'$serviceToKeep' is not a valid AWS service namespace. Please check spelling and casing."
                );
            }
        }
    }

    private static function removeServiceDirs(
        $event,
        $filesystem,
        $serviceMapping,
        $listedServices,
        $vendorPath
    ) {
        $unsafeForDeletion = ['Kms', 'S3', 'SSO', 'SSOOIDC', 'Sts'];
        if (in_array('DynamoDbStreams', $listedServices)) {
            $unsafeForDeletion[] = 'DynamoDb';
        }

        $clientPath = $vendorPath . '/aws/aws-sdk-php/src/';
        $modelPath = $clientPath . 'data/';
        $deleteCount = 0;

        foreach ($serviceMapping as $clientName => $modelName) {
            if (!in_array($clientName, $listedServices) &&
                !in_array($clientName, $unsafeForDeletion)
            ) {
                $clientDir = $clientPath . $clientName;
                $modelDir = $modelPath . $modelName;

                if ($filesystem->exists([$clientDir, $modelDir])) {
                    $attempts = 3;
                    $delay = 2;

                    while ($attempts) {
                        try {
                            $filesystem->remove([$clientDir, $modelDir]);
                            $deleteCount++;
                            break;
                        } catch (IOException $e) {
                            $attempts--;

                            if (!$attempts) {
                                throw new IOException(
                                    "Removal failed after several attempts. Last error: " . $e->getMessage()
                                );
                            } else {
                                sleep($delay);
                                $event->getIO()->write(
                                    "Error encountered: " . $e->getMessage() . ". Retrying..."
                                );
                                $delay += 2;
                            }
                    }
                }

                }
            }
        }
        $event->getIO()->write(
            "Removed $deleteCount AWS service" . ($deleteCount === 1 ? '' : 's')
        );
    }
}