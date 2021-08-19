<?php

declare(strict_types=1);

namespace Sabre\DAV\Xml\Request;

use Sabre\Xml\Element;
use Sabre\Xml\Reader;
use Sabre\Xml\Writer;

/**
 * WebDAV PROPPATCH request parser.
 *
 * This class parses the {DAV:}propertyupdate request, as defined in:
 *
 * https://tools.ietf.org/html/rfc4918#section-14.20
 *
 * @copyright Copyright (C) fruux GmbH (https://fruux.com/)
 * @author Evert Pot (http://www.rooftopsolutions.nl/)
 * @license http://sabre.io/license/ Modified BSD License
 */
class PropPatch implements Element
{
    /**
     * The list of properties that will be updated and removed.
     *
     * If a property will be removed, it's value will be set to null.
     *
     * @var array
     */
    public $properties = [];

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
        foreach ($this->properties as $propertyName => $propertyValue) {
            if (is_null($propertyValue)) {
                $writer->startElement('{DAV:}remove');
                $writer->write(['{DAV:}prop' => [$propertyName => $propertyValue]]);
                $writer->endElement();
            } else {
                $writer->startElement('{DAV:}set');
                $writer->write(['{DAV:}prop' => [$propertyName => $propertyValue]]);
                $writer->endElement();
            }
        }
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
        $self = new self();

        $elementMap = $reader->elementMap;
        $elementMap['{DAV:}prop'] = 'Sabre\DAV\Xml\Element\Prop';
        $elementMap['{DAV:}set'] = 'Sabre\Xml\Element\KeyValue';
        $elementMap['{DAV:}remove'] = 'Sabre\Xml\Element\KeyValue';

        $elems = $reader->parseInnerTree($elementMap);

        foreach ($elems as $elem) {
            if ('{DAV:}set' === $elem['name']) {
                $self->properties = array_merge($self->properties, $elem['value']['{DAV:}prop']);
            }
            if ('{DAV:}remove' === $elem['name']) {
                // Ensuring there are no values.
                foreach ($elem['value']['{DAV:}prop'] as $remove => $value) {
                    $self->properties[$remove] = null;
                }
            }
        }

        return $self;
    }
}
