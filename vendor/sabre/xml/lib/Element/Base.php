<?php

declare(strict_types=1);

namespace Sabre\Xml\Element;

use Sabre\Xml;

/**
 * The Base XML element is the standard parser & generator that's used by the
 * XML reader and writer.
 *
 * It spits out a simple PHP array structure during deserialization, that can
 * also be directly injected back into Writer::write.
 *
 * @copyright Copyright (C) 2009-2015 fruux GmbH (https://fruux.com/).
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
class Base implements Xml\Element
{
    /**
     * PHP value to serialize.
     */
    protected $value;

    /**
     * Constructor.
     */
    public function __construct($value = null)
    {
        $this->value = $value;
    }

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
    public function xmlSerialize(Xml\Writer $writer)
    {
        $writer->write($this->value);
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
     * Important note 2: You are responsible for advancing the reader to the
     * next element. Not doing anything will result in a never-ending loop.
     *
     * If you just want to skip parsing for this element altogether, you can
     * just call $reader->next();
     *
     * $reader->parseInnerTree() will parse the entire sub-tree, and advance to
     * the next element.
     */
    public static function xmlDeserialize(Xml\Reader $reader)
    {
        $subTree = $reader->parseInnerTree();

        return $subTree;
    }
}
