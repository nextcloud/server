<?php

namespace Sabre\VObject\Property\ICalendar;

use Sabre\VObject\Property\Text;

/**
 * CalAddress property.
 *
 * This object encodes CAL-ADDRESS values, as defined in rfc5545
 *
 * @copyright Copyright (C) fruux GmbH (https://fruux.com/)
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
class CalAddress extends Text
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
        return 'CAL-ADDRESS';
    }

    /**
     * This returns a normalized form of the value.
     *
     * This is primarily used right now to turn mixed-cased schemes in user
     * uris to lower-case.
     *
     * Evolution in particular tends to encode mailto: as MAILTO:.
     *
     * @return string
     */
    public function getNormalizedValue()
    {
        $input = $this->getValue();
        if (!strpos($input, ':')) {
            return $input;
        }
        list($schema, $everythingElse) = explode(':', $input, 2);
        $schema = strtolower($schema);
        if ('mailto' === $schema) {
            $everythingElse = strtolower($everythingElse);
        }

        return $schema.':'.$everythingElse;
    }
}
