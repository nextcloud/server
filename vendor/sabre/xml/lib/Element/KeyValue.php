<?php

declare(strict_types=1);

namespace Sabre\Xml\Element;

use Sabre\Xml;
use Sabre\Xml\Deserializer;

/**
 * 'KeyValue' parses out all child elements from a single node, and outputs a
 * key=>value struct.
 *
 * Attributes will be removed, and duplicate child elements are discarded.
 * Complex values within the elements will be parsed by the 'standard' parser.
 *
 * For example, KeyValue will parse:
 *
 * <?xml version="1.0"?>
 * <s:root xmlns:s="http://sabredav.org/ns">
 *   <s:elem1>value1</s:elem1>
 *   <s:elem2>value2</s:elem2>
 *   <s:elem3 />
 * </s:root>
 *
 * Into:
 *
 * [
 *   "{http://sabredav.org/ns}elem1" => "value1",
 *   "{http://sabredav.org/ns}elem2" => "value2",
 *   "{http://sabredav.org/ns}elem3" => null,
 * ];
 *
 * @copyright Copyright (C) 2009-2015 fruux GmbH (https://fruux.com/).
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
class KeyValue implements Xml\Element
{
    /**
     * Value to serialize.
     *
     * @var array
     */
    protected $value;

    /**
     * Constructor.
     */
    public function __construct(array $value = [])
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
        return Deserializer\keyValue($reader);
    }
}
