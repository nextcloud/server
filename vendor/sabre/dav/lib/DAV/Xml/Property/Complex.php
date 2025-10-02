<?php

declare(strict_types=1);

namespace Sabre\DAV\Xml\Property;

use Sabre\Xml\Element\XmlFragment;
use Sabre\Xml\Reader;

/**
 * This class represents a 'complex' property that didn't have a default
 * decoder.
 *
 * It's basically a container for an xml snippet.
 *
 * @copyright Copyright (C) fruux GmbH (https://fruux.com/)
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
class Complex extends XmlFragment
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
        $xml = $reader->readInnerXml();

        if (Reader::ELEMENT === $reader->nodeType && $reader->isEmptyElement) {
            // Easy!
            $reader->next();

            return null;
        }
        // Now we have a copy of the inner xml, we need to traverse it to get
        // all the strings. If there's no non-string data, we just return the
        // string, otherwise we return an instance of this class.
        $reader->read();

        $nonText = false;
        $text = '';

        while (true) {
            switch ($reader->nodeType) {
                case Reader::ELEMENT:
                    $nonText = true;
                    $reader->next();
                    continue 2;
                case Reader::TEXT:
                case Reader::CDATA:
                    $text .= $reader->value;
                    break;
                case Reader::END_ELEMENT:
                    break 2;
            }
            $reader->read();
        }

        // Make sure we advance the cursor one step further.
        $reader->read();

        if ($nonText) {
            $new = new self($xml);

            return $new;
        } else {
            return $text;
        }
    }
}
