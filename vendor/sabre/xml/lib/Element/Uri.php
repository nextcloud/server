<?php

declare(strict_types=1);

namespace Sabre\Xml\Element;

use Sabre\Xml;

/**
 * Uri element.
 *
 * This represents a single uri. An example of how this may be encoded:
 *
 *    <link>/foo/bar</link>
 *    <d:href xmlns:d="DAV:">http://example.org/hi</d:href>
 *
 * If the uri is relative, it will be automatically expanded to an absolute
 * url during writing and reading, if the contextUri property is set on the
 * reader and/or writer.
 *
 * @copyright Copyright (C) 2009-2015 fruux GmbH (https://fruux.com/).
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
class Uri implements Xml\Element
{
    /**
     * Uri element value.
     *
     * @var string
     */
    protected $value;

    /**
     * Constructor.
     *
     * @param string $value
     */
    public function __construct($value)
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
        $writer->text(
            \Sabre\Uri\resolve(
                $writer->contextUri,
                $this->value
            )
        );
    }

    /**
     * This method is called during xml parsing.
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
     * $reader->parseSubTree() will parse the entire sub-tree, and advance to
     * the next element.
     */
    public static function xmlDeserialize(Xml\Reader $reader)
    {
        return new self(
            \Sabre\Uri\resolve(
                (string) $reader->contextUri,
                $reader->readText()
            )
        );
    }
}
