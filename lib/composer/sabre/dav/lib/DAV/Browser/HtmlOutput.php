<?php

declare(strict_types=1);

namespace Sabre\DAV\Browser;

/**
 * WebDAV properties that implement this interface are able to generate their
 * own html output for the browser plugin.
 *
 * This is only useful for display purposes, and might make it a bit easier for
 * people to read and understand the value of some properties.
 *
 * @copyright Copyright (C) fruux GmbH (https://fruux.com/)
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
interface HtmlOutput
{
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
    public function toHtml(HtmlOutputHelper $html);
}
