<?php

declare(strict_types=1);

namespace Sabre\CalDAV\Xml\Property;

use Sabre\CalDAV\Plugin;
use Sabre\Xml\Writer;
use Sabre\Xml\XmlSerializable;

/**
 * supported-collation-set property.
 *
 * This property is a representation of the supported-collation-set property
 * in the CalDAV namespace.
 *
 * This property is defined in:
 * http://tools.ietf.org/html/rfc4791#section-7.5.1
 *
 * @copyright Copyright (C) fruux GmbH (https://fruux.com/)
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
class SupportedCollationSet implements XmlSerializable
{
    /**
     * The xmlSerialize method is called during xml writing.
     *
     * Use the $writer argument to write its own xml serialization.
     *
     * An important note: do _not_ create a parent element. Any element
     * implementing XmlSerializable should only ever write what's considered
     * its 'inner xml'.
     *
     * The parent of the current element is responsible for writing a
     * containing element.
     *
     * This allows serializers to be re-used for different element names.
     *
     * If you are opening new elements, you must also close them again.
     */
    public function xmlSerialize(Writer $writer)
    {
        $collations = [
            'i;ascii-casemap',
            'i;octet',
            'i;unicode-casemap',
        ];

        foreach ($collations as $collation) {
            $writer->writeElement('{'.Plugin::NS_CALDAV.'}supported-collation', $collation);
        }
    }
}
