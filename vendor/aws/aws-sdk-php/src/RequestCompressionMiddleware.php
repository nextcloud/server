<?php
namespace Aws;

use GuzzleHttp\Psr7;
use Psr\Http\Message\RequestInterface;

/**
 * Used to compress request payloads if the service/operation support it.
 *
 * IMPORTANT: this middleware must be added after the "build" step.
 *
 * @internal
 */
class RequestCompressionMiddleware
{
    private $api;
    private $minimumCompressionSize;
    private $nextHandler;
    private $encodings;
    private $encoding;
    private $encodingMap = [
        'gzip' => 'gzencode'
    ];

    /**
     * Create a middleware wrapper function.
     *
     * @return callable
     */
    public static function wrap(array $config)
    {
        return function (callable $handler) use ($config) {
            return new self($handler, $config);
        };
    }

    public function __construct(callable $nextHandler, $config)
    {
        $this->minimumCompressionSize = $this->determineMinimumCompressionSize($config);
        $this->api = $config['api'];
        $this->nextHandler = $nextHandler;
    }

    public function __invoke(CommandInterface $command, RequestInterface $request)
    {
        if (isset($command['@request_min_compression_size_bytes'])
            && is_int($command['@request_min_compression_size_bytes'])
            && $this->isValidCompressionSize($command['@request_min_compression_size_bytes'])
        ) {
            $this->minimumCompressionSize = $command['@request_min_compression_size_bytes'];
        }
        $nextHandler = $this->nextHandler;
        $operation = $this->api->getOperation($command->getName());
        $compressionInfo = isset($operation['requestcompression'])
            ? $operation['requestcompression']
            : null;

        if (!$this->shouldCompressRequestBody(
            $compressionInfo,
            $command,
            $operation,
            $request
        )) {
            return $nextHandler($command, $request);
        }

        $this->encodings = $compressionInfo['encodings'];
        $request = $this->compressRequestBody($request);

        // Capture request compression metric
        $command->getMetricsBuilder()->identifyMetricByValueAndAppend(
            'request_compression',
            $request->getHeaderLine('content-encoding')
        );

        return $nextHandler($command, $request);
    }

    private function compressRequestBody(
        RequestInterface $request
    ) {
        $fn = $this->determineEncoding();
        if (is_null($fn)) {
            return $request;
        }

        $body = $request->getBody()->getContents();
        $compressedBody = $fn($body);

        return $request->withBody(Psr7\Utils::streamFor($compressedBody))
            ->withHeader('content-encoding', $this->encoding);
    }

    private function determineEncoding()
    {
        foreach ($this->encodings as $encoding) {
            if (isset($this->encodingMap[$encoding])) {
                $this->encoding = $encoding;
                return $this->encodingMap[$encoding];
            }
        }
        return null;
    }

    private function shouldCompressRequestBody(
        $compressionInfo,
        $command,
        $operation,
        $request
    ){
        if ($compressionInfo) {
            if (isset($command['@disable_request_compression'])
                && $command['@disable_request_compression'] === true
            ) {
                return false;
            } elseif ($this->hasStreamingTraitWithoutRequiresLength($command, $operation)
            ) {
                return true;
            }

            $requestBodySize = $request->hasHeader('content-length')
                ? (int) $request->getHeaderLine('content-length')
                : $request->getBody()->getSize();

            if ($requestBodySize >= $this->minimumCompressionSize) {
                return true;
            }
        }
        return false;
    }

    private function hasStreamingTraitWithoutRequiresLength($command, $operation)
    {
        foreach ($operation->getInput()->getMembers() as $name => $member) {
            if (isset($command[$name])
                && !empty($member['streaming'])
                && empty($member['requiresLength'])
            ){
                return true;
            }
        }
        return false;
    }

    private function determineMinimumCompressionSize($config) {
        if (is_callable($config['request_min_compression_size_bytes'])) {
            $minCompressionSz = $config['request_min_compression_size_bytes']();
        } else {
            $minCompressionSz = $config['request_min_compression_size_bytes'];
        }

        if ($this->isValidCompressionSize($minCompressionSz)) {
            return $minCompressionSz;
        }
    }

    private function isValidCompressionSize($compressionSize)
    {
        if (is_numeric($compressionSize)
            && ($compressionSize >= 0 && $compressionSize <= 10485760)
        ) {
            return true;
        }

        throw new \InvalidArgumentException(
            'The minimum request compression size must be a '
            . 'non-negative integer value between 0 and 10485760 bytes, inclusive.'
        );
    }
}
