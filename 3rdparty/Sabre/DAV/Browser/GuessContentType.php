<?php

/**
 * GuessContentType plugin
 *
 * A lot of the built-in File objects just return application/octet-stream
 * as a content-type by default. This is a problem for some clients, because
 * they expect a correct contenttype.
 *
 * There's really no accurate, fast and portable way to determine the contenttype
 * so this extension does what the rest of the world does, and guesses it based
 * on the file extension.
 *
 * @package Sabre
 * @subpackage DAV
 * @copyright Copyright (C) 2007-2012 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/) 
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
class Sabre_DAV_Browser_GuessContentType extends Sabre_DAV_ServerPlugin {

    /**
     * List of recognized file extensions
     *
     * Feel free to add more
     *
     * @var array
     */
    public $extensionMap = array(

        // images
        'jpg' => 'image/jpeg',
        'gif' => 'image/gif',
        'png' => 'image/png',

        // groupware
        'ics' => 'text/calendar',
        'vcf' => 'text/x-vcard',

        // text
        'txt' => 'text/plain',

    );

    /**
     * Initializes the plugin
     *
     * @param Sabre_DAV_Server $server
     * @return void
     */
    public function initialize(Sabre_DAV_Server $server) {

        // Using a relatively low priority (200) to allow other extensions
        // to set the content-type first.
        $server->subscribeEvent('afterGetProperties',array($this,'afterGetProperties'),200);

    }

    /**
     * Handler for teh afterGetProperties event
     *
     * @param string $path
     * @param array $properties
     * @return void
     */
    public function afterGetProperties($path, &$properties) {

        if (array_key_exists('{DAV:}getcontenttype', $properties[404])) {

            list(, $fileName) = Sabre_DAV_URLUtil::splitPath($path);
            $contentType = $this->getContentType($fileName);

            if ($contentType) {
                $properties[200]['{DAV:}getcontenttype'] = $contentType;
                unset($properties[404]['{DAV:}getcontenttype']);
            }

        }

    }

    /**
     * Simple method to return the contenttype
     *
     * @param string $fileName
     * @return string
     */
    protected function getContentType($fileName) {

        // Just grabbing the extension
        $extension = strtolower(substr($fileName,strrpos($fileName,'.')+1));
        if (isset($this->extensionMap[$extension]))
            return $this->extensionMap[$extension];

    }

}
