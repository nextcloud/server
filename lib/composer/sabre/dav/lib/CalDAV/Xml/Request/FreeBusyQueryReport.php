<?php

declare(strict_types=1);

namespace Sabre\CalDAV\Xml\Request;

use Sabre\CalDAV\Plugin;
use Sabre\DAV\Exception\BadRequest;
use Sabre\VObject\DateTimeParser;
use Sabre\Xml\Reader;
use Sabre\Xml\XmlDeserializable;

/**
 * FreeBusyQueryReport.
 *
 * This class parses the {DAV:}free-busy-query REPORT, as defined in:
 *
 * http://tools.ietf.org/html/rfc3253#section-3.8
 *
 * @copyright Copyright (C) fruux GmbH (https://fruux.com/)
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
class FreeBusyQueryReport implements XmlDeserializable
{
    /**
     * Starttime of report.
     *
     * @var \DateTime|null
     */
    public $start;

    /**
     * End time of report.
     *
     * @var \DateTime|null
     */
    public $end;

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
        $timeRange = '{'.Plugin::NS_CALDAV.'}time-range';

        $start = null;
        $end = null;

        foreach ((array) $reader->parseInnerTree([]) as $elem) {
            if ($elem['name'] !== $timeRange) {
                continue;
            }

            $start = empty($elem['attributes']['start']) ?: $elem['attributes']['start'];
            $end = empty($elem['attributes']['end']) ?: $elem['attributes']['end'];
        }
        if (!$start && !$end) {
            throw new BadRequest('The freebusy report must have a time-range element');
        }
        if ($start) {
            $start = DateTimeParser::parseDateTime($start);
        }
        if ($end) {
            $end = DateTimeParser::parseDateTime($end);
        }
        $result = new self();
        $result->start = $start;
        $result->end = $end;

        return $result;
    }
}
