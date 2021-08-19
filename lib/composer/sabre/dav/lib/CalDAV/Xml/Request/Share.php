<?php

declare(strict_types=1);

namespace Sabre\CalDAV\Xml\Request;

use Sabre\CalDAV\Plugin;
use Sabre\DAV\Xml\Element\Sharee;
use Sabre\Xml\Reader;
use Sabre\Xml\XmlDeserializable;

/**
 * Share POST request parser.
 *
 * This class parses the share POST request, as defined in:
 *
 * http://svn.calendarserver.org/repository/calendarserver/CalendarServer/trunk/doc/Extensions/caldav-sharing.txt
 *
 * @copyright Copyright (C) fruux GmbH (https://fruux.com/)
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
class Share implements XmlDeserializable
{
    /**
     * The list of new people added or updated or removed from the share.
     *
     * @var Sharee[]
     */
    public $sharees = [];

    /**
     * Constructor.
     *
     * @param Sharee[] $sharees
     */
    public function __construct(array $sharees)
    {
        $this->sharees = $sharees;
    }

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
        $elems = $reader->parseGetElements([
            '{'.Plugin::NS_CALENDARSERVER.'}set' => 'Sabre\\Xml\\Element\\KeyValue',
            '{'.Plugin::NS_CALENDARSERVER.'}remove' => 'Sabre\\Xml\\Element\\KeyValue',
        ]);

        $sharees = [];

        foreach ($elems as $elem) {
            switch ($elem['name']) {
                case '{'.Plugin::NS_CALENDARSERVER.'}set':
                    $sharee = $elem['value'];

                    $sumElem = '{'.Plugin::NS_CALENDARSERVER.'}summary';
                    $commonName = '{'.Plugin::NS_CALENDARSERVER.'}common-name';

                    $properties = [];
                    if (isset($sharee[$commonName])) {
                        $properties['{DAV:}displayname'] = $sharee[$commonName];
                    }

                    $access = array_key_exists('{'.Plugin::NS_CALENDARSERVER.'}read-write', $sharee)
                        ? \Sabre\DAV\Sharing\Plugin::ACCESS_READWRITE
                        : \Sabre\DAV\Sharing\Plugin::ACCESS_READ;

                    $sharees[] = new Sharee([
                        'href' => $sharee['{DAV:}href'],
                        'properties' => $properties,
                        'access' => $access,
                        'comment' => isset($sharee[$sumElem]) ? $sharee[$sumElem] : null,
                    ]);
                    break;

                case '{'.Plugin::NS_CALENDARSERVER.'}remove':
                    $sharees[] = new Sharee([
                        'href' => $elem['value']['{DAV:}href'],
                        'access' => \Sabre\DAV\Sharing\Plugin::ACCESS_NOACCESS,
                    ]);
                    break;
            }
        }

        return new self($sharees);
    }
}
