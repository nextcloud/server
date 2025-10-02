<?php
namespace Aws\S3\Crypto;

use Aws\Crypto\DecryptionTrait;
use Aws\HashingStream;
use Aws\MetricsBuilder;
use Aws\PhpHash;
use Aws\Crypto\AbstractCryptoClient;
use Aws\Crypto\EncryptionTrait;
use Aws\Crypto\MetadataEnvelope;
use Aws\Crypto\MaterialsProvider;
use Aws\Crypto\Cipher\CipherBuilderTrait;
use Aws\S3\S3Client;
use GuzzleHttp\Promise;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Psr7;

/**
 * Provides a wrapper for an S3Client that supplies functionality to encrypt
 * data on putObject[Async] calls and decrypt data on getObject[Async] calls.
 *
 * Legacy implementation using older encryption workflow.
 *
 * AWS strongly recommends the upgrade to the S3EncryptionClientV2 (over the
 * S3EncryptionClient), as it offers updated data security best practices to our
 * customers who upgrade. S3EncryptionClientV2 contains breaking changes, so this
 * will require planning by engineering teams to migrate. New workflows should
 * just start with S3EncryptionClientV2.
 *
 * @deprecated
 */
class S3EncryptionClient extends AbstractCryptoClient
{
    use CipherBuilderTrait;
    use CryptoParamsTrait;
    use DecryptionTrait;
    use EncryptionTrait;
    use UserAgentTrait;

    const CRYPTO_VERSION = '1n';

    private $client;
    private $instructionFileSuffix;

    /**
     * @param S3Client $client The S3Client to be used for true uploading and
     *                         retrieving objects from S3 when using the
     *                         encryption client.
     * @param string|null $instructionFileSuffix Suffix for a client wide
     *                                           default when using instruction
     *                                           files for metadata storage.
     */
    public function __construct(
        S3Client $client,
        $instructionFileSuffix = null
    ) {
        $this->client = $client;
        $this->instructionFileSuffix = $instructionFileSuffix;
        MetricsBuilder::appendMetricsCaptureMiddleware(
            $this->client->getHandlerList(),
            MetricsBuilder::S3_CRYPTO_V1N
        );
    }

    private static function getDefaultStrategy()
    {
        return new HeadersMetadataStrategy();
    }

    /**
     * Encrypts the data in the 'Body' field of $args and promises to upload it
     * to the specified location on S3.
     *
     * @param array $args Arguments for encrypting an object and uploading it
     *                    to S3 via PutObject.
     *
     * The required configuration arguments are as follows:
     *
     * - @MaterialsProvider: (MaterialsProvider) Provides Cek, Iv, and Cek
     *   encrypting/decrypting for encryption metadata.
     * - @CipherOptions: (array) Cipher options for encrypting data. Only the
     *   Cipher option is required. Accepts the following:
     *       - Cipher: (string) cbc|gcm
     *            See also: AbstractCryptoClient::$supportedCiphers. Note that
     *            cbc is deprecated and gcm should be used when possible.
     *       - KeySize: (int) 128|192|256
     *            See also: MaterialsProvider::$supportedKeySizes
     *       - Aad: (string) Additional authentication data. This option is
     *            passed directly to OpenSSL when using gcm. It is ignored when
     *            using cbc. Note if you pass in Aad for gcm encryption, the
     *            PHP SDK will be able to decrypt the resulting object, but other
     *            AWS SDKs may not be able to do so.
     *
     * The optional configuration arguments are as follows:
     *
     * - @MetadataStrategy: (MetadataStrategy|string|null) Strategy for storing
     *   MetadataEnvelope information. Defaults to using a
     *   HeadersMetadataStrategy. Can either be a class implementing
     *   MetadataStrategy, a class name of a predefined strategy, or empty/null
     *   to default.
     * - @InstructionFileSuffix: (string|null) Suffix used when writing to an
     *   instruction file if using an InstructionFileMetadataHandler.
     *
     * @return PromiseInterface
     *
     * @throws \InvalidArgumentException Thrown when arguments above are not
     *                                   passed or are passed incorrectly.
     */
    public function putObjectAsync(array $args)
    {
        $provider = $this->getMaterialsProvider($args);
        unset($args['@MaterialsProvider']);

        $instructionFileSuffix = $this->getInstructionFileSuffix($args);
        unset($args['@InstructionFileSuffix']);

        $strategy = $this->getMetadataStrategy($args, $instructionFileSuffix);
        unset($args['@MetadataStrategy']);

        $envelope = new MetadataEnvelope();

        return Promise\Create::promiseFor($this->encrypt(
            Psr7\Utils::streamFor($args['Body']),
            $args['@CipherOptions'] ?: [],
            $provider,
            $envelope
        ))->then(
            function ($encryptedBodyStream) use ($args) {
                $hash = new PhpHash('sha256');
                $hashingEncryptedBodyStream = new HashingStream(
                    $encryptedBodyStream,
                    $hash,
                    self::getContentShaDecorator($args)
                );
                return [$hashingEncryptedBodyStream, $args];
            }
        )->then(
            function ($putObjectContents) use ($strategy, $envelope) {
                list($bodyStream, $args) = $putObjectContents;
                if ($strategy === null) {
                    $strategy = self::getDefaultStrategy();
                }

                $updatedArgs = $strategy->save($envelope, $args);
                $updatedArgs['Body'] = $bodyStream;
                return $updatedArgs;
            }
        )->then(
            function ($args) {
                unset($args['@CipherOptions']);
                return $this->client->putObjectAsync($args);
            }
        );
    }

