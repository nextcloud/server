<?php

declare(strict_types=1);

namespace Sabre\Xml\Element;

use Sabre\Xml\Element;
use Sabre\Xml\Reader;
use Sabre\Xml\Writer;

/**
 * The XmlFragment element allows you to extract a portion of your xml tree,
 * and get a well-formed xml string.
 *
 * This goes a bit beyond `innerXml` and friends, as we'll also match all the
 * correct namespaces.
 *
 * Please note that the XML fragment:
 *
 * 1. Will not have an <?xml declaration.
 * 2. Or a DTD
 * 3. It will have all the relevant xmlns attributes.
 * 4. It may not have a root element.
 */
class XmlFragment implements Element
{
    /**
     * The inner XML value.
     *
     * @var string
     */
    protected $xml;

    /**
     * Constructor.
     */
    public function __construct(string $xml)
    {
        $this->xml = $xml;
    }

    /**
     * Returns the inner XML document.
     */
    public function getXml(): string
    {
        return $this->xml;
    }

    /**
     * The xmlSerialize metod is called during xml writing.
     *
     * Use the $writer argument to write its own xml serialization.
     *
     * An important note: do _not_ create a parent element. Any element
     * implementing XmlSerializble should only ever write what's considered
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
        $reader = new Reader();

        // Wrapping the xml in a container, so root-less values can still be
        // parsed.
        $xml = <<<XML
<?xml version="1.0"?>
<xml-fragment xmlns="http://sabre.io/ns">{$this->getXml()}</xml-fragment>
XML;

        $reader->xml($xml);

        while ($reader->read()) {
            if ($reader->depth < 1) {
                // Skipping the root node.
                continue;
            }

            switch ($reader->nodeType) {
                case Reader::ELEMENT:
                    $writer->startElement(
                        (string) $reader->getClark()
                    );
                    $empty = $reader->isEmptyElement;
                    while ($reader->moveToNextAttribute()) {
                        switch ($reader->namespaceURI) {
                            case '':
                                $writer->writeAttribute($reader->localName, $reader->value);
                                break;
                            case 'http://www.w3.org/2000/xmlns/':
                                // Skip namespace declarations
                                break;
                            default:
                                $writer->writeAttribute((string) $reader->getClark(), $reader->value);
                                break;
                        }
                    }
                    if ($empty) {
                        $writer->endElement();
                    }
                    break;
                case Reader::CDATA:
                case Reader::TEXT:
                    $writer->text(
                        $reader->value
                    );
                    break;
                case Reader::END_ELEMENT:
                    $writer->endElement();
                    break;
            }
        }
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
        $result = new self($reader->readInnerXml());
        $reader->next();

        return $result;
    }
}
