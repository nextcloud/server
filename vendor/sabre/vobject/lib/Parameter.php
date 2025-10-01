<?php

namespace Sabre\VObject;

use ArrayIterator;
use Sabre\Xml;

/**
 * VObject Parameter.
 *
 * This class represents a parameter. A parameter is always tied to a property.
 * In the case of:
 *   DTSTART;VALUE=DATE:20101108
 * VALUE=DATE would be the parameter name and value.
 *
 * @copyright Copyright (C) fruux GmbH (https://fruux.com/)
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
class Parameter extends Node
{
    /**
     * Parameter name.
     *
     * @var string
     */
    public $name;

    /**
     * vCard 2.1 allows parameters to be encoded without a name.
     *
     * We can deduce the parameter name based on its value.
     *
     * @var bool
     */
    public $noName = false;

    /**
     * Parameter value.
     *
     * @var string
     */
    protected $value;

    /**
     * Sets up the object.
     *
     * It's recommended to use the create:: factory method instead.
     *
     * @param string $name
     * @param string $value
     */
    public function __construct(Document $root, $name, $value = null)
    {
        $this->root = $root;
        if (is_null($name)) {
            $this->noName = true;
            $this->name = static::guessParameterNameByValue($value);
        } else {
            $this->name = strtoupper($name);
        }

        // If guessParameterNameByValue() returns an empty string
        // above, we're actually dealing with a parameter that has no value.
        // In that case we have to move the value to the name.
        if ('' === $this->name) {
            $this->noName = false;
            $this->name = strtoupper($value);
        } else {
            $this->setValue($value);
        }
    }

    /**
     * Try to guess property name by value, can be used for vCard 2.1 nameless parameters.
     *
     * Figuring out what the name should have been. Note that a ton of
     * these are rather silly in 2014 and would probably rarely be
     * used, but we like to be complete.
     *
     * @param string $value
     *
     * @return string
     */
    public static function guessParameterNameByValue($value)
    {
        switch (strtoupper($value)) {
            // Encodings
            case '7-BIT':
            case 'QUOTED-PRINTABLE':
            case 'BASE64':
                $name = 'ENCODING';
                break;

            // Common types
            case 'WORK':
            case 'HOME':
            case 'PREF':
            // Delivery Label Type
            case 'DOM':
            case 'INTL':
            case 'POSTAL':
            case 'PARCEL':
            // Telephone types
            case 'VOICE':
            case 'FAX':
            case 'MSG':
            case 'CELL':
            case 'PAGER':
            case 'BBS':
            case 'MODEM':
            case 'CAR':
            case 'ISDN':
            case 'VIDEO':
            // EMAIL types (lol)
            case 'AOL':
            case 'APPLELINK':
            case 'ATTMAIL':
            case 'CIS':
            case 'EWORLD':
            case 'INTERNET':
            case 'IBMMAIL':
            case 'MCIMAIL':
            case 'POWERSHARE':
            case 'PRODIGY':
            case 'TLX':
            case 'X400':
            // Photo / Logo format types
            case 'GIF':
            case 'CGM':
            case 'WMF':
            case 'BMP':
            case 'DIB':
            case 'PICT':
            case 'TIFF':
            case 'PDF':
            case 'PS':
            case 'JPEG':
            case 'MPEG':
            case 'MPEG2':
            case 'AVI':
            case 'QTIME':
            // Sound Digital Audio Type
            case 'WAVE':
            case 'PCM':
            case 'AIFF':
            // Key types
            case 'X509':
            case 'PGP':
                $name = 'TYPE';
                break;

            // Value types
            case 'INLINE':
            case 'URL':
            case 'CONTENT-ID':
            case 'CID':
                $name = 'VALUE';
                break;

            default:
                $name = '';
        }

        return $name;
    }

    /**
     * Updates the current value.
     *
     * This may be either a single, or multiple strings in an array.
     *
     * @param string|array $value
     */
    public function setValue($value)
    {
        $this->value = $value;
    }

    /**
     * Returns the current value.
     *
     * This method will always return a string, or null. If there were multiple
     * values, it will automatically concatenate them (separated by comma).
     *
     * @return string|null
     */
    public function getValue()
    {
        if (is_array($this->value)) {
            return implode(',', $this->value);
        } else {
            return $this->value;
        }
    }

    /**
     * Sets multiple values for this parameter.
     */
    public function setParts(array $value)
    {
        $this->value = $value;
    }

    /**
     * Returns all values for this parameter.
     *
     * If there were no values, an empty array will be returned.
     *
     * @return array
     */
    public function getParts()
    {
        if (is_array($this->value)) {
            return $this->value;
        } elseif (is_null($this->value)) {
            return [];
        } else {
            return [$this->value];
        }
    }

    /**
     * Adds a value to this parameter.
     *
     * If the argument is specified as an array, all items will be added to the
     * parameter value list.
     *
     * @param string|array $part
     */
    public function addValue($part)
    {
        if (is_null($this->value)) {
            $this->value = $part;
        } else {
            $this->value = array_merge((array) $this->value, (array) $part);
        }
    }

    /**
     * Checks if this parameter contains the specified value.
     *
     * This is a case-insensitive match. It makes sense to call this for for
     * instance the TYPE parameter, to see if it contains a keyword such as
     * 'WORK' or 'FAX'.
     *
     * @param string $value
     *
     * @return bool
     */
    public function has($value)
    {
        return in_array(
            strtolower($value),
            array_map('strtolower', (array) $this->value)
        );
    }

    /**
     * Turns the object back into a serialized blob.
     *
     * @return string
     */
    public function serialize()
    {
        $value = $this->getParts();

        if (0 === count($value)) {
            return $this->name.'=';
        }

        if (Document::VCARD21 === $this->root->getDocumentType() && $this->noName) {
            return implode(';', $value);
        }

        return $this->name.'='.array_reduce(
            $value,
            function ($out, $item) {
                if (!is_null($out)) {
                    $out .= ',';
                }

                // If there's no special characters in the string, we'll use the simple
                // format.
                //
                // The list of special characters is defined as:
                //
                // Any character except CONTROL, DQUOTE, ";", ":", ","
                //
                // by the iCalendar spec:
                // https://tools.ietf.org/html/rfc5545#section-3.1
                //
                // And we add ^ to that because of:
                // https://tools.ietf.org/html/rfc6868
                //
                // But we've found that iCal (7.0, shipped with OSX 10.9)
                // severely trips on + characters not being quoted, so we
                // added + as well.
                if (!preg_match('#(?: [\n":;\^,\+] )#x', $item)) {
                    return $out.$item;
                } else {
                    // Enclosing in double-quotes, and using RFC6868 for encoding any
                    // special characters
                    $out .= '"'.strtr(
                        $item,
                        [
                            '^' => '^^',
                            "\n" => '^n',
                            '"' => '^\'',
                        ]
                    ).'"';

                    return $out;
                }
            }
        );
    }

    /**
     * This method returns an array, with the representation as it should be
     * encoded in JSON. This is used to create jCard or jCal documents.
     *
     * @return array
     */
    #[\ReturnTypeWillChange]
    public function jsonSerialize()
    {
        return $this->value;
    }

    /**
     * This method serializes the data into XML. This is used to create xCard or
     * xCal documents.
     *
     * @param Xml\Writer $writer XML writer
     */
    public function xmlSerialize(Xml\Writer $writer): void
    {
        foreach (explode(',', $this->value) as $value) {
            $writer->writeElement('text', $value);
        }
    }

    /**
     * Called when this object is being cast to a string.
     *
     * @return string
     */
    public function __toString()
    {
        return (string) $this->getValue();
    }

    /**
     * Returns the iterator for this object.
     *
     * @return ElementList
     */
    #[\ReturnTypeWillChange]
    public function getIterator()
    {
        if (!is_null($this->iterator)) {
            return $this->iterator;
        }

        return $this->iterator = new ArrayIterator((array) $this->value);
    }
}
