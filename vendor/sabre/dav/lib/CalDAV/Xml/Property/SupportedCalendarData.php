<?php

declare(strict_types=1);

namespace Sabre\CalDAV\Xml\Property;

use Sabre\CalDAV\Plugin;
use Sabre\Xml\Writer;
use Sabre\Xml\XmlSerializable;

/**
 * Supported-calendar-data property.
 *
 * This property is a representation of the supported-calendar-data property
 * in the CalDAV namespace. SabreDAV only has support for text/calendar;2.0
 * so the value is currently hardcoded.
 *
 * This property is defined in:
 * http://tools.ietf.org/html/rfc4791#section-5.2.4
 *
 * @copyright Copyright (C) fruux GmbH (https://fruux.com/)
 * @author Evert Pot (http://www.rooftopsolutions.nl/)
 * @license http://sabre.io/license/ Modified BSD License
 */
class SupportedCalendarData implements XmlSerializable
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
        $writer->startElement('{'.Plugin::NS_CALDAV.'}calendar-data');
        $writer->writeAttributes([
            'content-type' => 'text/calendar',
            'version' => '2.0',
        ]);
        $writer->endElement(); // calendar-data
        $writer->startElement('{'.Plugin::NS_CALDAV.'}calendar-data');
        $writer->writeAttributes([
            'content-type' => 'application/calendar+json',
        ]);
        $writer->endElement(); // calendar-data
    }
}
