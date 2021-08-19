<?php

declare(strict_types=1);

namespace Sabre\CalDAV\Xml\Filter;

use Sabre\CalDAV\Plugin;
use Sabre\Xml\Reader;
use Sabre\Xml\XmlDeserializable;

/**
 * PropFilter parser.
 *
 * This class parses the {urn:ietf:params:xml:ns:caldav}param-filter XML
 * element, as defined in:
 *
 * https://tools.ietf.org/html/rfc4791#section-9.7.3
 *
 * The result will be spit out as an array.
 *
 * @copyright Copyright (C) fruux GmbH (https://fruux.com/)
 * @author Evert Pot (http://www.rooftopsolutions.nl/)
 * @license http://sabre.io/license/ Modified BSD License
 */
class ParamFilter implements XmlDeserializable
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
     * Important note 2: You are responsible for advancing the reader to the
     * next element. Not doing anything will result in a never-ending loop.
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
            'text-match' => null,
        ];

        $att = $reader->parseAttributes();
        $result['name'] = $att['name'];

        $elems = $reader->parseInnerTree();

        if (is_array($elems)) {
            foreach ($elems as $elem) {
                switch ($elem['name']) {
                case '{'.Plugin::NS_CALDAV.'}is-not-defined':
                    $result['is-not-defined'] = true;
                    break;
                case '{'.Plugin::NS_CALDAV.'}text-match':
                    $result['text-match'] = [
                        'negate-condition' => isset($elem['attributes']['negate-condition']) && 'yes' === $elem['attributes']['negate-condition'],
                        'collation' => isset($elem['attributes']['collation']) ? $elem['attributes']['collation'] : 'i;ascii-casemap',
                        'value' => $elem['value'],
                    ];
                    break;
            }
            }
        }

        return $result;
    }
}
