<?php

namespace Sabre\VObject;

use Sabre\Xml;

/**
 * Property.
 *
 * A property is always in a KEY:VALUE structure, and may optionally contain
 * parameters.
 *
 * @copyright Copyright (C) fruux GmbH (https://fruux.com/)
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
abstract class Property extends Node
{
    /**
     * Property name.
     *
     * This will contain a string such as DTSTART, SUMMARY, FN.
     *
     * @var string
     */
    public $name;

    /**
     * Property group.
     *
     * This is only used in vcards
     *
     * @var string|null
     */
    public $group;

    /**
     * List of parameters.
     *
     * @var array
     */
    public $parameters = [];

    /**
     * Current value.
     *
     * @var mixed
     */
    protected $value;

    /**
     * In case this is a multi-value property. This string will be used as a
     * delimiter.
     *
     * @var string
     */
    public $delimiter = ';';

    /**
     * The line number in the original iCalendar / vCard file
     *   that corresponds with the current node
     *   if the node was read from a file.
     */
    public $lineIndex;

    /**
     * The line string from the original iCalendar / vCard file
     *   that corresponds with the current node
     *   if the node was read from a file.
     */
    public $lineString;

    /**
     * Creates the generic property.
     *
     * Parameters must be specified in key=>value syntax.
     *
     * @param Component         $root       The root document
     * @param string            $name
     * @param string|array|null $value
     * @param array             $parameters List of parameters
     * @param string            $group      The vcard property group
     */
    public function __construct(Component $root, $name, $value = null, array $parameters = [], $group = null, ?int $lineIndex = null, ?string $lineString = null)
    {
        $this->name = $name;
        $this->group = $group;

        $this->root = $root;

        foreach ($parameters as $k => $v) {
            $this->add($k, $v);
        }

        if (!is_null($value)) {
            $this->setValue($value);
        }

        if (!is_null($lineIndex)) {
            $this->lineIndex = $lineIndex;
        }

        if (!is_null($lineString)) {
            $this->lineString = $lineString;
        }
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
     * This method will always return a singular value. If this was a
     * multi-value object, some decision will be made first on how to represent
     * it as a string.
     *
     * To get the correct multi-value version, use getParts.
     *
     * @return string
     */
    public function getValue()
    {
        if (is_array($this->value)) {
            if (0 == count($this->value)) {
                return;
            } elseif (1 === count($this->value)) {
                return $this->value[0];
            } else {
                return $this->getRawMimeDirValue();
            }
        } else {
            return $this->value;
        }
    }

    /**
     * Sets a multi-valued property.
     */
    public function setParts(array $parts)
    {
        $this->value = $parts;
    }

    /**
     * Returns a multi-valued property.
     *
     * This method always returns an array, if there was only a single value,
     * it will still be wrapped in an array.
     *
     * @return array
     */
    public function getParts()
    {
        if (is_null($this->value)) {
            return [];
        } elseif (is_array($this->value)) {
            return $this->value;
        } else {
            return [$this->value];
        }
    }

    /**
     * Adds a new parameter.
     *
     * If a parameter with same name already existed, the values will be
     * combined.
     * If nameless parameter is added, we try to guess its name.
     *
     * @param string            $name
     * @param string|array|null $value
     */
    public function add($name, $value = null)
    {
        $noName = false;
        if (null === $name) {
            $name = Parameter::guessParameterNameByValue($value);
            $noName = true;
        }

        if (isset($this->parameters[strtoupper($name)])) {
            $this->parameters[strtoupper($name)]->addValue($value);
        } else {
            $param = new Parameter($this->root, $name, $value);
            $param->noName = $noName;
            $this->parameters[$param->name] = $param;
        }
    }

    /**
     * Returns an iterable list of children.
     *
     * @return array
     */
    public function parameters()
    {
        return $this->parameters;
    }

    /**
     * Returns the type of value.
     *
     * This corresponds to the VALUE= parameter. Every property also has a
     * 'default' valueType.
     *
     * @return string
     */
    abstract public function getValueType();

    /**
     * Sets a raw value coming from a mimedir (iCalendar/vCard) file.
     *
     * This has been 'unfolded', so only 1 line will be passed. Unescaping is
     * not yet done, but parameters are not included.
     *
     * @param string $val
     */
    abstract public function setRawMimeDirValue($val);

    /**
     * Returns a raw mime-dir representation of the value.
     *
     * @return string
     */
    abstract public function getRawMimeDirValue();

    /**
     * Turns the object back into a serialized blob.
     *
     * @return string
     */
    public function serialize()
    {
        $str = $this->name;
        if ($this->group) {
            $str = $this->group.'.'.$this->name;
        }

        foreach ($this->parameters() as $param) {
            $str .= ';'.$param->serialize();
        }

        $str .= ':'.$this->getRawMimeDirValue();

        $str = \preg_replace(
            '/(
                (?:^.)?         # 1 additional byte in first line because of missing single space (see next line)
                .{1,74}         # max 75 bytes per line (1 byte is used for a single space added after every CRLF)
                (?![\x80-\xbf]) # prevent splitting multibyte characters
            )/x',
            "$1\r\n ",
            $str
        );

        // remove single space after last CRLF
        return \substr($str, 0, -1);
    }

    /**
     * Returns the value, in the format it should be encoded for JSON.
     *
     * This method must always return an array.
     *
     * @return array
     */
    public function getJsonValue()
    {
        return $this->getParts();
    }

    /**
     * Sets the JSON value, as it would appear in a jCard or jCal object.
     *
     * The value must always be an array.
     */
    public function setJsonValue(array $value)
    {
        if (1 === count($value)) {
            $this->setValue(reset($value));
        } else {
            $this->setValue($value);
        }
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
        $parameters = [];

        foreach ($this->parameters as $parameter) {
            if ('VALUE' === $parameter->name) {
                continue;
            }
            $parameters[strtolower($parameter->name)] = $parameter->jsonSerialize();
        }
        // In jCard, we need to encode the property-group as a separate 'group'
        // parameter.
        if ($this->group) {
            $parameters['group'] = $this->group;
        }

        return array_merge(
            [
                strtolower($this->name),
                (object) $parameters,
                strtolower($this->getValueType()),
            ],
            $this->getJsonValue()
        );
    }

    /**
     * Hydrate data from a XML subtree, as it would appear in a xCard or xCal
     * object.
     */
    public function setXmlValue(array $value)
    {
        $this->setJsonValue($value);
    }

    /**
     * This method serializes the data into XML. This is used to create xCard or
     * xCal documents.
     *
     * @param Xml\Writer $writer XML writer
     */
    public function xmlSerialize(Xml\Writer $writer): void
    {
        $parameters = [];

        foreach ($this->parameters as $parameter) {
            if ('VALUE' === $parameter->name) {
                continue;
            }

            $parameters[] = $parameter;
        }

        $writer->startElement(strtolower($this->name));

        if (!empty($parameters)) {
            $writer->startElement('parameters');

            foreach ($parameters as $parameter) {
                $writer->startElement(strtolower($parameter->name));
                $writer->write($parameter);
                $writer->endElement();
            }

            $writer->endElement();
        }

        $this->xmlSerializeValue($writer);
        $writer->endElement();
    }

    /**
     * This method serializes only the value of a property. This is used to
     * create xCard or xCal documents.
     *
     * @param Xml\Writer $writer XML writer
     */
    protected function xmlSerializeValue(Xml\Writer $writer)
    {
        $valueType = strtolower($this->getValueType());

        foreach ($this->getJsonValue() as $values) {
            foreach ((array) $values as $value) {
                $writer->writeElement($valueType, $value);
            }
        }
    }

    /**
     * Called when this object is being cast to a string.
     *
     * If the property only had a single value, you will get just that. In the
     * case the property had multiple values, the contents will be escaped and
     * combined with ,.
     *
     * @return string
     */
    public function __toString()
    {
        return (string) $this->getValue();
    }

    /* ArrayAccess interface {{{ */

    /**
     * Checks if an array element exists.
     *
     * @param mixed $name
     *
     * @return bool
     */
    #[\ReturnTypeWillChange]
    public function offsetExists($name)
    {
        if (is_int($name)) {
            return parent::offsetExists($name);
        }

        $name = strtoupper($name);

        foreach ($this->parameters as $parameter) {
            if ($parameter->name == $name) {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns a parameter.
     *
     * If the parameter does not exist, null is returned.
     *
     * @param string $name
     *
     * @return Node
     */
    #[\ReturnTypeWillChange]
    public function offsetGet($name)
    {
        if (is_int($name)) {
            return parent::offsetGet($name);
        }
        $name = strtoupper($name);

        if (!isset($this->parameters[$name])) {
            return;
        }

        return $this->parameters[$name];
    }

    /**
     * Creates a new parameter.
     *
     * @param string $name
     * @param mixed  $value
     */
    #[\ReturnTypeWillChange]
    public function offsetSet($name, $value)
    {
        if (is_int($name)) {
            parent::offsetSet($name, $value);
            // @codeCoverageIgnoreStart
            // This will never be reached, because an exception is always
            // thrown.
            return;
            // @codeCoverageIgnoreEnd
        }

        $param = new Parameter($this->root, $name, $value);
        $this->parameters[$param->name] = $param;
    }

    /**
     * Removes one or more parameters with the specified name.
     *
     * @param string $name
     */
    #[\ReturnTypeWillChange]
    public function offsetUnset($name)
    {
        if (is_int($name)) {
            parent::offsetUnset($name);
            // @codeCoverageIgnoreStart
            // This will never be reached, because an exception is always
            // thrown.
            return;
            // @codeCoverageIgnoreEnd
        }

        unset($this->parameters[strtoupper($name)]);
    }

    /* }}} */

    /**
     * This method is automatically called when the object is cloned.
     * Specifically, this will ensure all child elements are also cloned.
     */
    public function __clone()
    {
        foreach ($this->parameters as $key => $child) {
            $this->parameters[$key] = clone $child;
            $this->parameters[$key]->parent = $this;
        }
    }

    /**
     * Validates the node for correctness.
     *
     * The following options are supported:
     *   - Node::REPAIR - If something is broken, and automatic repair may
     *                    be attempted.
     *
     * An array is returned with warnings.
     *
     * Every item in the array has the following properties:
     *    * level - (number between 1 and 3 with severity information)
     *    * message - (human readable message)
     *    * node - (reference to the offending node)
     *
     * @param int $options
     *
     * @return array
     */
    public function validate($options = 0)
    {
        $warnings = [];

        // Checking if our value is UTF-8
        if (!StringUtil::isUTF8($this->getRawMimeDirValue())) {
            $oldValue = $this->getRawMimeDirValue();
            $level = 3;
            if ($options & self::REPAIR) {
                $newValue = StringUtil::convertToUTF8($oldValue);
                if (true || StringUtil::isUTF8($newValue)) {
                    $this->setRawMimeDirValue($newValue);
                    $level = 1;
                }
            }

            if (preg_match('%([\x00-\x08\x0B-\x0C\x0E-\x1F\x7F])%', $oldValue, $matches)) {
                $message = 'Property contained a control character (0x'.bin2hex($matches[1]).')';
            } else {
                $message = 'Property is not valid UTF-8! '.$oldValue;
            }

            $warnings[] = [
                'level' => $level,
                'message' => $message,
                'node' => $this,
            ];
        }

        // Checking if the propertyname does not contain any invalid bytes.
        if (!preg_match('/^([A-Z0-9-]+)$/', $this->name)) {
            $warnings[] = [
                'level' => $options & self::REPAIR ? 1 : 3,
                'message' => 'The propertyname: '.$this->name.' contains invalid characters. Only A-Z, 0-9 and - are allowed',
                'node' => $this,
            ];
            if ($options & self::REPAIR) {
                // Uppercasing and converting underscores to dashes.
                $this->name = strtoupper(
                    str_replace('_', '-', $this->name)
                );
                // Removing every other invalid character
                $this->name = preg_replace('/([^A-Z0-9-])/u', '', $this->name);
            }
        }

        if ($encoding = $this->offsetGet('ENCODING')) {
            if (Document::VCARD40 === $this->root->getDocumentType()) {
                $warnings[] = [
                    'level' => 3,
                    'message' => 'ENCODING parameter is not valid in vCard 4.',
                    'node' => $this,
                ];
            } else {
                $encoding = (string) $encoding;

                $allowedEncoding = [];

                switch ($this->root->getDocumentType()) {
                    case Document::ICALENDAR20:
                        $allowedEncoding = ['8BIT', 'BASE64'];
                        break;
                    case Document::VCARD21:
                        $allowedEncoding = ['QUOTED-PRINTABLE', 'BASE64', '8BIT'];
                        break;
                    case Document::VCARD30:
                        $allowedEncoding = ['B'];
                        //Repair vCard30 that use BASE64 encoding
                        if ($options & self::REPAIR) {
                            if ('BASE64' === strtoupper($encoding)) {
                                $encoding = 'B';
                                $this['ENCODING'] = $encoding;
                                $warnings[] = [
                                    'level' => 1,
                                    'message' => 'ENCODING=BASE64 has been transformed to ENCODING=B.',
                                    'node' => $this,
                                ];
                            }
                        }
                        break;
                }
                if ($allowedEncoding && !in_array(strtoupper($encoding), $allowedEncoding)) {
                    $warnings[] = [
                        'level' => 3,
                        'message' => 'ENCODING='.strtoupper($encoding).' is not valid for this document type.',
                        'node' => $this,
                    ];
                }
            }
        }

        // Validating inner parameters
        foreach ($this->parameters as $param) {
            $warnings = array_merge($warnings, $param->validate($options));
        }

        return $warnings;
    }

    /**
     * Call this method on a document if you're done using it.
     *
     * It's intended to remove all circular references, so PHP can easily clean
     * it up.
     */
    public function destroy()
    {
        parent::destroy();
        foreach ($this->parameters as $param) {
            $param->destroy();
        }
        $this->parameters = [];
    }
}
