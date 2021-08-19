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

/**
 * The Identifier encodes the ASN.1 tag (class and number) of the type of a data value.
 *
 * Every identifier whose number is in the range 0 to 30 has the following structure:
 *
 * Bits:    8  7    6    5  4  3  2  1
 *       | Class | P/C |   Tag number  |
 *       ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
 *
 * Bits 8 and 7 define the class of this type ( Universal, Application, Context-specific or Private).
 * Bit 6 encoded whether this type is primitive or constructed
 * The remaining bits 5 - 1 encode the tag number
 */
class Identifier
{
    const CLASS_UNIVERSAL        = 0x00;
    const CLASS_APPLICATION      = 0x01;
    const CLASS_CONTEXT_SPECIFIC = 0x02;
    const CLASS_PRIVATE          = 0x03;

    const EOC               = 0x00; // unsupported for now
    const BOOLEAN           = 0x01;
    const INTEGER           = 0x02;
    const BITSTRING         = 0x03;
    const OCTETSTRING       = 0x04;
    const NULL              = 0x05;
    const OBJECT_IDENTIFIER = 0x06;
    const OBJECT_DESCRIPTOR = 0x07;
    const EXTERNAL          = 0x08; // unsupported for now
    const REAL              = 0x09; // unsupported for now
    const ENUMERATED        = 0x0A;
    const EMBEDDED_PDV      = 0x0B; // unsupported for now
    const UTF8_STRING       = 0x0C;
    const RELATIVE_OID      = 0x0D;
    // value 0x0E and 0x0F are reserved for future use

    const SEQUENCE          = 0x30;
    const SET               = 0x31;
    const NUMERIC_STRING    = 0x12;
    const PRINTABLE_STRING  = 0x13;
    const T61_STRING        = 0x14; // sometimes referred to as TeletextString
    const VIDEOTEXT_STRING  = 0x15;
    const IA5_STRING        = 0x16;
    const UTC_TIME          = 0x17;
    const GENERALIZED_TIME  = 0x18;
    const GRAPHIC_STRING    = 0x19;
    const VISIBLE_STRING    = 0x1A;
    const GENERAL_STRING    = 0x1B;
    const UNIVERSAL_STRING  = 0x1C;
    const CHARACTER_STRING  = 0x1D; // Unrestricted character type
    const BMP_STRING        = 0x1E;

    const LONG_FORM         = 0x1F;
    const IS_CONSTRUCTED    = 0x20;

    /**
     * Creates an identifier. Short form identifiers are returned as integers
     * for BC, long form identifiers will be returned as a string of octets.
     *
     * @param int $class
     * @param bool $isConstructed
     * @param int $tagNumber
     *
     * @throws Exception if the given arguments are invalid
     *
     * @return int|string
     */
    public static function create($class, $isConstructed, $tagNumber)
    {
        if (!is_numeric($class) || $class < self::CLASS_UNIVERSAL || $class > self::CLASS_PRIVATE) {
            throw new Exception(sprintf('Invalid class %d given', $class));
        }

        if (!is_bool($isConstructed)) {
            throw new Exception("\$isConstructed must be a boolean value ($isConstructed given)");
        }

        $tagNumber = self::makeNumeric($tagNumber);
        if ($tagNumber < 0) {
            throw new Exception(sprintf('Invalid $tagNumber %d given. You can only use positive integers.', $tagNumber));
        }

        if ($tagNumber < self::LONG_FORM) {
            return ($class << 6) | ($isConstructed << 5) | $tagNumber;
        }

        $firstOctet = ($class << 6) | ($isConstructed << 5) | self::LONG_FORM;

        // Tag numbers formatted in long form are base-128 encoded. See X.609#8.1.2.4
        return chr($firstOctet).Base128::encode($tagNumber);
    }

    public static function isConstructed($identifierOctet)
    {
        return ($identifierOctet & self::IS_CONSTRUCTED) === self::IS_CONSTRUCTED;
    }

    public static function isLongForm($identifierOctet)
    {
        return ($identifierOctet & self::LONG_FORM) === self::LONG_FORM;
    }

    /**
     * Return the name of the mapped ASN.1 type with a preceding "ASN.1 ".
     *
     * Example: ASN.1 Octet String
     *
     * @see Identifier::getShortName()
     *
     * @param int|string $identifier
     *
     * @return string
     */
    public static function getName($identifier)
    {
        $identifierOctet = self::makeNumeric($identifier);

        $typeName = static::getShortName($identifier);

        if (($identifierOctet & self::LONG_FORM) < self::LONG_FORM) {
            $typeName = "ASN.1 {$typeName}";
        }

        return $typeName;
    }

