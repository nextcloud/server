<?php
/*
 * This file is part of the PHPASN1 library.
 *
 * Copyright © Friedrich Große <friedrich.grosse@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FG\ASN1\Universal;

use Exception;
use FG\ASN1\Exception\ParserException;
use FG\ASN1\Parsable;
use FG\ASN1\Identifier;

class BitString extends OctetString implements Parsable
{
    private $nrOfUnusedBits;

    /**
     * Creates a new ASN.1 BitString object.
     *
     * @param string|int $value Either the hexadecimal value as a string (spaces are allowed - leading 0x is optional) or a numeric value
     * @param int $nrOfUnusedBits the number of unused bits in the last octet [optional].
     *
     * @throws Exception if the second parameter is no positive numeric value
     */
    public function __construct($value, $nrOfUnusedBits = 0)
    {
        parent::__construct($value);

        if (!is_numeric($nrOfUnusedBits) || $nrOfUnusedBits < 0) {
            throw new Exception('BitString: second parameter needs to be a positive number (or zero)!');
        }

        $this->nrOfUnusedBits = $nrOfUnusedBits;
    }

    public function getType()
    {
        return Identifier::BITSTRING;
    }

    protected function calculateContentLength()
    {
        // add one to the length for the first octet which encodes the number of unused bits in the last octet
        return parent::calculateContentLength() + 1;
    }

    protected function getEncodedValue()
    {
        // the first octet determines the number of unused bits
        $nrOfUnusedBitsOctet = chr($this->nrOfUnusedBits);
        $actualContent = parent::getEncodedValue();

        return $nrOfUnusedBitsOctet.$actualContent;
    }

    public function getNumberOfUnusedBits()
    {
        return $this->nrOfUnusedBits;
    }

    public static function fromBinary(&$binaryData, &$offsetIndex = 0)
    {
        self::parseIdentifier($binaryData[$offsetIndex], Identifier::BITSTRING, $offsetIndex++);
        $contentLength = self::parseContentLength($binaryData, $offsetIndex, 2);

        $nrOfUnusedBits = ord($binaryData[$offsetIndex]);
        $value = substr($binaryData, $offsetIndex + 1, $contentLength - 1);

        if ($nrOfUnusedBits > 7 || // no less than 1 used, otherwise non-minimal
            ($contentLength - 1) == 1 && $nrOfUnusedBits > 0 || // content length only 1, no
            (ord($value[strlen($value)-1])&((1<<$nrOfUnusedBits)-1)) != 0 // unused bits set
        ) {
            throw new ParserException("Can not parse bit string with invalid padding", $offsetIndex);
        }

        $offsetIndex += $contentLength;

        $parsedObject = new self(bin2hex($value), $nrOfUnusedBits);
        $parsedObject->setContentLength($contentLength);

        return $parsedObject;
    }
}
