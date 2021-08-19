<?php

declare(strict_types=1);

namespace Sabre\DAVACL\Xml\Property;

use Sabre\DAV\Browser\HtmlOutput;
use Sabre\DAV\Browser\HtmlOutputHelper;
use Sabre\Xml\Element;
use Sabre\Xml\Reader;
use Sabre\Xml\Writer;

/**
 * CurrentUserPrivilegeSet.
 *
 * This class represents the current-user-privilege-set property. When
 * requested, it contain all the privileges a user has on a specific node.
 *
 * @copyright Copyright (C) fruux GmbH (https://fruux.com/)
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
class CurrentUserPrivilegeSet implements Element, HtmlOutput
{
    /**
     * List of privileges.
     *
     * @var array
     */
    private $privileges;

    /**
     * Creates the object.
     *
     * Pass the privileges in clark-notation
     */
    public function __construct(array $privileges)
    {
        $this->privileges = $privileges;
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
    public function xmlSerialize(Writer $writer)
    {
        foreach ($this->privileges as $privName) {
            $writer->startElement('{DAV:}privilege');
            $writer->writeElement($privName);
            $writer->endElement();
        }
    }

    /**
     * Returns true or false, whether the specified principal appears in the
     * list.
     *
     * @param string $privilegeName
     *
     * @return bool
     */
    public function has($privilegeName)
    {
        return in_array($privilegeName, $this->privileges);
    }

    /**
     * Returns the list of privileges.
     *
     * @return array
     */
    public function getValue()
    {
        return $this->privileges;
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
        $result = [];

        $tree = $reader->parseInnerTree(['{DAV:}privilege' => 'Sabre\\Xml\\Element\\Elements']);
        foreach ($tree as $element) {
            if ('{DAV:}privilege' !== $element['name']) {
                continue;
            }
            $result[] = $element['value'][0];
        }

        return new self($result);
    }

    /**
     * Generate html representation for this value.
     *
     * The html output is 100% trusted, and no effort is being made to sanitize
     * it. It's up to the implementor to sanitize user provided values.
     *
     * The output must be in UTF-8.
     *
     * The baseUri parameter is a url to the root of the application, and can
     * be used to construct local links.
     *
     * @return string
     */
    public function toHtml(HtmlOutputHelper $html)
    {
        return implode(
            ', ',
            array_map([$html, 'xmlName'], $this->getValue())
        );
    }
}
