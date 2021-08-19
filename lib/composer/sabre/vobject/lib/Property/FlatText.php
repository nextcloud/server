<?php

namespace Sabre\VObject\Property;

/**
 * FlatText property.
 *
 * This object represents certain TEXT values.
 *
 * Specifically, this property is used for text values where there is only 1
 * part. Semi-colons and colons will be de-escaped when deserializing, but if
 * any semi-colons or commas appear without a backslash, we will not assume
 * that they are delimiters.
 *
 * vCard 2.1 specifically has a whole bunch of properties where this may
 * happen, as it only defines a delimiter for a few properties.
 *
 * vCard 4.0 states something similar. An unescaped semi-colon _may_ be a
 * delimiter, depending on the property.
 *
 * @copyright Copyright (C) fruux GmbH (https://fruux.com/)
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
class FlatText extends Text
{
    /**
     * Field separator.
     *
     * @var string
     */
    public $delimiter = ',';

    /**
     * Sets the value as a quoted-printable encoded string.
     *
     * Overriding this so we're not splitting on a ; delimiter.
     *
     * @param string $val
     */
    public function setQuotedPrintableValue($val)
    {
        $val = quoted_printable_decode($val);
        $this->setValue($val);
    }
}
