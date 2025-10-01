<?php
namespace Aws\S3\Crypto;

use Aws\Crypto\DecryptionTraitV2;
use Aws\Exception\CryptoException;
use Aws\HashingStream;
use Aws\MetricsBuilder;
use Aws\PhpHash;
use Aws\Crypto\AbstractCryptoClientV2;
use Aws\Crypto\EncryptionTraitV2;
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
 * AWS strongly recommends the upgrade to the S3EncryptionClientV2 (over the
 * S3EncryptionClient), as it offers updated data security best practices to our
 * customers who upgrade. S3EncryptionClientV2 contains breaking changes, so this
 * will require planning by engineering teams to migrate. New workflows should
 * just start with S3EncryptionClientV2.
 *
 * Note that for PHP versions of < 7.1, this class uses an AES-GCM polyfill
 * for encryption since there is no native PHP support. The performance for large
 * inputs will be a lot slower than for PHP 7.1+, so upgrading older PHP version
 * environments may be necessary to use this effectively.
 *
 * Example write path:
 *
 * <code>
 * use Aws\Crypto\KmsMaterialsProviderV2;
 * use Aws\S3\Crypto\S3EncryptionClientV2;
 * use Aws\S3\S3Client;
 *
 * $encryptionClient = new S3EncryptionClientV2(
 *     new S3Client([
 *         'region' => 'us-west-2',
 *         'version' => 'latest'
 *     ])
 * );
 * $materialsProvider = new KmsMaterialsProviderV2(
 *     new KmsClient([
 *         'profile' => 'default',
 *         'region' => 'us-east-1',
 *         'version' => 'latest',
 *     ],
 *    'your-kms-key-id'
 * );
 *
 * $encryptionClient->putObject([
 *     '@MaterialsProvider' => $materialsProvider,
 *     '@CipherOptions' => [
 *         'Cipher' => 'gcm',
 *         'KeySize' => 256,
 *     ],
 *     '@KmsEncryptionContext' => ['foo' => 'bar'],
 *     'Bucket' => 'your-bucket',
 *     'Key' => 'your-key',
 *     'Body' => 'your-encrypted-data',
 * ]);
 * </code>
 *
 * Example read call (using objects from previous example):
 *
 * <code>
 * $encryptionClient->getObject([
 *     '@MaterialsProvider' => $materialsProvider,
 *     '@CipherOptions' => [
 *         'Cipher' => 'gcm',
 *         'KeySize' => 256,
 *     ],
 *     'Bucket' => 'your-bucket',
 *     'Key' => 'your-key',
 * ]);
 * </code>
 */
class S3EncryptionClientV2 extends AbstractCryptoClientV2
{
    use CipherBuilderTrait;
    use CryptoParamsTraitV2;
    use DecryptionTraitV2;
    use EncryptionTraitV2;
    use UserAgentTrait;

    const CRYPTO_VERSION = '2.1';

    private $client;
    private $instructionFileSuffix;
    private $legacyWarningCount;

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
        $this->legacyWarningCount = 0;
        MetricsBuilder::appendMetricsCaptureMiddleware(
            $this->client->getHandlerList(),
            MetricsBuilder::S3_CRYPTO_V2
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
     * Note that for PHP versions of < 7.1, this operation uses an AES-GCM
     * polyfill for encryption since there is no native PHP support. The
     * performance for large inputs will be a lot slower than for PHP 7.1+, so
     * upgrading older PHP version environments may be necessary to use this
     * effectively.
     *
     * @param array $args Arguments for encrypting an object and uploading it
     *                    to S3 via PutObject.
     *
     * The required configuration arguments are as follows:
     *
     * - @MaterialsProvider: (MaterialsProviderV2) Provides Cek, Iv, and Cek
     *   encrypting/decrypting for encryption metadata.
     * - @CipherOptions: (array) Cipher options for encrypting data. Only the
     *   Cipher option is required. Accepts the following:
     *       - Cipher: (string) gcm
     *            See also: AbstractCryptoClientV2::$supportedCiphers
     *       - KeySize: (int) 128|256
     *            See also: MaterialsProvider::$supportedKeySizes
     *       - Aad: (string) Additional authentication data. This option is
     *            passed directly to OpenSSL when using gcm. Note if you pass in
     *            Aad, the PHP SDK will be able to decrypt the resulting object,
     *            but other AWS SDKs may not be able to do so.
     * - @KmsEncryptionContext: (array) Only required if using
     *   KmsMaterialsProviderV2. An associative array of key-value
     *   pairs to be added to the encryption context for KMS key encryption. An
     *   empty array may be passed if no additional context is desired.
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
            $args,
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
     * Note that for PHP versions of < 7.1, this operation uses an AES-GCM
     * polyfill for encryption since there is no native PHP support. The
     * performance for large inputs will be a lot slower than for PHP 7.1+, so
     * upgrading older PHP version environments may be necessary to use this
     * effectively.
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
     *       - Cipher: (string) gcm
     *            See also: AbstractCryptoClientV2::$supportedCiphers
     *       - KeySize: (int) 128|256
     *            See also: MaterialsProvider::$supportedKeySizes
     *       - Aad: (string) Additional authentication data. This option is
     *            passed directly to OpenSSL when using gcm. Note if you pass in
     *            Aad, the PHP SDK will be able to decrypt the resulting object,
     *            but other AWS SDKs may not be able to do so.
     * - @KmsEncryptionContext: (array) Only required if using
     *   KmsMaterialsProviderV2. An associative array of key-value
     *   pairs to be added to the encryption context for KMS key encryption. An
     *   empty array may be passed if no additional context is desired.
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
     * - @MaterialsProvider: (MaterialsProviderInterface) Provides Cek, Iv, and Cek
     *   encrypting/decrypting for decryption metadata. May have data loaded
     *   from the MetadataEnvelope upon decryption.
     * - @SecurityProfile: (string) Must be set to 'V2' or 'V2_AND_LEGACY'.
     *      - 'V2' indicates that only objects encrypted with S3EncryptionClientV2
     *        content encryption and key wrap schemas are able to be decrypted.
     *      - 'V2_AND_LEGACY' indicates that objects encrypted with both
     *        S3EncryptionClientV2 and older legacy encryption clients are able
     *        to be decrypted.
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
     * - @KmsAllowDecryptWithAnyCmk: (bool) This allows decryption with
     *   KMS materials for any KMS key ID, instead of needing the KMS key ID to
     *   be specified and provided to the decrypt operation. Ignored for non-KMS
     *   materials providers. Defaults to false.
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

        if (!isset($args['@SecurityProfile'])
            || !in_array($args['@SecurityProfile'], self::$supportedSecurityProfiles)
        ) {
            throw new CryptoException("@SecurityProfile is required and must be"
                . " set to 'V2' or 'V2_AND_LEGACY'");
        }

        // Only throw this legacy warning once per client
        if (in_array($args['@SecurityProfile'], self::$legacySecurityProfiles)
            && $this->legacyWarningCount < 1
        ) {
            $this->legacyWarningCount++;
            trigger_error(
                "This S3 Encryption Client operation is configured to"
                    . " read encrypted data with legacy encryption modes. If you"
                    . " don't have objects encrypted with these legacy modes,"
                    . " you should disable support for them to enhance security. ",
                E_USER_WARNING
            );
        }

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

                    $result['Body'] = $this->decrypt(
                        $result['Body'],
                        $provider,
                        $envelope,
                        $args
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
     * - @MaterialsProvider: (MaterialsProviderInterface) Provides Cek, Iv, and Cek
     *   encrypting/decrypting for decryption metadata. May have data loaded
     *   from the MetadataEnvelope upon decryption.
     * - @SecurityProfile: (string) Must be set to 'V2' or 'V2_AND_LEGACY'.
     *      - 'V2' indicates that only objects encrypted with S3EncryptionClientV2
     *        content encryption and key wrap schemas are able to be decrypted.
     *      - 'V2_AND_LEGACY' indicates that objects encrypted with both
     *        S3EncryptionClientV2 and older legacy encryption clients are able
     *        to be decrypted.
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
     * - @KmsAllowDecryptWithAnyCmk: (bool) This allows decryption with
     *   KMS materials for any KMS key ID, instead of needing the KMS key ID to
     *   be specified and provided to the decrypt operation. Ignored for non-KMS
     *   materials providers. Defaults to false.
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
