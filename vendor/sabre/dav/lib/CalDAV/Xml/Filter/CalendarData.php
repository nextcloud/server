<?php

declare(strict_types=1);

namespace Sabre\CalDAV\Xml\Filter;

use Sabre\CalDAV\Plugin;
use Sabre\DAV\Exception\BadRequest;
use Sabre\VObject\DateTimeParser;
use Sabre\Xml\Reader;
use Sabre\Xml\XmlDeserializable;

/**
 * CalendarData parser.
 *
 * This class parses the {urn:ietf:params:xml:ns:caldav}calendar-data XML
 * element, as defined in:
 *
 * https://tools.ietf.org/html/rfc4791#section-9.6
 *
 * This element is used in three distinct places in the caldav spec, but in
 * this case, this element class only implements the calendar-data element as
 * it appears in a DAV:prop element, in a calendar-query or calendar-multiget
 * REPORT request.
 *
 * @copyright Copyright (C) fruux GmbH (https://fruux.com/)
 * @author Evert Pot (http://www.rooftopsolutions.nl/)
 * @license http://sabre.io/license/ Modified BSD License
 */
class CalendarData implements XmlDeserializable
{
    /**
     * The deserialize method is called during xml parsing.
     *
     * This method is called statically, this is because in theory this method
     * may be used as a type of constructor, or factory method.
     *
     * Often you want to return an instance of the current class, but you are
     * free to return other data as well.
     *
     * You are responsible for advancing the reader to the next element. Not
     * doing anything will result in a never-ending loop.
     *
     * If you just want to skip parsing for this element altogether, you can
     * just call $reader->next();
     *
     * $reader->parseInnerTree() will parse the entire sub-tree, and advance to
     * the next element.
     *
     * @return mixed
     */
    public static function xmlDeserialize(Reader $reader)
    {
        $result = [
            'contentType' => $reader->getAttribute('content-type') ?: 'text/calendar',
            'version' => $reader->getAttribute('version') ?: '2.0',
        ];

        $elems = (array) $reader->parseInnerTree();
        foreach ($elems as $elem) {
            switch ($elem['name']) {
                case '{'.Plugin::NS_CALDAV.'}expand':
                    $result['expand'] = [
                        'start' => isset($elem['attributes']['start']) ? DateTimeParser::parseDateTime($elem['attributes']['start']) : null,
                        'end' => isset($elem['attributes']['end']) ? DateTimeParser::parseDateTime($elem['attributes']['end']) : null,
                    ];

                    if (!$result['expand']['start'] || !$result['expand']['end']) {
                        throw new BadRequest('The "start" and "end" attributes are required when expanding calendar-data');
                    }
                    if ($result['expand']['end'] <= $result['expand']['start']) {
                        throw new BadRequest('The end-date must be larger than the start-date when expanding calendar-data');
                    }
                    break;
            }
        }

        return $result;
    }
}
