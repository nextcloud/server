<?php
namespace Aws\S3\Crypto;

use \Aws\Crypto\MetadataStrategyInterface;
use \Aws\Crypto\MetadataEnvelope;
use \Aws\S3\S3Client;

/**
 * Stores and reads encryption MetadataEnvelope information in a file on Amazon
 * S3.
 *
 * A file with the contents of a MetadataEnvelope will be created or read from
 * alongside the base file on Amazon S3. The provided client will be used for
 * reading or writing this object. A specified suffix (default of '.instruction'
 * will be applied to each of the operations involved with the instruction file.
 *
 * If there is a failure after an instruction file has been uploaded, it will
 * not be automatically deleted.
 */
class InstructionFileMetadataStrategy implements MetadataStrategyInterface
{
    const DEFAULT_FILE_SUFFIX = '.instruction';

    private $client;
    private $suffix;

    /**
     * @param S3Client $client Client for use in uploading the instruction file.
     * @param string|null $suffix Optional override suffix for instruction file
     *                            object keys.
     */
    public function __construct(S3Client $client, $suffix = null)
    {
        $this->suffix = empty($suffix)
            ? self::DEFAULT_FILE_SUFFIX
            : $suffix;
        $this->client = $client;
    }

    /**
     * Places the information in the MetadataEnvelope to a location on S3.
     *
     * @param MetadataEnvelope $envelope Encryption data to save according to
     *                                   the strategy.
     * @param array $args Starting arguments for PutObject, used for saving
     *                    extra the instruction file.
     *
     * @return array Updated arguments for PutObject.
     */
    public function save(MetadataEnvelope $envelope, array $args)
    {
        $this->client->putObject([
            'Bucket' => $args['Bucket'],
            'Key' => $args['Key'] . $this->suffix,
            'Body' => json_encode($envelope)
        ]);

        return $args;
    }

    /**
     * Uses the strategy's client to retrieve the instruction file from S3 and generates
     * a MetadataEnvelope from its contents.
     *
     * @param array $args Arguments from Command and Result that contains
     *                    S3 Object information, relevant headers, and command
     *                    configuration.
     *
     * @return MetadataEnvelope
     */
    public function load(array $args)
    {
        $result = $this->client->getObject([
            'Bucket' => $args['Bucket'],
            'Key' => $args['Key'] . $this->suffix
        ]);

        $metadataHeaders = json_decode($result['Body'], true);
        $envelope = new MetadataEnvelope();
        $constantValues = MetadataEnvelope::getConstantValues();

        foreach ($constantValues as $constant) {
            if (!empty($metadataHeaders[$constant])) {
                $envelope[$constant] = $metadataHeaders[$constant];
            }
        }

        return $envelope;
    }
}
