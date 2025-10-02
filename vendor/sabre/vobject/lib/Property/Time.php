<?php

namespace Sabre\VObject\Property;

use Sabre\VObject\DateTimeParser;

/**
 * Time property.
 *
 * This object encodes TIME values.
 *
 * @copyright Copyright (C) fruux GmbH (https://fruux.com/)
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
class Time extends Text
{
    /**
     * In case this is a multi-value property. This string will be used as a
     * delimiter.
     *
     * @var string
     */
    public $delimiter = '';

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
        return 'TIME';
    }

    /**
     * Sets the JSON value, as it would appear in a jCard or jCal object.
     *
     * The value must always be an array.
     */
    public function setJsonValue(array $value)
    {
        // Removing colons from value.
        $value = str_replace(
            ':',
            '',
            $value
        );

        if (1 === count($value)) {
            $this->setValue(reset($value));
        } else {
            $this->setValue($value);
        }
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
        $parts = DateTimeParser::parseVCardTime($this->getValue());
        $timeStr = '';

        // Hour
        if (!is_null($parts['hour'])) {
            $timeStr .= $parts['hour'];

            if (!is_null($parts['minute'])) {
                $timeStr .= ':';
            }
        } else {
            // We know either minute or second _must_ be set, so we insert a
            // dash for an empty value.
            $timeStr .= '-';
        }

        // Minute
        if (!is_null($parts['minute'])) {
            $timeStr .= $parts['minute'];

            if (!is_null($parts['second'])) {
                $timeStr .= ':';
            }
        } else {
            if (isset($parts['second'])) {
                // Dash for empty minute
                $timeStr .= '-';
            }
        }

        // Second
        if (!is_null($parts['second'])) {
            $timeStr .= $parts['second'];
        }

        // Timezone
        if (!is_null($parts['timezone'])) {
            if ('Z' === $parts['timezone']) {
                $timeStr .= 'Z';
            } else {
                $timeStr .=
                    preg_replace('/([0-9]{2})([0-9]{2})$/', '$1:$2', $parts['timezone']);
            }
        }

        return [$timeStr];
    }

    /**
     * Hydrate data from a XML subtree, as it would appear in a xCard or xCal
     * object.
     */
    public function setXmlValue(array $value)
    {
        $value = array_map(
            function ($value) {
                return str_replace(':', '', $value);
            },
            $value
        );
        parent::setXmlValue($value);
    }
}