    /**
     * Return the short version of the type name.
     *
     * If the given identifier octet can be mapped to a known universal type this will
     * return its name. Else Identifier::getClassDescription() is used to retrieve
     * information about the identifier.
     *
     * @see Identifier::getName()
     * @see Identifier::getClassDescription()
     *
     * @param int|string $identifier
     *
     * @return string
     */
    public static function getShortName($identifier)
    {
        $identifierOctet = self::makeNumeric($identifier);

        switch ($identifierOctet) {
            case self::EOC:
                return 'End-of-contents octet';
            case self::BOOLEAN:
                return 'Boolean';
            case self::INTEGER:
                return 'Integer';
            case self::BITSTRING:
                return 'Bit String';
            case self::OCTETSTRING:
                return 'Octet String';
            case self::NULL:
                return 'NULL';
            case self::OBJECT_IDENTIFIER:
                return 'Object Identifier';
            case self::OBJECT_DESCRIPTOR:
                return 'Object Descriptor';
            case self::EXTERNAL:
                return 'External Type';
            case self::REAL:
                return 'Real';
            case self::ENUMERATED:
                return 'Enumerated';
            case self::EMBEDDED_PDV:
                return 'Embedded PDV';
            case self::UTF8_STRING:
                return 'UTF8 String';
            case self::RELATIVE_OID:
                return 'Relative OID';
            case self::SEQUENCE:
                return 'Sequence';
            case self::SET:
                return 'Set';
            case self::NUMERIC_STRING:
                return 'Numeric String';
            case self::PRINTABLE_STRING:
                return 'Printable String';
            case self::T61_STRING:
                return 'T61 String';
            case self::VIDEOTEXT_STRING:
                return 'Videotext String';
            case self::IA5_STRING:
                return 'IA5 String';
            case self::UTC_TIME:
                return 'UTC Time';
            case self::GENERALIZED_TIME:
                return 'Generalized Time';
            case self::GRAPHIC_STRING:
                return 'Graphic String';
            case self::VISIBLE_STRING:
                return 'Visible String';
            case self::GENERAL_STRING:
                return 'General String';
            case self::UNIVERSAL_STRING:
                return 'Universal String';
            case self::CHARACTER_STRING:
                return 'Character String';
            case self::BMP_STRING:
                return 'BMP String';

            case 0x0E:
                return 'RESERVED (0x0E)';
            case 0x0F:
                return 'RESERVED (0x0F)';

            case self::LONG_FORM:
            default:
                $classDescription = self::getClassDescription($identifier);

                if (is_int($identifier)) {
                    $identifier = chr($identifier);
                }

                return "$classDescription (0x".strtoupper(bin2hex($identifier)).')';
        }
    }

    /**
     * Returns a textual description of the information encoded in a given identifier octet.
     *
     * The first three (most significant) bytes are evaluated to determine if this is a
     * constructed or primitive type and if it is either universal, application, context-specific or
     * private.
     *
     * Example:
     *     Constructed context-specific
     *     Primitive universal
     *
     * @param int|string $identifier
     *
     * @return string
     */
    public static function getClassDescription($identifier)
    {
        $identifierOctet = self::makeNumeric($identifier);

        if (self::isConstructed($identifierOctet)) {
            $classDescription = 'Constructed ';
        } else {
            $classDescription = 'Primitive ';
        }
        $classBits = $identifierOctet >> 6;
        switch ($classBits) {
            case self::CLASS_UNIVERSAL:
                $classDescription .= 'universal';
                break;
            case self::CLASS_APPLICATION:
                $classDescription .= 'application';
                break;
            case self::CLASS_CONTEXT_SPECIFIC:
                $tagNumber = self::getTagNumber($identifier);
                $classDescription = "[$tagNumber] Context-specific";
                break;
            case self::CLASS_PRIVATE:
                $classDescription .= 'private';
                break;

            default:
                return "INVALID IDENTIFIER OCTET: {$identifierOctet}";
        }

        return $classDescription;
    }

    /**
     * @param int|string $identifier
     *
     * @return int
     */
    public static function getTagNumber($identifier)
    {
        $firstOctet = self::makeNumeric($identifier);
        $tagNumber = $firstOctet & self::LONG_FORM;

        if ($tagNumber < self::LONG_FORM) {
            return $tagNumber;
        }

        if (is_numeric($identifier)) {
            $identifier = chr($identifier);
        }
        return Base128::decode(substr($identifier, 1));
    }

    public static function isUniversalClass($identifier)
    {
        $identifier = self::makeNumeric($identifier);

        return $identifier >> 6 == self::CLASS_UNIVERSAL;
    }

    public static function isApplicationClass($identifier)
    {
        $identifier = self::makeNumeric($identifier);

        return $identifier >> 6 == self::CLASS_APPLICATION;
    }

    public static function isContextSpecificClass($identifier)
    {
        $identifier = self::makeNumeric($identifier);

        return $identifier >> 6 == self::CLASS_CONTEXT_SPECIFIC;
    }

    public static function isPrivateClass($identifier)
    {
        $identifier = self::makeNumeric($identifier);

        return $identifier >> 6 == self::CLASS_PRIVATE;
    }

    private static function makeNumeric($identifierOctet)
    {
        if (!is_numeric($identifierOctet)) {
            return ord($identifierOctet);
        } else {
            return $identifierOctet;
        }
    }
}
