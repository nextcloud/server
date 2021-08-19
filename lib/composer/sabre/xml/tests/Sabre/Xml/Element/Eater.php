<?php

declare(strict_types=1);

namespace Sabre\Xml\Element;

use Sabre\Xml;

/**
 * The intention for this reader class, is to read past the end element. This
 * should trigger a ParseException.
 *
 * @copyright Copyright (C) 2009-2015 fruux GmbH (https://fruux.com/).
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
class Eater implements Xml\Element
{
    /**
     * The serialize method is called during xml writing.
     *
     * It should use the $writer argument to encode this object into Xml.
     *
     * Important note: it is not needed to create the parent element. The
     * parent element is already created, and we only have to worry about
     * attributes, child elements and text (if any).
     *
     * Important note 2: If you are writing any new elements, you are also
     * responsible for closing them.
     */
    public function xmlSerialize(Xml\Writer $writer)
    {
        $writer->startElement('{http://sabredav.org/ns}elem1');
        $writer->write('hiiii!');
        $writer->endElement();
    }

    /**
     * The deserialize method is called during xml parsing.
     *
     * This method is called statictly, this is because in theory this method
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
     * $reader->parseSubTree() will parse the entire sub-tree, and advance to
     * the next element.
     *
     * @return mixed
     */
    public static function xmlDeserialize(Xml\Reader $reader)
    {
        $reader->next();

        $count = 1;
        while ($count) {
            $reader->read();
            if ($reader->nodeType === $reader::END_ELEMENT) {
                --$count;
            }
        }
        $reader->read();
    }
}
