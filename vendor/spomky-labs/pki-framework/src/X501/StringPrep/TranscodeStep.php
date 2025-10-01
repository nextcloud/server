<?php

declare(strict_types=1);

namespace SpomkyLabs\Pki\X501\StringPrep;

use LogicException;
use SpomkyLabs\Pki\ASN1\Element;
use SpomkyLabs\Pki\ASN1\Type\Primitive\T61String;
use function in_array;

/**
 * Implements 'Transcode' step of the Internationalized String Preparation as specified by RFC 4518.
 *
 * @see https://tools.ietf.org/html/rfc4518#section-2.1
 */
final class TranscodeStep implements PrepareStep
{
    /**
     * Supported ASN.1 types.
     *
     * @var array<int>
     */
    private const SUPPORTED_TYPES = [
        Element::TYPE_UTF8_STRING,
        Element::TYPE_PRINTABLE_STRING,
        Element::TYPE_BMP_STRING,
        Element::TYPE_UNIVERSAL_STRING,
        Element::TYPE_T61_STRING,
    ];

    /**
     * @param int $_type ASN.1 type tag of the string
     */
    private function __construct(
        private readonly int $_type
    ) {
    }

    public static function create(int $_type): self
    {
        return new self($_type);
    }

    /**
     * Check whether transcoding from given ASN.1 type tag is supported.
     *
     * @param int $type ASN.1 type tag
     */
    public static function isTypeSupported(int $type): bool
    {
        return in_array($type, self::SUPPORTED_TYPES, true);
    }

    /**
     * @param string $string String to prepare
     *
     * @return string UTF-8 encoded string
     */
    public function apply(string $string): string
    {
        switch ($this->_type) {
            // UTF-8 string as is
            case Element::TYPE_UTF8_STRING:
                // PrintableString maps directly to UTF-8
            case Element::TYPE_PRINTABLE_STRING:
                return $string;
                // UCS-2 to UTF-8
            case Element::TYPE_BMP_STRING:
                return mb_convert_encoding($string, 'UTF-8', 'UCS-2BE');
                // UCS-4 to UTF-8
            case Element::TYPE_UNIVERSAL_STRING:
                return mb_convert_encoding($string, 'UTF-8', 'UCS-4BE');
                // TeletexString mapping is a local matter.
                // We take a shortcut here and encode it as a hexstring.
            case Element::TYPE_T61_STRING:
                $el = T61String::create($string);
                return '#' . bin2hex($el->toDER());
        }
        throw new LogicException(sprintf('Unsupported string type %s.', Element::tagToName($this->_type)));
    }
}
