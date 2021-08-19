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
use FG\ASN1\Base128;
use FG\ASN1\OID;
use FG\ASN1\ASNObject;
use FG\ASN1\Parsable;
use FG\ASN1\Identifier;
use FG\ASN1\Exception\ParserException;

class ObjectIdentifier extends ASNObject implements Parsable
{
    protected $subIdentifiers;
    protected $value;

    public function __construct($value)
    {
        $this->subIdentifiers = explode('.', $value);
        $nrOfSubIdentifiers = count($this->subIdentifiers);

        for ($i = 0; $i < $nrOfSubIdentifiers; $i++) {
            if (is_numeric($this->subIdentifiers[$i])) {
                // enforce the integer type
                $this->subIdentifiers[$i] = intval($this->subIdentifiers[$i]);
            } else {
                throw new Exception("[{$value}] is no valid object identifier (sub identifier ".($i + 1).' is not numeric)!');
            }
        }

        // Merge the first to arcs of the OID registration tree (per ASN definition!)
        if ($nrOfSubIdentifiers >= 2) {
            $this->subIdentifiers[1] = ($this->subIdentifiers[0] * 40) + $this->subIdentifiers[1];
            unset($this->subIdentifiers[0]);
        }

        $this->value = $value;
    }

    public function getContent()
    {
        return $this->value;
    }

    public function getType()
    {
        return Identifier::OBJECT_IDENTIFIER;
    }

    protected function calculateContentLength()
    {
        $length = 0;
        foreach ($this->subIdentifiers as $subIdentifier) {
            do {
                $subIdentifier = $subIdentifier >> 7;
                $length++;
            } while ($subIdentifier > 0);
        }

        return $length;
    }

    protected function getEncodedValue()
    {
        $encodedValue = '';
        foreach ($this->subIdentifiers as $subIdentifier) {
            $encodedValue .= Base128::encode($subIdentifier);
        }

        return $encodedValue;
    }

    public function __toString()
    {
        return OID::getName($this->value);
    }

    public static function fromBinary(&$binaryData, &$offsetIndex = 0)
    {
        self::parseIdentifier($binaryData[$offsetIndex], Identifier::OBJECT_IDENTIFIER, $offsetIndex++);
        $contentLength = self::parseContentLength($binaryData, $offsetIndex, 1);

        $firstOctet = ord($binaryData[$offsetIndex++]);
        $oidString = floor($firstOctet / 40).'.'.($firstOctet % 40);
        $oidString .= '.'.self::parseOid($binaryData, $offsetIndex, $contentLength - 1);

        $parsedObject = new self($oidString);
        $parsedObject->setContentLength($contentLength);

        return $parsedObject;
    }

    /**
     * Parses an object identifier except for the first octet, which is parsed
     * differently. This way relative object identifiers can also be parsed
     * using this.
     *
     * @param $binaryData
     * @param $offsetIndex
     * @param $octetsToRead
     *
     * @throws ParserException
     *
     * @return string
     */
    protected static function parseOid(&$binaryData, &$offsetIndex, $octetsToRead)
    {
        $oid = '';

        while ($octetsToRead > 0) {
            $octets = '';

            do {
                if (0 === $octetsToRead) {
                    throw new ParserException('Malformed ASN.1 Object Identifier', $offsetIndex - 1);
                }

                $octetsToRead--;
                $octet = $binaryData[$offsetIndex++];
                $octets .= $octet;
            } while (ord($octet) & 0x80);

            $oid .= sprintf('%d.', Base128::decode($octets));
        }

        // Remove trailing '.'
        return substr($oid, 0, -1) ?: '';
    }
}
