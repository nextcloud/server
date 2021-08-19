<?php

declare(strict_types=1);

namespace Sabre\DAV\Xml\Property;

use Sabre\HTTP;

/**
 * LocalHref property.
 *
 * Like the Href property, this element represents {DAV:}href. The difference
 * is that this is used strictly for paths on the server. The LocalHref property
 * will prepare the path so it's a valid URI.
 *
 * These two objects behave identically:
 *    new LocalHref($path)
 *    new Href(\Sabre\HTTP\encodePath($path))
 *
 * LocalPath basically ensures that your spaces are %20, and everything that
 * needs to be is uri encoded.
 *
 * @copyright Copyright (C) fruux GmbH (https://fruux.com/)
 * @author Evert Pot (http://www.rooftopsolutions.nl/)
 * @license http://sabre.io/license/ Modified BSD License
 */
class LocalHref extends Href
{
    /**
     * Constructor.
     *
     * You must either pass a string for a single href, or an array of hrefs.
     *
     * If auto-prefix is set to false, the hrefs will be treated as absolute
     * and not relative to the servers base uri.
     *
     * @param string|string[] $hrefs
     */
    public function __construct($hrefs)
    {
        parent::__construct(array_map(
            function ($href) {
                return \Sabre\HTTP\encodePath($href);
            },
            (array) $hrefs
        ));
    }
}
