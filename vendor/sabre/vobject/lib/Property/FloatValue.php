<?php

namespace Sabre\VObject\Property;

use Sabre\VObject\Property;
use Sabre\Xml;

/**
 * Float property.
 *
 * This object represents FLOAT values. These can be 1 or more floating-point
 * numbers.
 *
 * @copyright Copyright (C) fruux GmbH (https://fruux.com/)
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
class FloatValue extends Property
{
    /**
     * In case this is a multi-value property. This string will be used as a
     * delimiter.
     *
     * @var string
     */
    public $delimiter = ';';

    /**
     * Sets a raw value coming from a mimedir (iCalendar/vCard) file.
     *
     * This has been 'unfolded', so only 1 line will be passed. Unescaping is
     * not yet done, but parameters are not included.
     *
     * @param string $val
     */
    public function setRawMimeDirValue($val)
    {
        $val = explode($this->delimiter, $val);
        foreach ($val as &$item) {
            $item = (float) $item;
        }
        $this->setParts($val);
    }

    /**
     * Returns a raw mime-dir representation of the value.
     *
     * @return string
     */
    public function getRawMimeDirValue()
    {
        return implode(
            $this->delimiter,
            $this->getParts()
        );
    }

    /**
     * Returns the type of value.
     *
     * This corresponds to the VALUE= parameter. Every property also has a
     * 'default' valueType.
     *
     * @return string
     */
    public function getValueType()
    {
        return 'FLOAT';
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
        $val = array_map('floatval', $this->getParts());

        // Special-casing the GEO property.
        //
        // See:
        // http://tools.ietf.org/html/draft-ietf-jcardcal-jcal-04#section-3.4.1.2
        if ('GEO' === $this->name) {
            return [$val];
        }

        return $val;
    }

    /**
     * Hydrate data from a XML subtree, as it would appear in a xCard or xCal
     * object.
     */
    public function setXmlValue(array $value)
    {
        $value = array_map('floatval', $value);
        parent::setXmlValue($value);
    }

    /**
     * This method serializes only the value of a property. This is used to
     * create xCard or xCal documents.
     *
     * @param Xml\Writer $writer XML writer
     */
    protected function xmlSerializeValue(Xml\Writer $writer)
    {
        // Special-casing the GEO property.
        //
        // See:
        // http://tools.ietf.org/html/rfc6321#section-3.4.1.2
        if ('GEO' === $this->name) {
            $value = array_map('floatval', $this->getParts());

            $writer->writeElement('latitude', $value[0]);
            $writer->writeElement('longitude', $value[1]);
        } else {
            parent::xmlSerializeValue($writer);
        }
    }
}
