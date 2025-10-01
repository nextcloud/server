<?php

declare(strict_types=1);

namespace Sabre\CalDAV\Xml\Filter;

use Sabre\CalDAV\Plugin;
use Sabre\DAV\Exception\BadRequest;
use Sabre\VObject\DateTimeParser;
use Sabre\Xml\Reader;
use Sabre\Xml\XmlDeserializable;

/**
 * CompFilter parser.
 *
 * This class parses the {urn:ietf:params:xml:ns:caldav}comp-filter XML
 * element, as defined in:
 *
 * https://tools.ietf.org/html/rfc4791#section-9.6
 *
 * The result will be spit out as an array.
 *
 * @copyright Copyright (C) fruux GmbH (https://fruux.com/)
 * @author Evert Pot (http://www.rooftopsolutions.nl/)
 * @license http://sabre.io/license/ Modified BSD License
 */
class CompFilter implements XmlDeserializable
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
            'name' => null,
            'is-not-defined' => false,
            'comp-filters' => [],
            'prop-filters' => [],
            'time-range' => false,
        ];

        $att = $reader->parseAttributes();
        $result['name'] = $att['name'];

        $elems = $reader->parseInnerTree();

        if (is_array($elems)) {
            foreach ($elems as $elem) {
                switch ($elem['name']) {
                case '{'.Plugin::NS_CALDAV.'}comp-filter':
                    $result['comp-filters'][] = $elem['value'];
                    break;
                case '{'.Plugin::NS_CALDAV.'}prop-filter':
                    $result['prop-filters'][] = $elem['value'];
                    break;
                case '{'.Plugin::NS_CALDAV.'}is-not-defined':
                    $result['is-not-defined'] = true;
                    break;
                case '{'.Plugin::NS_CALDAV.'}time-range':
                    if ('VCALENDAR' === $result['name']) {
                        throw new BadRequest('You cannot add time-range filters on the VCALENDAR component');
                    }
                    $result['time-range'] = [
                        'start' => isset($elem['attributes']['start']) ? DateTimeParser::parseDateTime($elem['attributes']['start']) : null,
                        'end' => isset($elem['attributes']['end']) ? DateTimeParser::parseDateTime($elem['attributes']['end']) : null,
                    ];
                    if ($result['time-range']['start'] && $result['time-range']['end'] && $result['time-range']['end'] <= $result['time-range']['start']) {
                        throw new BadRequest('The end-date must be larger than the start-date');
                    }
                    break;
            }
            }
        }

        return $result;
    }
}
