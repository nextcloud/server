<?php
/*
 * This file is part of the PHPASN1 library.
 *
 * Copyright © Friedrich Große <friedrich.grosse@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FG\ASN1;

use Exception;

abstract class AbstractString extends ASNObject implements Parsable
{
    /** @var string */
    protected $value;
    private $checkStringForIllegalChars = true;
    private $allowedCharacters = [];

    /**
     * The abstract base class for ASN.1 classes which represent some string of character.
     *
     * @param string $string
     */
    public function __construct($string)
    {
        $this->value = $string;
    }

    public function getContent()
    {
        return $this->value;
    }

    protected function allowCharacter($character)
    {
        $this->allowedCharacters[] = $character;
    }

    protected function allowCharacters(...$characters)
    {
        foreach ($characters as $character) {
            $this->allowedCharacters[] = $character;
        }
    }

    protected function allowNumbers()
    {
        foreach (range('0', '9') as $char) {
            $this->allowedCharacters[] = (string) $char;
        }
    }

    protected function allowAllLetters()
    {
        $this->allowSmallLetters();
        $this->allowCapitalLetters();
    }

    protected function allowSmallLetters()
    {
        foreach (range('a', 'z') as $char) {
            $this->allowedCharacters[] = $char;
        }
    }

    protected function allowCapitalLetters()
    {
        foreach (range('A', 'Z') as $char) {
            $this->allowedCharacters[] = $char;
        }
    }

    protected function allowSpaces()
    {
        $this->allowedCharacters[] = ' ';
    }

    protected function allowAll()
    {
        $this->checkStringForIllegalChars = false;
    }

    protected function calculateContentLength()
    {
        return strlen($this->value);
    }

    protected function getEncodedValue()
    {
        if ($this->checkStringForIllegalChars) {
            $this->checkString();
        }

        return $this->value;
    }

    protected function checkString()
    {
        $stringLength = $this->getContentLength();
        for ($i = 0; $i < $stringLength; $i++) {
            if (in_array($this->value[$i], $this->allowedCharacters) == false) {
                $typeName = Identifier::getName($this->getType());
                throw new Exception("Could not create a {$typeName} from the character sequence '{$this->value}'.");
            }
        }
    }

    public static function fromBinary(&$binaryData, &$offsetIndex = 0)
    {
        $parsedObject = new static('');

        self::parseIdentifier($binaryData[$offsetIndex], $parsedObject->getType(), $offsetIndex++);
        $contentLength = self::parseContentLength($binaryData, $offsetIndex);
        $string = substr($binaryData, $offsetIndex, $contentLength);
        $offsetIndex += $contentLength;

        $parsedObject->value = $string;
        $parsedObject->setContentLength($contentLength);
        return $parsedObject;
    }

    public static function isValid($string)
    {
        $testObject = new static($string);
        try {
            $testObject->checkString();

            return true;
        } catch (Exception $exception) {
            return false;
        }
    }
}
