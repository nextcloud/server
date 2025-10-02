<?php

declare(strict_types=1);

namespace Sabre\CardDAV\Xml\Request;

use Sabre\CardDAV\Plugin;
use Sabre\Uri;
use Sabre\Xml\Reader;
use Sabre\Xml\XmlDeserializable;

/**
 * AddressBookMultiGetReport request parser.
 *
 * This class parses the {urn:ietf:params:xml:ns:carddav}addressbook-multiget
 * REPORT, as defined in:
 *
 * http://tools.ietf.org/html/rfc6352#section-8.7
 *
 * @copyright Copyright (C) fruux GmbH (https://fruux.com/)
 * @author Evert Pot (http://www.rooftopsolutions.nl/)
 * @license http://sabre.io/license/ Modified BSD License
 */
class AddressBookMultiGetReport implements XmlDeserializable
{
    /**
     * An array with requested properties.
     *
     * @var array
     */
    public $properties;

    /**
     * This is an array with the urls that are being requested.
     *
     * @var array
     */
    public $hrefs;

    /**
     * The mimetype of the content that should be returned. Usually
     * text/vcard.
     *
     * @var string
     */
    public $contentType = null;

    /**
     * The version of vcard data that should be returned. Usually 3.0,
     * referring to vCard 3.0.
     *
     * @var string
     */
    public $version = null;

    /**
     * An array with requested vcard properties.
     *
     * @var array
     */
    public $addressDataProperties;

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
        $elems = $reader->parseInnerTree([
            '{urn:ietf:params:xml:ns:carddav}address-data' => 'Sabre\\CardDAV\\Xml\\Filter\\AddressData',
            '{DAV:}prop' => 'Sabre\\Xml\\Element\\KeyValue',
        ]);

        $newProps = [
            'hrefs' => [],
            'properties' => [],
        ];

        foreach ($elems as $elem) {
            switch ($elem['name']) {
                case '{DAV:}prop':
                    $newProps['properties'] = array_keys($elem['value']);
                    if (isset($elem['value']['{'.Plugin::NS_CARDDAV.'}address-data'])) {
                        $newProps += $elem['value']['{'.Plugin::NS_CARDDAV.'}address-data'];
                    }
                    break;
                case '{DAV:}href':
                    $newProps['hrefs'][] = Uri\resolve($reader->contextUri, $elem['value']);
                    break;
            }
        }

        $obj = new self();
        foreach ($newProps as $key => $value) {
            $obj->$key = $value;
        }

        return $obj;
    }
}
