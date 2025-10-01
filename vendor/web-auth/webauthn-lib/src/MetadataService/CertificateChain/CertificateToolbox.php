<?php

declare(strict_types=1);

namespace Webauthn\MetadataService\CertificateChain;

use ParagonIE\ConstantTime\Base64;
use function preg_replace;
use const PHP_EOL;

class CertificateToolbox
{
    private const PEM_HEADER = '-----BEGIN ';

    private const PEM_FOOTER = '-----END ';

    /**
     * @param string[] $data
     *
     * @return string[]
     */
    public static function fixPEMStructures(array $data, string $type = 'CERTIFICATE'): array
    {
        return array_map(static fn ($d): string => self::fixPEMStructure($d, $type), $data);
    }

    public static function fixPEMStructure(string $data, string $type = 'CERTIFICATE'): string
    {
        if (str_contains($data, self::PEM_HEADER)) {
            return trim($data);
        }
        $pem = self::PEM_HEADER . $type . '-----' . PHP_EOL;
        $pem .= chunk_split($data, 64, PHP_EOL);

        return $pem . (self::PEM_FOOTER . $type . '-----' . PHP_EOL);
    }

    /**
     * @deprecated since 4.7.0 and will be removed in 5.0.0. No replacement as not used internally.
     * @infection-ignore-all
     */
    public static function convertPEMToDER(string $data): string
    {
        if (! str_contains($data, self::PEM_HEADER)) {
            return $data;
        }
        $data = preg_replace('/\-{5}.*\-{5}[\r\n]*/', '', $data);
        $data = preg_replace("/[\r\n]*/", '', (string) $data);

        return Base64::decode(trim((string) $data), true);
    }

    public static function convertDERToPEM(string $data, string $type = 'CERTIFICATE'): string
    {
        if (str_contains($data, self::PEM_HEADER)) {
            return $data;
        }

        return self::fixPEMStructure(base64_encode($data), $type);
    }

    /**
     * @param string[] $data
     *
     * @return string[]
     */
    public static function convertAllDERToPEM(iterable $data, string $type = 'CERTIFICATE'): array
    {
        return array_map(static fn ($d): string => self::convertDERToPEM($d, $type), $data);
    }
}
