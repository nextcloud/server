<?php
/*
 * This file is part of the PHPASN1 library.
 *
 * Copyright © Friedrich Große <friedrich.grosse@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FG\X509\SAN;

use FG\ASN1\ASNObject;
use FG\ASN1\Parsable;
use FG\ASN1\Exception\ParserException;

class IPAddress extends ASNObject implements Parsable
{
    const IDENTIFIER = 0x87; // not sure yet why this is the identifier used in SAN extensions

    /** @var string */
    private $value;

    public function __construct($ipAddressString)
    {
        $this->value = $ipAddressString;
    }

    public function getType()
    {
        return self::IDENTIFIER;
    }

    public function getContent()
    {
        return $this->value;
    }

    protected function calculateContentLength()
    {
        return 4;
    }

    protected function getEncodedValue()
    {
        $ipParts = explode('.', $this->value);
        $binary  = chr($ipParts[0]);
        $binary .= chr($ipParts[1]);
        $binary .= chr($ipParts[2]);
        $binary .= chr($ipParts[3]);

        return $binary;
    }

    public static function fromBinary(&$binaryData, &$offsetIndex = 0)
    {
        self::parseIdentifier($binaryData[$offsetIndex], self::IDENTIFIER, $offsetIndex++);
        $contentLength = self::parseContentLength($binaryData, $offsetIndex);
        if ($contentLength != 4) {
            throw new ParserException("A FG\\X509\SAN\IPAddress should have a content length of 4. Extracted length was {$contentLength}", $offsetIndex);
        }

        $ipAddressString = ord($binaryData[$offsetIndex++]).'.';
        $ipAddressString .= ord($binaryData[$offsetIndex++]).'.';
        $ipAddressString .= ord($binaryData[$offsetIndex++]).'.';
        $ipAddressString .= ord($binaryData[$offsetIndex++]);

        $parsedObject = new self($ipAddressString);
        $parsedObject->getObjectLength();

        return $parsedObject;
    }
}
