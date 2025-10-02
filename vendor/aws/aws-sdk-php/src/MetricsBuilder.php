<?php

namespace Aws;

use Aws\Credentials\CredentialsInterface;
use Aws\Credentials\CredentialSources;

/**
 * A placeholder for gathering metrics in a request.
 *
 * @internal
 */
final class MetricsBuilder
{
    const WAITER = "B";
    const PAGINATOR = "C";
    const RETRY_MODE_LEGACY = "D";
    const RETRY_MODE_STANDARD = "E";
    const RETRY_MODE_ADAPTIVE = "F";
    const S3_TRANSFER = "G";
    const S3_CRYPTO_V1N = "H";
    const S3_CRYPTO_V2 = "I";
    const S3_EXPRESS_BUCKET = "J";
    const GZIP_REQUEST_COMPRESSION = "L";
    const ENDPOINT_OVERRIDE = "N";
    const ACCOUNT_ID_ENDPOINT = "O";
    const ACCOUNT_ID_MODE_PREFERRED = "P";
    const ACCOUNT_ID_MODE_DISABLED = "Q";
    const ACCOUNT_ID_MODE_REQUIRED = "R";
    const SIGV4A_SIGNING = "S";
    const RESOLVED_ACCOUNT_ID = "T";
    const FLEXIBLE_CHECKSUMS_REQ_CRC32 = "U";
    const FLEXIBLE_CHECKSUMS_REQ_CRC32C = "V";
    const FLEXIBLE_CHECKSUMS_REQ_CRC64 = "W";
    const FLEXIBLE_CHECKSUMS_REQ_SHA1 = "X";
    const FLEXIBLE_CHECKSUMS_REQ_SHA256 = "Y";
    const FLEXIBLE_CHECKSUMS_REQ_WHEN_SUPPORTED = "Z";
    const FLEXIBLE_CHECKSUMS_REQ_WHEN_REQUIRED = "a";
    const FLEXIBLE_CHECKSUMS_RES_WHEN_SUPPORTED = "b";
    const FLEXIBLE_CHECKSUMS_RES_WHEN_REQUIRED = "c";
    const CREDENTIALS_CODE = "e";
    const CREDENTIALS_ENV_VARS = "g";
    const CREDENTIALS_ENV_VARS_STS_WEB_ID_TOKEN = "h";
    const CREDENTIALS_STS_ASSUME_ROLE = "i";
    const CREDENTIALS_STS_ASSUME_ROLE_WEB_ID = "k";
    const CREDENTIALS_PROFILE = "n";
    const CREDENTIALS_PROFILE_STS_WEB_ID_TOKEN = "q";
    const CREDENTIALS_HTTP = "z";
    const CREDENTIALS_IMDS = "0";
    const CREDENTIALS_PROFILE_PROCESS = "v";
    const CREDENTIALS_PROFILE_SSO = "r";
    const CREDENTIALS_PROFILE_SSO_LEGACY = "t";

    /** @var int */
    private static $MAX_METRICS_SIZE = 1024; // 1KB or 1024 B

    /** @var string */
    private static $METRIC_SEPARATOR = ",";

    /** @var array $metrics */
    private $metrics;

    /** @var int $metricsSize */
    private $metricsSize;

    public function __construct()
    {
        $this->metrics = [];
        // The first metrics does not include the separator
        // therefore it is reduced by default.
        $this->metricsSize = -(strlen(self::$METRIC_SEPARATOR));
    }

    /**
     * Build the metrics string value.
     *
     * @return string
     */
    public function build(): string
    {
        if (empty($this->metrics)) {
            return "";
        }

        return $this->encode();
    }

    /**
     * Encodes the metrics by separating each metric
     * with a comma. Example: for the metrics[A,B,C] then
     * the output would be "A,B,C".
     *
     * @return string
     */
    private function encode(): string
    {
        return implode(self::$METRIC_SEPARATOR, array_keys($this->metrics));
    }

    /**
     * Appends a metric to the internal metrics holder after validating it.
     * Increases the current metrics size by the length of the new metric
     * plus the length of the encoding separator.
     * Example: $currentSize = $currentSize + len($newMetric) + len($separator)
     *
     * @param string $metric The metric to append.
     *
     * @return void
     */
    public function append(string $metric): void
    {
        if (!$this->canMetricBeAppended($metric)) {
            return;
        }

        $this->metrics[$metric] = true;
        $this->metricsSize += strlen($metric) + strlen(self::$METRIC_SEPARATOR);
    }

    /**
     * Receives a feature group and a value to identify which one is the metric.
     * For example, a group could be `signature` and a value could be `v4a`,
     * then the metric will be `SIGV4A_SIGNING`.
     *
     * @param string $featureGroup the feature group such as `signature`.
     * @param mixed $value the value for identifying the metric.
     *
     * @return void
     */
    public function identifyMetricByValueAndAppend(
        string $featureGroup,
        $value
    ): void
    {
        if (empty($value)) {
            return;
        }

        static $appendMetricFns = [
            'signature' => 'appendSignatureMetric',
            'request_compression' => 'appendRequestCompressionMetric',
            'request_checksum' => 'appendRequestChecksumMetric',
            'credentials' => 'appendCredentialsMetric',
            'account_id_endpoint_mode' => 'appendAccountIdEndpointMode',
            'account_id_endpoint' => 'appendAccountIdEndpoint',
            'request_checksum_calculation' => 'appendRequestChecksumCalculationMetric',
        ];

        $fn = $appendMetricFns[$featureGroup];
        $this->{$fn}($value);
    }

