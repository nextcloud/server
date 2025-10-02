<?php

declare(strict_types=1);

namespace Sabre\DAVACL\Xml\Request;

use Sabre\Xml\Reader;
use Sabre\Xml\XmlDeserializable;

/**
 * ExpandProperty request parser.
 *
 * This class parses the {DAV:}expand-property REPORT, as defined in:
 *
 * http://tools.ietf.org/html/rfc3253#section-3.8
 *
 * @copyright Copyright (C) fruux GmbH (https://fruux.com/)
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
class ExpandPropertyReport implements XmlDeserializable
{
    /**
     * An array with requested properties.
     *
     * The requested properties will be used as keys in this array. The value
     * is normally null.
     *
     * If the value is an array though, it means the property must be expanded.
     * Within the array, the sub-properties, which themselves may be null or
     * arrays.
     *
     * @var array
     */
    public $properties;

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
        $elems = $reader->parseInnerTree();

        $obj = new self();
        $obj->properties = self::traverse($elems);

        return $obj;
    }

    /**
     * This method is used by deserializeXml, to recursively parse the
     * {DAV:}property elements.
     *
     * @param array $elems
     *
     * @return array
     */
    private static function traverse($elems)
    {
        $result = [];

        foreach ($elems as $elem) {
            if ('{DAV:}property' !== $elem['name']) {
                continue;
            }

            $namespace = isset($elem['attributes']['namespace']) ?
                $elem['attributes']['namespace'] :
                'DAV:';

            $propName = '{'.$namespace.'}'.$elem['attributes']['name'];

            $value = null;
            if (is_array($elem['value'])) {
                $value = self::traverse($elem['value']);
            }

            $result[$propName] = $value;
        }

        return $result;
    }
}
