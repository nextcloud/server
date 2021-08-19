<?php

declare(strict_types=1);

namespace Sabre\DAV\Browser;

use Sabre\DAV;
use Sabre\DAV\INode;
use Sabre\DAV\PropFind;
use Sabre\Uri;

/**
 * GuessContentType plugin.
 *
 * A lot of the built-in File objects just return application/octet-stream
 * as a content-type by default. This is a problem for some clients, because
 * they expect a correct contenttype.
 *
 * There's really no accurate, fast and portable way to determine the contenttype
 * so this extension does what the rest of the world does, and guesses it based
 * on the file extension.
 *
 * @copyright Copyright (C) fruux GmbH (https://fruux.com/)
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
class GuessContentType extends DAV\ServerPlugin
{
    /**
     * List of recognized file extensions.
     *
     * Feel free to add more
     *
     * @var array
     */
    public $extensionMap = [
        // images
        'jpg' => 'image/jpeg',
        'gif' => 'image/gif',
        'png' => 'image/png',

        // groupware
        'ics' => 'text/calendar',
        'vcf' => 'text/vcard',

        // text
        'txt' => 'text/plain',
    ];

    /**
     * Initializes the plugin.
     */
    public function initialize(DAV\Server $server)
    {
        // Using a relatively low priority (200) to allow other extensions
        // to set the content-type first.
        $server->on('propFind', [$this, 'propFind'], 200);
    }

    /**
     * Our PROPFIND handler.
     *
     * Here we set a contenttype, if the node didn't already have one.
     */
    public function propFind(PropFind $propFind, INode $node)
    {
        $propFind->handle('{DAV:}getcontenttype', function () use ($propFind) {
            list(, $fileName) = Uri\split($propFind->getPath());

            return $this->getContentType($fileName);
        });
    }

    /**
     * Simple method to return the contenttype.
     *
     * @param string $fileName
     *
     * @return string
     */
    protected function getContentType($fileName)
    {
        if (null !== $fileName) {
            // Just grabbing the extension
            $extension = strtolower(substr($fileName, strrpos($fileName, '.') + 1));
            if (isset($this->extensionMap[$extension])) {
                return $this->extensionMap[$extension];
            }
        }

        return 'application/octet-stream';
    }
}
