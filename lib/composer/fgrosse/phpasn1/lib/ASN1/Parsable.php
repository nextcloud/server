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

use FG\ASN1\Exception\ParserException;

/**
 * The Parsable interface describes classes that can be parsed from their binary DER representation.
 */
interface Parsable
{
    /**
     * Parse an instance of this class from its binary DER encoded representation.
     *
     * @param string $binaryData
     * @param int $offsetIndex the offset at which parsing of the $binaryData is started. This parameter ill be modified
     *            to contain the offset index of the next object after this object has been parsed
     *
     * @throws ParserException if the given binary data is either invalid or not currently supported
     *
     * @return static
     */
    public static function fromBinary(&$binaryData, &$offsetIndex = null);
}