    private static function getContentShaDecorator(&$args)
    {
        return function ($hash) use (&$args) {
            $args['ContentSHA256'] = bin2hex($hash);
        };
    }

    /**
     * Encrypts the data in the 'Body' field of $args and uploads it to the
     * specified location on S3.
     *
     * @param array $args Arguments for encrypting an object and uploading it
     *                    to S3 via PutObject.
     *
     * The required configuration arguments are as follows:
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
     *            using cbc. Note if you pass in Aad for gcm encryption, the
     *            PHP SDK will be able to decrypt the resulting object, but other
     *            AWS SDKs may not be able to do so.
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
     *
     * @return \Aws\Result PutObject call result with the details of uploading
     *                     the encrypted file.
     *
     * @throws \InvalidArgumentException Thrown when arguments above are not
     *                                   passed or are passed incorrectly.
     */
    public function putObject(array $args)
    {
        return $this->putObjectAsync($args)->wait();
    }

    /**
     * Promises to retrieve an object from S3 and decrypt the data in the
     * 'Body' field.
     *
     * @param array $args Arguments for retrieving an object from S3 via
     *                    GetObject and decrypting it.
     *
     * The required configuration argument is as follows:
     *
     * - @MaterialsProvider: (MaterialsProvider) Provides Cek, Iv, and Cek
     *   encrypting/decrypting for decryption metadata. May have data loaded
     *   from the MetadataEnvelope upon decryption.
     *
     * The optional configuration arguments are as follows:
     *
     * - SaveAs: (string) The path to a file on disk to save the decrypted
     *   object data. This will be handled by file_put_contents instead of the
     *   Guzzle sink.
     *
     * - @MetadataStrategy: (MetadataStrategy|string|null) Strategy for reading
     *   MetadataEnvelope information. Defaults to determining based on object
     *   response headers. Can either be a class implementing MetadataStrategy,
     *   a class name of a predefined strategy, or empty/null to default.
     * - @InstructionFileSuffix: (string) Suffix used when looking for an
     *   instruction file if an InstructionFileMetadataHandler is being used.
     * - @CipherOptions: (array) Cipher options for decrypting data. A Cipher
     *   is required. Accepts the following options:
     *       - Aad: (string) Additional authentication data. This option is
     *            passed directly to OpenSSL when using gcm. It is ignored when
     *            using cbc.
     *
     * @return PromiseInterface
     *
     * @throws \InvalidArgumentException Thrown when required arguments are not
     *                                   passed or are passed incorrectly.
     */
    public function getObjectAsync(array $args)
    {
        $provider = $this->getMaterialsProvider($args);
        unset($args['@MaterialsProvider']);

        $instructionFileSuffix = $this->getInstructionFileSuffix($args);
        unset($args['@InstructionFileSuffix']);

        $strategy = $this->getMetadataStrategy($args, $instructionFileSuffix);
        unset($args['@MetadataStrategy']);

        $saveAs = null;
        if (!empty($args['SaveAs'])) {
            $saveAs = $args['SaveAs'];
        }

        $promise = $this->client->getObjectAsync($args)
            ->then(
                function ($result) use (
                    $provider,
                    $instructionFileSuffix,
                    $strategy,
                    $args
                ) {
                    if ($strategy === null) {
                        $strategy = $this->determineGetObjectStrategy(
                            $result,
                            $instructionFileSuffix
                        );
                    }

                    $envelope = $strategy->load($args + [
                        'Metadata' => $result['Metadata']
                    ]);

                    $provider = $provider->fromDecryptionEnvelope($envelope);

                    $result['Body'] = $this->decrypt(
                        $result['Body'],
                        $provider,
                        $envelope,
                        isset($args['@CipherOptions'])
                            ? $args['@CipherOptions']
                            : []
                    );
                    return $result;
                }
            )->then(
                function ($result) use ($saveAs) {
                    if (!empty($saveAs)) {
                        file_put_contents(
                            $saveAs,
                            (string)$result['Body'],
                            LOCK_EX
                        );
                    }
                    return $result;
                }
            );

        return $promise;
    }

    /**
     * Retrieves an object from S3 and decrypts the data in the 'Body' field.
     *
     * @param array $args Arguments for retrieving an object from S3 via
     *                    GetObject and decrypting it.
     *
     * The required configuration argument is as follows:
     *
     * - @MaterialsProvider: (MaterialsProvider) Provides Cek, Iv, and Cek
     *   encrypting/decrypting for decryption metadata. May have data loaded
     *   from the MetadataEnvelope upon decryption.
     *
     * The optional configuration arguments are as follows:
     *
     * - SaveAs: (string) The path to a file on disk to save the decrypted
     *   object data. This will be handled by file_put_contents instead of the
     *   Guzzle sink.
     * - @InstructionFileSuffix: (string|null) Suffix used when looking for an
     *   instruction file if an InstructionFileMetadataHandler was detected.
     * - @CipherOptions: (array) Cipher options for encrypting data. A Cipher
     *   is required. Accepts the following options:
     *       - Aad: (string) Additional authentication data. This option is
     *            passed directly to OpenSSL when using gcm. It is ignored when
     *            using cbc.
     *
     * @return \Aws\Result GetObject call result with the 'Body' field
     *                     wrapped in a decryption stream with its metadata
     *                     information.
     *
     * @throws \InvalidArgumentException Thrown when arguments above are not
     *                                   passed or are passed incorrectly.
     */
    public function getObject(array $args)
    {
        return $this->getObjectAsync($args)->wait();
    }
}
