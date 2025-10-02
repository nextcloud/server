<?php

namespace Sabre\VObject\Property\VCard;

/**
 * DateTime property.
 *
 * This object encodes DATE-TIME values for vCards.
 *
 * @copyright Copyright (C) fruux GmbH (https://fruux.com/)
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
class DateTime extends DateAndOrTime
{
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
        return 'DATE-TIME';
    }
}
