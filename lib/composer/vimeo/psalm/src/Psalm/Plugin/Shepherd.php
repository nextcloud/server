<?php
namespace Psalm\Plugin;

use function array_filter;
use function curl_close;
use function curl_exec;
use function curl_getinfo;
use function curl_init;
use function curl_setopt;
use const CURLINFO_HEADER_OUT;
use const CURLOPT_HTTPHEADER;
use const CURLOPT_POST;
use const CURLOPT_POSTFIELDS;
use const CURLOPT_RETURNTRANSFER;
use function function_exists;
use function fwrite;
use function json_encode;
use function parse_url;
use const PHP_EOL;
use const PHP_URL_SCHEME;
use Psalm\Codebase;
use Psalm\Internal\Analyzer\IssueData;
use Psalm\SourceControl\SourceControlInfo;
use const STDERR;
use function strlen;
use function var_export;
use function array_merge;
use function array_values;

class Shepherd implements \Psalm\Plugin\Hook\AfterAnalysisInterface
{
    /**
     * Called after analysis is complete
     *
     * @param array<string, list<IssueData>> $issues
     */
    public static function afterAnalysis(
        Codebase $codebase,
        array $issues,
        array $build_info,
        ?SourceControlInfo $source_control_info = null
    ): void {
        if (!function_exists('curl_init')) {
            fwrite(STDERR, 'No curl found, cannot send data to ' . $codebase->config->shepherd_host . PHP_EOL);

            return;
        }

        $source_control_data = $source_control_info ? $source_control_info->toArray() : [];

        if (!$source_control_data && isset($build_info['git']) && \is_array($build_info['git'])) {
            $source_control_data = $build_info['git'];
        }

        unset($build_info['git']);

        if ($build_info) {
            $normalized_data = $issues === [] ? [] : array_filter(
                array_merge(...array_values($issues)),
                static function (IssueData $i) : bool {
                    return $i->severity === 'error';
                }
            );

            $data = [
                'build' => $build_info,
                'git' => $source_control_data,
                'issues' => $normalized_data,
                'coverage' => $codebase->analyzer->getTotalTypeCoverage($codebase),
            ];

            $payload = json_encode($data);

            $base_address = $codebase->config->shepherd_host;

            if (parse_url($base_address, PHP_URL_SCHEME) === null) {
                $base_address = 'https://' . $base_address;
            }

            // Prepare new cURL resource
            $ch = curl_init($base_address . '/hooks/psalm');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLINFO_HEADER_OUT, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);

            // Set HTTP Header for POST request
            curl_setopt(
                $ch,
                CURLOPT_HTTPHEADER,
                [
                    'Content-Type: application/json',
                    'Content-Length: ' . strlen($payload),
                ]
            );

            // Submit the POST request
            $return = curl_exec($ch);

            if ($return !== '') {
                fwrite(STDERR, 'Error with Psalm Shepherd:' . PHP_EOL);

                if ($return === false) {
                    fwrite(STDERR, self::getCurlErrorMessage($ch) . PHP_EOL);
                } else {
                    echo $return . PHP_EOL;
                    echo 'Git args: '
                        . var_export($source_control_data, true)
                        . PHP_EOL;
                    echo 'CI args: '
                        . var_export($build_info, true)
                        . PHP_EOL;
                }
            } else {
                $short_address = \str_replace('https://', '', $base_address);

                fwrite(STDERR, "🐑 results sent to $short_address 🐑" . PHP_EOL);
            }

            // Close cURL session handle
            curl_close($ch);
        }
    }

    /**
     * @param mixed $ch
     *
     * @psalm-pure
     */
    public static function getCurlErrorMessage($ch) : string
    {
        /**
         * @psalm-suppress MixedArgument
         * @var array
         */
        $curl_info = curl_getinfo($ch);

        if (isset($curl_info['ssl_verify_result'])
            && $curl_info['ssl_verify_result'] !== 0
        ) {
            switch ($curl_info['ssl_verify_result']) {
                case 2:
                    return 'unable to get issuer certificate';
                case 3:
                    return 'unable to get certificate CRL';
                case 4:
                    return 'unable to decrypt certificate’s signature';
                case 5:
                    return 'unable to decrypt CRL’s signature';
                case 6:
                    return 'unable to decode issuer public key';
                case 7:
                    return 'certificate signature failure';
                case 8:
                    return 'CRL signature failure';
                case 9:
                    return 'certificate is not yet valid';
                case 10:
                    return 'certificate has expired';
                case 11:
                    return 'CRL is not yet valid';
                case 12:
                    return 'CRL has expired';
                case 13:
                    return 'format error in certificate’s notBefore field';
                case 14:
                    return 'format error in certificate’s notAfter field';
                case 15:
                    return 'format error in CRL’s lastUpdate field';
                case 16:
                    return 'format error in CRL’s nextUpdate field';
                case 17:
                    return 'out of memory';
                case 18:
                    return 'self signed certificate';
                case 19:
                    return 'self signed certificate in certificate chain';
                case 20:
                    return 'unable to get local issuer certificate';
                case 21:
                    return 'unable to verify the first certificate';
                case 22:
                    return 'certificate chain too long';
                case 23:
                    return 'certificate revoked';
                case 24:
                    return 'invalid CA certificate';
                case 25:
                    return 'path length constraint exceeded';
                case 26:
                    return 'unsupported certificate purpose';
                case 27:
                    return 'certificate not trusted';
                case 28:
                    return 'certificate rejected';
                case 29:
                    return 'subject issuer mismatch';
                case 30:
                    return 'authority and subject key identifier mismatch';
                case 31:
                    return 'authority and issuer serial number mismatch';
                case 32:
                    return 'key usage does not include certificate signing';
                case 50:
                    return 'application verification failure';
            }

            return '';
        }

        /**
         * @psalm-suppress MixedArgument
         */
        return var_export(curl_getinfo($ch), true);
    }
}