    /**
     * Appends the signature metric based on the signature value.
     *
     * @param string $signature
     *
     * @return void
     */
    private function appendSignatureMetric(string $signature): void
    {
        if ($signature === 'v4-s3express') {
            $this->append(self::S3_EXPRESS_BUCKET);
        } elseif ($signature === 'v4a') {
            $this->append(self::SIGV4A_SIGNING);
        }
    }

    /**
     * Appends the request compression metric based on the format resolved.
     *
     * @param string $format
     *
     * @return void
     */
    private function appendRequestCompressionMetric(string $format): void
    {
        if ($format === 'gzip') {
            $this->append(self::GZIP_REQUEST_COMPRESSION);
        }
    }

    /**
     * Appends the request checksum metric based on the algorithm.
     *
     * @param string $algorithm
     *
     * @return void
     */
    private function appendRequestChecksumMetric(string $algorithm): void
    {
        if ($algorithm === 'crc32') {
            $this->append(self::FLEXIBLE_CHECKSUMS_REQ_CRC32);
        } elseif ($algorithm === 'crc32c') {
            $this->append(self::FLEXIBLE_CHECKSUMS_REQ_CRC32C);
        } elseif ($algorithm === 'crc64') {
            $this->append(self::FLEXIBLE_CHECKSUMS_REQ_CRC64);
        } elseif ($algorithm === 'sha1') {
            $this->append(self::FLEXIBLE_CHECKSUMS_REQ_SHA1);
        } elseif ($algorithm === 'sha256') {
            $this->append(self::FLEXIBLE_CHECKSUMS_REQ_SHA256);
        }
    }


    /**
     * Appends the credentials metric based on the type of credentials
     * resolved.
     *
     * @param CredentialsInterface $credentials
     *
     * @return void
     */
    private function appendCredentialsMetric(
        CredentialsInterface $credentials
    ): void
    {
        $source = $credentials->toArray()['source'] ?? null;
        if (empty($source)) {
            return;
        }

        static $credentialsMetricMapping = [
            CredentialSources::STATIC =>
                self::CREDENTIALS_CODE,
            CredentialSources::ENVIRONMENT =>
                self::CREDENTIALS_ENV_VARS,
            CredentialSources::ENVIRONMENT_STS_WEB_ID_TOKEN =>
                self::CREDENTIALS_ENV_VARS_STS_WEB_ID_TOKEN,
            CredentialSources::STS_ASSUME_ROLE =>
                self::CREDENTIALS_STS_ASSUME_ROLE,
            CredentialSources::STS_WEB_ID_TOKEN =>
                self::CREDENTIALS_STS_ASSUME_ROLE_WEB_ID,
            CredentialSources::PROFILE =>
                self::CREDENTIALS_PROFILE,
            CredentialSources::IMDS =>
                self::CREDENTIALS_IMDS,
            CredentialSources::ECS =>
                self::CREDENTIALS_HTTP,
            CredentialSources::PROFILE_STS_WEB_ID_TOKEN =>
                self::CREDENTIALS_PROFILE_STS_WEB_ID_TOKEN,
            CredentialSources::PROFILE_PROCESS =>
                self::CREDENTIALS_PROFILE_PROCESS,
            CredentialSources::PROFILE_SSO =>
                self::CREDENTIALS_PROFILE_SSO,
            CredentialSources::PROFILE_SSO_LEGACY =>
                self::CREDENTIALS_PROFILE_SSO_LEGACY,
        ];
        if (isset($credentialsMetricMapping[$source])) {
            $this->append($credentialsMetricMapping[$source]);
        }
    }

    private function appendRequestChecksumCalculationMetric(
        string $checkSumCalculation
    ): void
    {
        static $checksumCalculationMetricMapping = [
            'when_supported' => self::FLEXIBLE_CHECKSUMS_REQ_WHEN_SUPPORTED,
            'when_required' => self::FLEXIBLE_CHECKSUMS_REQ_WHEN_REQUIRED,
        ];

        if (isset($checksumCalculationMetricMapping[$checkSumCalculation])) {
            $this->append($checksumCalculationMetricMapping[$checkSumCalculation]);
        }
    }

    /**
     * Appends the account_id_endpoint_mode metrics based on
     * the value resolved.
     *
     * @param string $accountIdEndpointMode
     *
     * @return void
     */
    private function appendAccountIdEndpointMode(
        string $accountIdEndpointMode
    ): void
    {
        if (empty($accountIdEndpointMode)) {
            return;
        }

        if ($accountIdEndpointMode === 'preferred') {
            $this->append(self::ACCOUNT_ID_MODE_PREFERRED);
        } elseif ($accountIdEndpointMode === 'disabled') {
            $this->append(self::ACCOUNT_ID_MODE_DISABLED);
        } elseif ($accountIdEndpointMode === 'required') {
            $this->append(self::ACCOUNT_ID_MODE_REQUIRED);
        }
    }

