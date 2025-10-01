<?php
namespace Aws\S3;

use AWS\CRT\CRT;
use Aws\Exception\CommonRuntimeException;
use GuzzleHttp\Psr7;
use InvalidArgumentException;

trait CalculatesChecksumTrait
{
    private static $supportedAlgorithms = [
        'crc32c' => true,
        'crc32' => true,
        'sha256' => true,
        'sha1' => true
    ];

    /**
     * @param string $requestedAlgorithm  the algorithm to encode with
     * @param string $value               the value to be encoded
     * @return string
     */
    public static function getEncodedValue($requestedAlgorithm, $value) {
        $requestedAlgorithm = strtolower($requestedAlgorithm);
        $useCrt = extension_loaded('awscrt');

        if (isset(self::$supportedAlgorithms[$requestedAlgorithm])) {
            if ($useCrt) {
                $crt = new Crt();
                switch ($requestedAlgorithm) {
                    case 'crc32c':
                        return base64_encode(pack('N*',($crt::crc32c($value))));
                    case 'crc32':
                        return base64_encode(pack('N*',($crt::crc32($value))));
                    default:
                        break;
                }
            }

            if ($requestedAlgorithm === 'crc32c') {
                throw new CommonRuntimeException("crc32c is not supported for checksums "
                    . "without use of the common runtime for php.  Please enable the CRT or choose "
                    . "a different algorithm."
                );
            }

            if ($requestedAlgorithm === "crc32") {
                $requestedAlgorithm = "crc32b";
            }
            return base64_encode(Psr7\Utils::hash($value, $requestedAlgorithm, true));
        }

        $validAlgorithms = implode(', ', array_keys(self::$supportedAlgorithms));
        throw new InvalidArgumentException(
            "Invalid checksum requested: {$requestedAlgorithm}."
            . "  Valid algorithms supported by the runtime are {$validAlgorithms}."
        );
    }
}
