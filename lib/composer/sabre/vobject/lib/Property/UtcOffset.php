<?php

namespace Sabre\VObject\Property;

/**
 * UtcOffset property.
 *
 * This object encodes UTC-OFFSET values.
 *
 * @copyright Copyright (C) fruux GmbH (https://fruux.com/)
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
class UtcOffset extends Text
{
    /**
     * In case this is a multi-value property. This string will be used as a
     * delimiter.
     *
     * @var string|null
     */
    public $delimiter = null;

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
        return 'UTC-OFFSET';
    }

    /**
     * Sets the JSON value, as it would appear in a jCard or jCal object.
     *
     * The value must always be an array.
     */
    public function setJsonValue(array $value)
    {
        $value = array_map(
            function ($value) {
                return str_replace(':', '', $value);
            },
            $value
        );
        parent::setJsonValue($value);
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
        return array_map(
            function ($value) {
                return substr($value, 0, -2).':'.
                       substr($value, -2);
            },
            parent::getJsonValue()
        );
    }
}
