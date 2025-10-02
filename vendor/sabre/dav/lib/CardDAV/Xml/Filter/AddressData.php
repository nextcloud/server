<?php

declare(strict_types=1);

namespace Sabre\CardDAV\Xml\Filter;

use Sabre\Xml\Reader;
use Sabre\Xml\XmlDeserializable;

/**
 * AddressData parser.
 *
 * This class parses the {urn:ietf:params:xml:ns:carddav}address-data XML
 * element, as defined in:
 *
 * http://tools.ietf.org/html/rfc6352#section-10.4
 *
 * This element is used in two distinct places, but this one specifically
 * encodes the address-data element as it appears in the addressbook-query
 * adressbook-multiget REPORT requests.
 *
 * @copyright Copyright (C) fruux GmbH (https://fruux.com/)
 * @author Evert Pot (http://www.rooftopsolutions.nl/)
 * @license http://sabre.io/license/ Modified BSD License
 */
class AddressData implements XmlDeserializable
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
            'contentType' => $reader->getAttribute('content-type') ?: 'text/vcard',
            'version' => $reader->getAttribute('version') ?: '3.0',
        ];

        $elems = (array) $reader->parseInnerTree();
        $elems = array_filter($elems, function ($element) {
            return '{urn:ietf:params:xml:ns:carddav}prop' === $element['name'] &&
                isset($element['attributes']['name']);
        });
        $result['addressDataProperties'] = array_map(function ($element) {
            return $element['attributes']['name'];
        }, $elems);

        return $result;
    }
}
