<?php

declare(strict_types=1);

namespace Bamarni\Composer\Bin\Config;

use Composer\Config as ComposerConfig;
use Composer\Factory;
use Composer\Json\JsonFile;
use Composer\Json\JsonValidationException;
use Seld\JsonLint\ParsingException;

final class ConfigFactory
{
    /**
     * @throws JsonValidationException
     * @throws ParsingException
     */
    public static function createConfig(): ComposerConfig
    {
        $config = Factory::createConfig();

        $file = new JsonFile(Factory::getComposerFile());

        if (!$file->exists()) {
            return $config;
        }

        $file->validateSchema(JsonFile::LAX_SCHEMA);

        $config->merge($file->read());

        return $config;
    }

    private function __construct()
    {
    }
}
