<?php

/**
 * Temporary File Filter Plugin
 *
 * The purpose of this filter is to intercept some of the garbage files
 * operation systems and applications tend to generate when mounting
 * a WebDAV share as a disk.
 *
 * It will intercept these files and place them in a separate directory.
 * these files are not deleted automatically, so it is adviceable to
 * delete these after they are not accessed for 24 hours.
 *
 * Currently it supports:
 *   * OS/X style resource forks and .DS_Store
 *   * desktop.ini and Thumbs.db (windows)
 *   * .*.swp (vim temporary files)
 *   * .dat.* (smultron temporary files)
 *
 * Additional patterns can be added, by adding on to the
 * temporaryFilePatterns property.
 *
 * @package Sabre
 * @subpackage DAV
 * @copyright Copyright (C) 2007-2012 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/)
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
class Sabre_DAV_TemporaryFileFilterPlugin extends Sabre_DAV_ServerPlugin {

    /**
     * This is the list of patterns we intercept.
     * If new patterns are added, they must be valid patterns for preg_match.
     *
     * @var array
     */
    public $temporaryFilePatterns = array(
        '/^\._(.*)$/',     // OS/X resource forks
        '/^.DS_Store$/',   // OS/X custom folder settings
        '/^desktop.ini$/', // Windows custom folder settings
        '/^Thumbs.db$/',   // Windows thumbnail cache
        '/^.(.*).swp$/',   // ViM temporary files
        '/^\.dat(.*)$/',   // Smultron seems to create these
        '/^~lock.(.*)#$/', // Windows 7 lockfiles
    );

    /**
     * This is the directory where this plugin
     * will store it's files.
     *
     * @var string
     */
    private $dataDir;

    /**
     * A reference to the main Server class
     *
     * @var Sabre_DAV_Server
     */
    private $server;

    /**
     * Creates the plugin.
     *
     * Make sure you specify a directory for your files. If you don't, we
     * will use PHP's directory for session-storage instead, and you might
     * not want that.
     *
     * @param string|null $dataDir
     */
    public function __construct($dataDir = null) {

        if (!$dataDir) $dataDir = ini_get('session.save_path').'/sabredav/';
        if (!is_dir($dataDir)) mkdir($dataDir);
        $this->dataDir = $dataDir;

    }

    /**
     * Initialize the plugin
     *
     * This is called automatically be the Server class after this plugin is
     * added with Sabre_DAV_Server::addPlugin()
     *
     * @param Sabre_DAV_Server $server
     * @return void
     */
    public function initialize(Sabre_DAV_Server $server) {

        $this->server = $server;
        $server->subscribeEvent('beforeMethod',array($this,'beforeMethod'));
        $server->subscribeEvent('beforeCreateFile',array($this,'beforeCreateFile'));

    }

    /**
     * This method is called before any HTTP method handler
     *
     * This method intercepts any GET, DELETE, PUT and PROPFIND calls to
     * filenames that are known to match the 'temporary file' regex.
     *
     * @param string $method
     * @param string $uri
     * @return bool
     */
    public function beforeMethod($method, $uri) {

        if (!$tempLocation = $this->isTempFile($uri))
            return true;

        switch($method) {
            case 'GET' :
                return $this->httpGet($tempLocation);
            case 'PUT' :
                return $this->httpPut($tempLocation);
            case 'PROPFIND' :
                return $this->httpPropfind($tempLocation, $uri);
            case 'DELETE' :
                return $this->httpDelete($tempLocation);
         }
         return true;

    }

    /**
     * This method is invoked if some subsystem creates a new file.
     *
     * This is used to deal with HTTP LOCK requests which create a new
     * file.
     *
     * @param string $uri
     * @param resource $data
     * @return bool
     */
    public function beforeCreateFile($uri,$data) {

        if ($tempPath = $this->isTempFile($uri)) {

            $hR = $this->server->httpResponse;
            $hR->setHeader('X-Sabre-Temp','true');
            file_put_contents($tempPath,$data);
            return false;
        }
        return true;

    }

    /**
     * This method will check if the url matches the temporary file pattern
     * if it does, it will return an path based on $this->dataDir for the
     * temporary file storage.
     *
     * @param string $path
     * @return boolean|string
     */
    protected function isTempFile($path) {

        // We're only interested in the basename.
        list(, $tempPath) = Sabre_DAV_URLUtil::splitPath($path);

        foreach($this->temporaryFilePatterns as $tempFile) {

            if (preg_match($tempFile,$tempPath)) {
                return $this->getDataDir() . '/sabredav_' . md5($path) . '.tempfile';
            }

        }

        return false;

    }


    /**
     * This method handles the GET method for temporary files.
     * If the file doesn't exist, it will return false which will kick in
     * the regular system for the GET method.
     *
     * @param string $tempLocation
     * @return bool
     */
    public function httpGet($tempLocation) {

        if (!file_exists($tempLocation)) return true;

        $hR = $this->server->httpResponse;
        $hR->setHeader('Content-Type','application/octet-stream');
        $hR->setHeader('Content-Length',filesize($tempLocation));
        $hR->setHeader('X-Sabre-Temp','true');
        $hR->sendStatus(200);
        $hR->sendBody(fopen($tempLocation,'r'));
        return false;

    }

    /**
     * This method handles the PUT method.
     *
     * @param string $tempLocation
     * @return bool
     */
    public function httpPut($tempLocation) {

        $hR = $this->server->httpResponse;
        $hR->setHeader('X-Sabre-Temp','true');

        $newFile = !file_exists($tempLocation);

        if (!$newFile && ($this->server->httpRequest->getHeader('If-None-Match'))) {
             throw new Sabre_DAV_Exception_PreconditionFailed('The resource already exists, and an If-None-Match header was supplied');
        }

        file_put_contents($tempLocation,$this->server->httpRequest->getBody());
        $hR->sendStatus($newFile?201:200);
        return false;

    }

    /**
     * This method handles the DELETE method.
     *
     * If the file didn't exist, it will return false, which will make the
     * standard HTTP DELETE handler kick in.
     *
     * @param string $tempLocation
     * @return bool
     */
    public function httpDelete($tempLocation) {

        if (!file_exists($tempLocation)) return true;

        unlink($tempLocation);
        $hR = $this->server->httpResponse;
        $hR->setHeader('X-Sabre-Temp','true');
        $hR->sendStatus(204);
        return false;

    }

    /**
     * This method handles the PROPFIND method.
     *
     * It's a very lazy method, it won't bother checking the request body
     * for which properties were requested, and just sends back a default
     * set of properties.
     *
     * @param string $tempLocation
     * @param string $uri
     * @return bool
     */
    public function httpPropfind($tempLocation, $uri) {

        if (!file_exists($tempLocation)) return true;

        $hR = $this->server->httpResponse;
        $hR->setHeader('X-Sabre-Temp','true');
        $hR->sendStatus(207);
        $hR->setHeader('Content-Type','application/xml; charset=utf-8');

        $this->server->parsePropFindRequest($this->server->httpRequest->getBody(true));

        $properties = array(
            'href' => $uri,
            200 => array(
                '{DAV:}getlastmodified' => new Sabre_DAV_Property_GetLastModified(filemtime($tempLocation)),
                '{DAV:}getcontentlength' => filesize($tempLocation),
                '{DAV:}resourcetype' => new Sabre_DAV_Property_ResourceType(null),
                '{'.Sabre_DAV_Server::NS_SABREDAV.'}tempFile' => true,

            ),
         );

        $data = $this->server->generateMultiStatus(array($properties));
        $hR->sendBody($data);
        return false;

    }


    /**
     * This method returns the directory where the temporary files should be stored.
     *
     * @return string
     */
    protected function getDataDir()
    {
        return $this->dataDir;
    }
}
