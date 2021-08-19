<?php

namespace Sabre\VObject\Parser\XML\Element;

use Sabre\Xml as SabreXml;

/**
 * Our own sabre/xml key-value element.
 *
 * It just removes the clark notation.
 *
 * @copyright Copyright (C) fruux GmbH (https://fruux.com/)
 * @author Ivan Enderlin
 * @license http://sabre.io/license/ Modified BSD License
 */
class KeyValue extends SabreXml\Element\KeyValue
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
     * @param XML\Reader $reader
     *
     * @return mixed
     */
    public static function xmlDeserialize(SabreXml\Reader $reader)
    {
        // If there's no children, we don't do anything.
        if ($reader->isEmptyElement) {
            $reader->next();

            return [];
        }

        $values = [];
        $reader->read();

        do {
            if (SabreXml\Reader::ELEMENT === $reader->nodeType) {
                $name = $reader->localName;
                $values[$name] = $reader->parseCurrentElement()['value'];
            } else {
                $reader->read();
            }
        } while (SabreXml\Reader::END_ELEMENT !== $reader->nodeType);

        $reader->read();

        return $values;
    }
}
