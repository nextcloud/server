<?php

namespace Sabre\VObject\Property;

use Sabre\VObject\Property;

/**
 * BINARY property.
 *
 * This object represents BINARY values.
 *
 * Binary values are most commonly used by the iCalendar ATTACH property, and
 * the vCard PHOTO property.
 *
 * This property will transparently encode and decode to base64.
 *
 * @copyright Copyright (C) fruux GmbH (https://fruux.com/)
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
class Binary extends Property
{
    /**
     * In case this is a multi-value property. This string will be used as a
     * delimiter.
     *
     * @var string|null
     */
    public $delimiter = null;

    /**
     * Updates the current value.
     *
     * This may be either a single, or multiple strings in an array.
     *
     * @param string|array $value
     */
    public function setValue($value)
    {
        if (is_array($value)) {
            if (1 === count($value)) {
                $this->value = $value[0];
            } else {
                throw new \InvalidArgumentException('The argument must either be a string or an array with only one child');
            }
        } else {
            $this->value = $value;
        }
    }

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
        $this->value = base64_decode($val);
    }

    /**
     * Returns a raw mime-dir representation of the value.
     *
     * @return string
     */
    public function getRawMimeDirValue()
    {
        return base64_encode($this->value);
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
        return 'BINARY';
    }

    /**
     * Returns the value, in the format it should be encoded for json.
     *
     * This method must always return an array.
     *
     * @return array
     */
    public function getJsonValue()
    {
        return [base64_encode($this->getValue())];
    }

    /**
     * Sets the json value, as it would appear in a jCard or jCal object.
     *
     * The value must always be an array.
     */
    public function setJsonValue(array $value)
    {
        $value = array_map('base64_decode', $value);
        parent::setJsonValue($value);
    }
}