    /**
     * Appends the account_id_endpoint metric whenever a resolved endpoint
     * matches an account_id endpoint pattern which also defined here.
     *
     * @param string $endpoint
     *
     * @return void
     */
    private function appendAccountIdEndpoint(string $endpoint): void
    {
        static $pattern = "/(https|http):\\/\\/\\d{12}\\.ddb/";
        if (preg_match($pattern, $endpoint)) {
            $this->append(self::ACCOUNT_ID_ENDPOINT);
        }
    }

    /**
     * Resolves metrics from client arguments.
     *
     * @param array $args
     *
     * @return void
     */
    public function resolveAndAppendFromArgs(array $args = []): void
    {
        static $metricsFnList = [
            'appendEndpointMetric',
            'appendRetryConfigMetric',
            'appendResponseChecksumValidationMetric',
        ];
        foreach ($metricsFnList as $metricFn) {
            $this->{$metricFn}($args);
        }
    }

    /**
     * Appends the endpoint metric into the metrics builder,
     * just if a custom endpoint was provided at client construction.
     *
     * @param array $args
     *
     * @return void
     */
    private function appendEndpointMetric(array $args): void
    {
        if (!empty($args['endpoint_override'])) {
            $this->append(MetricsBuilder::ENDPOINT_OVERRIDE);
        }
    }

    /**
     * Appends the retry mode metric into the metrics builder,
     * based on the resolved retry config mode.
     *
     * @param array $args
     *
     * @return void
     */
    private function appendRetryConfigMetric(array $args): void
    {
        $retries = $args['retries'] ?? null;
        if ($retries === null) {
            return;
        }

        $retryMode = '';
        if ($retries instanceof \Aws\Retry\Configuration) {
            $retryMode = $retries->getMode();
        } elseif (is_array($retries)
            && isset($retries["mode"])
        ) {
            $retryMode = $retries["mode"];
        }

        if ($retryMode === 'legacy') {
            $this->append(
                MetricsBuilder::RETRY_MODE_LEGACY
            );
        } elseif ($retryMode === 'standard') {
            $this->append(
                MetricsBuilder::RETRY_MODE_STANDARD
            );
        } elseif ($retryMode === 'adaptive') {
            $this->append(
                MetricsBuilder::RETRY_MODE_ADAPTIVE
            );
        }
    }

    /**
     * Appends the provided/resolved response checksum validation mode.
     *
     * @param array $args
     *
     * @return void
     */
    private function appendResponseChecksumValidationMetric(array $args): void
    {
        if (empty($args['response_checksum_validation'])) {
            return;
        }

        $checksumValidation = $args['response_checksum_validation'];
        static $checksumValidationMetricMapping = [
            'when_supported' => MetricsBuilder::FLEXIBLE_CHECKSUMS_RES_WHEN_SUPPORTED,
            'when_required' => MetricsBuilder::FLEXIBLE_CHECKSUMS_RES_WHEN_REQUIRED,
        ];

        if (isset($checksumValidationMetricMapping[$checksumValidation])) {
            $this->append($checksumValidationMetricMapping[$checksumValidation]);
        }
    }

    /**
     * Validates if a metric can be appended by ensuring the total size,
     * including the new metric and separator, does not exceed the limit.
     * Also checks that the metric does not already exist.
     * Example: Appendable if:
     *  $currentSize + len($newMetric) + len($separator) <= MAX_SIZE
     *  and:
     * $newMetric not in $existingMetrics
     *
     * @param string $newMetric The metric to validate.
     *
     * @return bool True if the metric can be appended, false otherwise.
     */
    private function canMetricBeAppended(string $newMetric): bool
    {
        if ($newMetric === "") {
            return false;
        }

        if ($this->metricsSize
            + (strlen($newMetric) + strlen(self::$METRIC_SEPARATOR))
            > self::$MAX_METRICS_SIZE
        ) {
            return false;
        }

        if (isset($this->metrics[$newMetric])) {
            return false;
        }

        return true;
    }

    /**
     * Returns the metrics builder from the property @context of a command.
     *
     * @param Command $command
     *
     * @return MetricsBuilder
     */
    public static function fromCommand(CommandInterface $command): MetricsBuilder
    {
        return $command->getMetricsBuilder();
    }

    /**
     * Helper method for appending a metrics capture middleware into a
     * handler stack given. The middleware appended here is on top of the
     * build step.
     *
     * @param HandlerList $handlerList
     * @param $metric
     *
     * @return void
     */
    public static function appendMetricsCaptureMiddleware(
        HandlerList $handlerList,
        $metric
    ): void
    {
        $middlewareName = 'metrics-capture-'.$metric;
        if (!$handlerList->hasMiddleware($middlewareName)) {
            $handlerList->appendBuild(
                Middleware::tap(
                    function (CommandInterface $command) use ($metric) {
                        self::fromCommand($command)->append(
                            $metric
                        );
                    }
                ),
                $middlewareName
            );
        }
    }
}
