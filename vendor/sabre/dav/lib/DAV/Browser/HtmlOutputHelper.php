<?php

declare(strict_types=1);

namespace Sabre\DAV\Browser;

use Sabre\Uri;
use Sabre\Xml\Service as XmlService;

/**
 * This class provides a few utility functions for easily generating HTML for
 * the browser plugin.
 *
 * @copyright Copyright (C) fruux GmbH (https://fruux.com/)
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
class HtmlOutputHelper
{
    /**
     * Link to the root of the application.
     *
     * @var string
     */
    protected $baseUri;

    /**
     * List of xml namespaces.
     *
     * @var array
     */
    protected $namespaceMap;

    /**
     * Creates the object.
     *
     * baseUri must point to the root of the application. This will be used to
     * easily generate links.
     *
     * The namespaceMap contains an array with the list of xml namespaces and
     * their prefixes. WebDAV uses a lot of XML with complex namespaces, so
     * that can be used to make output a lot shorter.
     *
     * @param string $baseUri
     */
    public function __construct($baseUri, array $namespaceMap)
    {
        $this->baseUri = $baseUri;
        $this->namespaceMap = $namespaceMap;
    }

    /**
     * Generates a 'full' url based on a relative one.
     *
     * For relative urls, the base of the application is taken as the reference
     * url, not the 'current url of the current request'.
     *
     * Absolute urls are left alone.
     *
     * @param string $path
     *
     * @return string
     */
    public function fullUrl($path)
    {
        return Uri\resolve($this->baseUri, $path);
    }

    /**
     * Escape string for HTML output.
     *
     * @param scalar $input
     *
     * @return string
     */
    public function h($input)
    {
        return htmlspecialchars((string) $input, ENT_COMPAT, 'UTF-8');
    }

    /**
     * Generates a full <a>-tag.
     *
     * Url is automatically expanded. If label is not specified, we re-use the
     * url.
     *
     * @param string $url
     * @param string $label
     *
     * @return string
     */
    public function link($url, $label = null)
    {
        $url = $this->h($this->fullUrl($url));

        return '<a href="'.$url.'">'.($label ? $this->h($label) : $url).'</a>';
    }

    /**
     * This method takes an xml element in clark-notation, and turns it into a
     * shortened version with a prefix, if it was a known namespace.
     *
     * @param string $element
     *
     * @return string
     */
    public function xmlName($element)
    {
        list($ns, $localName) = XmlService::parseClarkNotation($element);
        if (isset($this->namespaceMap[$ns])) {
            $propName = $this->namespaceMap[$ns].':'.$localName;
        } else {
            $propName = $element;
        }

        return '<span title="'.$this->h($element).'">'.$this->h($propName).'</span>';
    }
}
