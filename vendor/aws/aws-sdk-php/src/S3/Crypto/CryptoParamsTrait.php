<?php
namespace Aws\S3\Crypto;

use Aws\Crypto\MaterialsProvider;
use Aws\Crypto\MetadataEnvelope;
use Aws\Crypto\MetadataStrategyInterface;

trait CryptoParamsTrait
{
    protected function getMaterialsProvider(array $args)
    {
        if ($args['@MaterialsProvider'] instanceof MaterialsProvider) {
            return $args['@MaterialsProvider'];
        }

        throw new \InvalidArgumentException('An instance of MaterialsProvider'
            . ' must be passed in the "MaterialsProvider" field.');
    }

    protected function getInstructionFileSuffix(array $args)
    {
        return !empty($args['@InstructionFileSuffix'])
            ? $args['@InstructionFileSuffix']
            : $this->instructionFileSuffix;
    }

    protected function determineGetObjectStrategy(
        $result,
        $instructionFileSuffix
    ) {
        if (isset($result['Metadata'][MetadataEnvelope::CONTENT_KEY_V2_HEADER])) {
            return new HeadersMetadataStrategy();
        }

        return new InstructionFileMetadataStrategy(
            $this->client,
            $instructionFileSuffix
        );
    }

    protected function getMetadataStrategy(array $args, $instructionFileSuffix)
    {
        if (!empty($args['@MetadataStrategy'])) {
            if ($args['@MetadataStrategy'] instanceof MetadataStrategyInterface) {
                return $args['@MetadataStrategy'];
            }

            if (is_string($args['@MetadataStrategy'])) {
                switch ($args['@MetadataStrategy']) {
                    case HeadersMetadataStrategy::class:
                        return new HeadersMetadataStrategy();
                    case InstructionFileMetadataStrategy::class:
                        return new InstructionFileMetadataStrategy(
                            $this->client,
                            $instructionFileSuffix
                        );
                    default:
                        throw new \InvalidArgumentException('Could not match the'
                            . ' specified string in "MetadataStrategy" to a'
                            . ' predefined strategy.');
                }
            } else {
                throw new \InvalidArgumentException('The metadata strategy that'
                    . ' was passed to "MetadataStrategy" was unrecognized.');
            }
        } elseif ($instructionFileSuffix) {
            return new InstructionFileMetadataStrategy(
                $this->client,
                $instructionFileSuffix
            );
        }

        return null;
    }
}
