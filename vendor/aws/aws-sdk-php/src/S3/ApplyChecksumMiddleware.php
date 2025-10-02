<?php
namespace Aws\S3;

use Aws\Api\Service;
use Aws\Api\Shape;
use Aws\CommandInterface;
use Aws\MetricsBuilder;
use GuzzleHttp\Psr7;
use InvalidArgumentException;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\StreamInterface;

/**
 * Apply required or optional checksums to requests before sending.
 *
 * IMPORTANT: This middleware must be added after the "build" step.
 *
 * @internal
 */
class ApplyChecksumMiddleware
{
    use CalculatesChecksumTrait;

    public const DEFAULT_CALCULATION_MODE = 'when_supported';
    public const DEFAULT_ALGORITHM = 'crc32';

    /**
     * @var true[]
     *
     * S3 Operations for which pre-calculated SHA256
     * Checksums can be added to the command
     */
    public static $sha256 = [
        'PutObject' => true,
        'UploadPart' => true,
    ];

    /** @var Service */
    private $api;

    /** @var array */
    private $config;

    /** @var callable */
    private $nextHandler;

    /**
     * Create a middleware wrapper function.
     *
     * @param Service $api
     * @return callable
     */
    public static function wrap(Service $api, array $config = [])
    {
        return function (callable $handler) use ($api, $config) {
            return new self($handler, $api, $config);
        };
    }

    public function __construct(
        callable $nextHandler,
        Service $api, 
        array $config = []
    )
    {
        $this->api = $api;
        $this->nextHandler = $nextHandler;
        $this->config = $config;
    }

    public function __invoke(
        CommandInterface $command,
        RequestInterface $request
    ) {
        $next = $this->nextHandler;
        $name = $command->getName();
        $body = $request->getBody();
        $operation = $this->api->getOperation($name);
        $mode = $this->config['request_checksum_calculation']
            ?? self::DEFAULT_CALCULATION_MODE;

        $command->getMetricsBuilder()->identifyMetricByValueAndAppend(
            'request_checksum_calculation',
            $mode
        );

        // Trigger warning if AddContentMD5 is specified for PutObject or UploadPart
        $this->handleDeprecatedAddContentMD5($command);

        $checksumInfo = $operation['httpChecksum'] ?? [];
        $checksumMemberName = $checksumInfo['requestAlgorithmMember'] ?? '';
        $checksumMember = !empty($checksumMemberName)
            ? $operation->getInput()->getMember($checksumMemberName)
            : null;
        $checksumRequired = $checksumInfo['requestChecksumRequired'] ?? false;
        $requestedAlgorithm = $command[$checksumMemberName] ?? null;

        $shouldAddChecksum = $this->shouldAddChecksum(
            $mode,
            $checksumRequired,
            $checksumMember,
            $requestedAlgorithm
        );
        if ($shouldAddChecksum) {
            if (!$this->hasAlgorithmHeader($request)) {
                $supportedAlgorithms =  array_map('strtolower', $checksumMember['enum'] ?? []);
                $algorithm = $this->determineChecksumAlgorithm(
                    $supportedAlgorithms,
                    $requestedAlgorithm,
                    $checksumMemberName
                );
                $request = $this->addAlgorithmHeader($algorithm, $request, $body);

                $command->getMetricsBuilder()->identifyMetricByValueAndAppend(
                    'request_checksum',
                    $algorithm
                );
            }
        }

        // Set the content hash header if ContentSHA256 is provided
        if (isset(self::$sha256[$name]) && $command['ContentSHA256']) {
            $request = $request->withHeader(
                'X-Amz-Content-Sha256',
                $command['ContentSHA256']
            );
            $command->getMetricsBuilder()->append(
                MetricsBuilder::FLEXIBLE_CHECKSUMS_REQ_SHA256
            );
        }

        return $next($command, $request);
    }

    /**
     * @param CommandInterface $command
     *
     * @return void
     */
    private function handleDeprecatedAddContentMD5(CommandInterface $command): void
    {
        if (!empty($command['AddContentMD5'])) {
            trigger_error(
                'S3 no longer supports MD5 checksums. ' .
                'A CRC32 checksum will be computed and applied on your behalf.',
                E_USER_DEPRECATED
            );
            $command['ChecksumAlgorithm'] = self::DEFAULT_ALGORITHM;
        }
    }

    /**
     * @param string $mode
     * @param Shape|null $checksumMember
     * @param string $name
     * @param bool $checksumRequired
     * @param string|null $requestedAlgorithm
     *
     * @return bool
     */
    private function shouldAddChecksum(
        string $mode,
        bool $checksumRequired,
        ?Shape $checksumMember,
        ?string $requestedAlgorithm
    ): bool
    {
        return ($mode === 'when_supported' && $checksumMember)
            || ($mode === 'when_required'
                && ($checksumRequired || ($checksumMember && $requestedAlgorithm)));
    }

    /**
     * @param Shape|null $checksumMember
     * @param string|null $requestedAlgorithm
     * @param string|null $checksumMemberName
     *
     * @return string
     */
    private function determineChecksumAlgorithm(
        array $supportedAlgorithms,
        ?string $requestedAlgorithm,
        ?string $checksumMemberName
    ): string
    {
        $algorithm = self::DEFAULT_ALGORITHM;

        if ($requestedAlgorithm) {
            $requestedAlgorithm = strtolower($requestedAlgorithm);
            if (!in_array($requestedAlgorithm, $supportedAlgorithms)) {
                throw new InvalidArgumentException(
                    "Unsupported algorithm supplied for input variable {$checksumMemberName}. " .
                    "Supported checksums for this operation include: "
                    . implode(", ", $supportedAlgorithms) . "."
                );
            }
            $algorithm = $requestedAlgorithm;
        }

        return $algorithm;
    }

    /**
     * @param string $requestedAlgorithm
     * @param RequestInterface $request
     * @param StreamInterface $body
     *
     * @return RequestInterface
     */
    private function addAlgorithmHeader(
        string $requestedAlgorithm,
        RequestInterface $request,
        StreamInterface $body
    ): RequestInterface
    {
        $headerName = "x-amz-checksum-{$requestedAlgorithm}";
        if (!$request->hasHeader($headerName)) {
            $encoded = self::getEncodedValue($requestedAlgorithm, $body);
            $request = $request->withHeader($headerName, $encoded);
        }

        return $request;
    }

    /**
     * @param RequestInterface $request
     *
     * @return bool
     */
    private function hasAlgorithmHeader(RequestInterface $request): bool
    {
        $headers = $request->getHeaders();

        foreach ($headers as $name => $values) {
            if (stripos($name, 'x-amz-checksum-') === 0) {
                return true;
            }
        }

        return false;
    }
}
