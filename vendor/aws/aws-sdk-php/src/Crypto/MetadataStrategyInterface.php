<?php
namespace Aws\Crypto;

interface MetadataStrategyInterface
{
    /**
     * Places the information in the MetadataEnvelope to the strategy specific
     * location. Populates the PutObject arguments with any information
     * necessary for loading.
     *
     * @param MetadataEnvelope $envelope Encryption data to save according to
     *                                   the strategy.
     * @param array $args Starting arguments for PutObject.
     *
     * @return array Updated arguments for PutObject.
     */
    public function save(MetadataEnvelope $envelope, array $args);

    /**
     * Generates a MetadataEnvelope according to the specific strategy using the
     * passed arguments.
     *
     * @param array $args Arguments from Command and Result that contains
     *                    S3 Object information, relevant headers, and command
     *                    configuration.
     *
     * @return MetadataEnvelope
     */
    public function load(array $args);
}
