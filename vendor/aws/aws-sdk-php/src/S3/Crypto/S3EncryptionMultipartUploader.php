<?php
namespace Aws\S3\Crypto;

use Aws\Crypto\AbstractCryptoClient;
use Aws\Crypto\EncryptionTrait;
use Aws\Crypto\MetadataEnvelope;
use Aws\Crypto\Cipher\CipherBuilderTrait;
use Aws\S3\MultipartUploader;
use Aws\S3\S3ClientInterface;
use GuzzleHttp\Promise;

/**
 * Encapsulates the execution of a multipart upload of an encrypted object to S3.
 *
 * Legacy implementation using older encryption workflow. Use
 * S3EncryptionMultipartUploaderV2 if possible.
 *
 * @deprecated
 */
class S3EncryptionMultipartUploader extends MultipartUploader
{
    use CipherBuilderTrait;
    use CryptoParamsTrait;
    use EncryptionTrait;
    use UserAgentTrait;

    const CRYPTO_VERSION = '1n';

    /**
     * Returns if the passed cipher name is supported for encryption by the SDK.
     *
     * @param string $cipherName The name of a cipher to verify is registered.
     *
     * @return bool If the cipher passed is in our supported list.
     */
    public static function isSupportedCipher($cipherName)
    {
        return in_array($cipherName, AbstractCryptoClient::$supportedCiphers);
    }

    private $provider;
    private $instructionFileSuffix;
    private $strategy;

    /**
     * Creates a multipart upload for an S3 object after encrypting it.
     *
     * The required configuration options are as follows:
     *
     * - @MaterialsProvider: (MaterialsProvider) Provides Cek, Iv, and Cek
     *   encrypting/decrypting for encryption metadata.
     * - @CipherOptions: (array) Cipher options for encrypting data. A Cipher
     *   is required. Accepts the following options:
     *       - Cipher: (string) cbc|gcm
     *            See also: AbstractCryptoClient::$supportedCiphers. Note that
     *            cbc is deprecated and gcm should be used when possible.
     *       - KeySize: (int) 128|192|256
     *            See also: MaterialsProvider::$supportedKeySizes
     *       - Aad: (string) Additional authentication data. This option is
     *            passed directly to OpenSSL when using gcm. It is ignored when
     *            using cbc.
     * - bucket: (string) Name of the bucket to which the object is
     *   being uploaded.
     * - key: (string) Key to use for the object being uploaded.
     *
     * The optional configuration arguments are as follows:
     *
     * - @MetadataStrategy: (MetadataStrategy|string|null) Strategy for storing
     *   MetadataEnvelope information. Defaults to using a
     *   HeadersMetadataStrategy. Can either be a class implementing
     *   MetadataStrategy, a class name of a predefined strategy, or empty/null
     *   to default.
     * - @InstructionFileSuffix: (string|null) Suffix used when writing to an
     *   instruction file if an using an InstructionFileMetadataHandler was
     *   determined.
     * - acl: (string) ACL to set on the object being upload. Objects are
     *   private by default.
     * - before_complete: (callable) Callback to invoke before the
     *   `CompleteMultipartUpload` operation. The callback should have a
     *   function signature like `function (Aws\Command $command) {...}`.
     * - before_initiate: (callable) Callback to invoke before the
     *   `CreateMultipartUpload` operation. The callback should have a function
     *   signature like `function (Aws\Command $command) {...}`.
     * - before_upload: (callable) Callback to invoke before any `UploadPart`
     *   operations. The callback should have a function signature like
     *   `function (Aws\Command $command) {...}`.
     * - concurrency: (int, default=int(5)) Maximum number of concurrent
     *   `UploadPart` operations allowed during the multipart upload.
     * - params: (array) An array of key/value parameters that will be applied
     *   to each of the sub-commands run by the uploader as a base.
     *   Auto-calculated options will override these parameters. If you need
     *   more granularity over parameters to each sub-command, use the before_*
     *   options detailed above to update the commands directly.
     * - part_size: (int, default=int(5242880)) Part size, in bytes, to use when
     *   doing a multipart upload. This must between 5 MB and 5 GB, inclusive.
     * - state: (Aws\Multipart\UploadState) An object that represents the state
     *   of the multipart upload and that is used to resume a previous upload.
     *   When this option is provided, the `bucket`, `key`, and `part_size`
     *   options are ignored.
     *
     * @param S3ClientInterface $client Client used for the upload.
     * @param mixed             $source Source of the data to upload.
     * @param array             $config Configuration used to perform the upload.
     */
    public function __construct(
        S3ClientInterface $client,
        $source,
        array $config = []
    ) {
        $this->appendUserAgent($client, 'feat/s3-encrypt/' . self::CRYPTO_VERSION);
        $this->client = $client;
        $config['params'] = [];
        if (!empty($config['bucket'])) {
            $config['params']['Bucket'] = $config['bucket'];
        }
        if (!empty($config['key'])) {
            $config['params']['Key'] = $config['key'];
        }

        $this->provider = $this->getMaterialsProvider($config);
        unset($config['@MaterialsProvider']);

        $this->instructionFileSuffix = $this->getInstructionFileSuffix($config);
        unset($config['@InstructionFileSuffix']);
        $this->strategy = $this->getMetadataStrategy(
            $config,
            $this->instructionFileSuffix
        );
        if ($this->strategy === null) {
            $this->strategy = self::getDefaultStrategy();
        }
        unset($config['@MetadataStrategy']);

        $config['prepare_data_source'] = $this->getEncryptingDataPreparer();

        parent::__construct($client, $source, $config);
    }

    private static function getDefaultStrategy()
    {
        return new HeadersMetadataStrategy();
    }

    private function getEncryptingDataPreparer()
    {
        return function() {
            // Defer encryption work until promise is executed
            $envelope = new MetadataEnvelope();

            list($this->source, $params) = Promise\Create::promiseFor($this->encrypt(
                $this->source,
                $this->config['@cipheroptions'] ?: [],
                $this->provider,
                $envelope
            ))->then(
                function ($bodyStream) use ($envelope) {
                    $params = $this->strategy->save(
                        $envelope,
                        $this->config['params']
                    );
                    return [$bodyStream, $params];
                }
            )->wait();

            $this->source->rewind();
            $this->config['params'] = $params;
        };
    }
}