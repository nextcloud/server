<?php

declare(strict_types=1);

namespace Sabre\DAV\Xml\Property;

use Sabre\DAV\Browser\HtmlOutput;
use Sabre\DAV\Browser\HtmlOutputHelper;
use Sabre\Uri;
use Sabre\Xml\Element;
use Sabre\Xml\Reader;
use Sabre\Xml\Writer;

/**
 * Href property.
 *
 * This class represents any WebDAV property that contains a {DAV:}href
 * element, and there are many.
 *
 * It can support either 1 or more hrefs. If while unserializing no valid
 * {DAV:}href elements were found, this property will unserialize itself as
 * null.
 *
 * @copyright Copyright (C) fruux GmbH (https://fruux.com/)
 * @author Evert Pot (http://www.rooftopsolutions.nl/)
 * @license http://sabre.io/license/ Modified BSD License
 */
class Href implements Element, HtmlOutput
{
    /**
     * List of uris.
     *
     * @var array
     */
    protected $hrefs;

    /**
     * Automatically prefix the url with the server base directory.
     * Note: use of this property in code was removed in PR:
     * https://github.com/sabre-io/dav/pull/801
     * But the property is left here because old data may still exist
     * that has this property saved.
     * See discussion in issue:
     * https://github.com/sabre-io/Baikal/issues/1154.
     *
     * @var bool
     */
    protected $autoPrefix = true;

    /**
     * Constructor.
     *
     * You must either pass a string for a single href, or an array of hrefs.
     *
     * @param string|string[] $hrefs
     */
    public function __construct($hrefs)
    {
        if (is_string($hrefs)) {
            $hrefs = [$hrefs];
        }
        $this->hrefs = $hrefs;
    }

    /**
     * Returns the first Href.
     *
     * @return string|null
     */
    public function getHref()
    {
        return $this->hrefs[0] ?? null;
    }

    /**
     * Returns the hrefs as an array.
     *
     * @return array
     */
    public function getHrefs()
    {
        return $this->hrefs;
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
        foreach ($this->getHrefs() as $href) {
            $href = Uri\resolve($writer->contextUri, $href);
            $writer->writeElement('{DAV:}href', $href);
        }
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
        $links = [];
        foreach ($this->getHrefs() as $href) {
            $links[] = $html->link($href);
        }

        return implode('<br />', $links);
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
        $hrefs = [];
        foreach ((array) $reader->parseInnerTree() as $elem) {
            if ('{DAV:}href' !== $elem['name']) {
                continue;
            }

            $hrefs[] = $elem['value'];
        }
        if ($hrefs) {
            return new self($hrefs);
        }
    }
}
