<?php

declare(strict_types=1);

namespace Sabre\DAV\Xml;

/**
 * XML service for WebDAV.
 *
 * @copyright Copyright (C) fruux GmbH (https://fruux.com/)
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
class Service extends \Sabre\Xml\Service
{
    /**
     * This is a list of XML elements that we automatically map to PHP classes.
     *
     * For instance, this list may contain an entry `{DAV:}propfind` that would
     * be mapped to Sabre\DAV\Xml\Request\PropFind
     */
    public $elementMap = [
        '{DAV:}multistatus' => 'Sabre\\DAV\\Xml\\Response\\MultiStatus',
        '{DAV:}response' => 'Sabre\\DAV\\Xml\\Element\\Response',

        // Requests
        '{DAV:}propfind' => 'Sabre\\DAV\\Xml\\Request\\PropFind',
        '{DAV:}propertyupdate' => 'Sabre\\DAV\\Xml\\Request\\PropPatch',
        '{DAV:}mkcol' => 'Sabre\\DAV\\Xml\\Request\\MkCol',

        // Properties
        '{DAV:}resourcetype' => 'Sabre\\DAV\\Xml\\Property\\ResourceType',
    ];

    /**
     * This is a default list of namespaces.
     *
     * If you are defining your own custom namespace, add it here to reduce
     * bandwidth and improve legibility of xml bodies.
     *
     * @var array
     */
    public $namespaceMap = [
        'DAV:' => 'd',
        'http://sabredav.org/ns' => 's',
    ];
}
