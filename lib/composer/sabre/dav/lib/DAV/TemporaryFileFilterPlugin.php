<?php

declare(strict_types=1);

namespace Sabre\DAV;

use Sabre\HTTP\RequestInterface;
use Sabre\HTTP\ResponseInterface;
use Sabre\Uri;

/**
 * Temporary File Filter Plugin.
 *
 * The purpose of this filter is to intercept some of the garbage files
 * operation systems and applications tend to generate when mounting
 * a WebDAV share as a disk.
 *
 * It will intercept these files and place them in a separate directory.
 * these files are not deleted automatically, so it is advisable to
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
 * @copyright Copyright (C) fruux GmbH (https://fruux.com/)
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
class TemporaryFileFilterPlugin extends ServerPlugin
{
    /**
     * This is the list of patterns we intercept.
     * If new patterns are added, they must be valid patterns for preg_match.
     *
     * @var array
     */
    public $temporaryFilePatterns = [
        '/^\._(.*)$/',     // OS/X resource forks
        '/^.DS_Store$/',   // OS/X custom folder settings
        '/^desktop.ini$/', // Windows custom folder settings
        '/^Thumbs.db$/',   // Windows thumbnail cache
        '/^.(.*).swp$/',   // ViM temporary files
        '/^\.dat(.*)$/',   // Smultron seems to create these
        '/^~lock.(.*)#$/', // Windows 7 lockfiles
    ];

    /**
     * A reference to the main Server class.
     *
     * @var \Sabre\DAV\Server
     */
    protected $server;

    /**
     * This is the directory where this plugin
     * will store it's files.
     *
     * @var string
     */
    private $dataDir;

    /**
     * Creates the plugin.
     *
     * Make sure you specify a directory for your files. If you don't, we
     * will use PHP's directory for session-storage instead, and you might
     * not want that.
     *
     * @param string|null $dataDir
     */
    public function __construct($dataDir = null)
    {
        if (!$dataDir) {
            $dataDir = ini_get('session.save_path').'/sabredav/';
        }
        if (!is_dir($dataDir)) {
            mkdir($dataDir);
        }
        $this->dataDir = $dataDir;
    }

    /**
     * Initialize the plugin.
     *
     * This is called automatically be the Server class after this plugin is
     * added with Sabre\DAV\Server::addPlugin()
     */
    public function initialize(Server $server)
    {
        $this->server = $server;
        $server->on('beforeMethod:*', [$this, 'beforeMethod']);
        $server->on('beforeCreateFile', [$this, 'beforeCreateFile']);
    }

    /**
     * This method is called before any HTTP method handler.
     *
     * This method intercepts any GET, DELETE, PUT and PROPFIND calls to
     * filenames that are known to match the 'temporary file' regex.
     *
     * @return bool
     */
    public function beforeMethod(RequestInterface $request, ResponseInterface $response)
    {
        if (!$tempLocation = $this->isTempFile($request->getPath())) {
            return;
        }

        switch ($request->getMethod()) {
            case 'GET':
                return $this->httpGet($request, $response, $tempLocation);
            case 'PUT':
                return $this->httpPut($request, $response, $tempLocation);
            case 'PROPFIND':
                return $this->httpPropfind($request, $response, $tempLocation);
            case 'DELETE':
                return $this->httpDelete($request, $response, $tempLocation);
        }

        return;
    }

    /**
     * This method is invoked if some subsystem creates a new file.
     *
     * This is used to deal with HTTP LOCK requests which create a new
     * file.
     *
     * @param string   $uri
     * @param resource $data
     * @param bool     $modified should be set to true, if this event handler
     *                           changed &$data
     *
     * @return bool
     */
    public function beforeCreateFile($uri, $data, ICollection $parent, $modified)
    {
        if ($tempPath = $this->isTempFile($uri)) {
            $hR = $this->server->httpResponse;
            $hR->setHeader('X-Sabre-Temp', 'true');
            file_put_contents($tempPath, $data);

            return false;
        }

        return;
    }

    /**
     * This method will check if the url matches the temporary file pattern
     * if it does, it will return an path based on $this->dataDir for the
     * temporary file storage.
     *
     * @param string $path
     *
     * @return bool|string
     */
    protected function isTempFile($path)
    {
        // We're only interested in the basename.
        list(, $tempPath) = Uri\split($path);

        if (null === $tempPath) {
            return false;
        }

        foreach ($this->temporaryFilePatterns as $tempFile) {
            if (preg_match($tempFile, $tempPath)) {
                return $this->getDataDir().'/sabredav_'.md5($path).'.tempfile';
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
     *
     * @return bool
     */
    public function httpGet(RequestInterface $request, ResponseInterface $hR, $tempLocation)
    {
        if (!file_exists($tempLocation)) {
            return;
        }

        $hR->setHeader('Content-Type', 'application/octet-stream');
        $hR->setHeader('Content-Length', filesize($tempLocation));
        $hR->setHeader('X-Sabre-Temp', 'true');
        $hR->setStatus(200);
        $hR->setBody(fopen($tempLocation, 'r'));

        return false;
    }

    /**
     * This method handles the PUT method.
     *
     * @param string $tempLocation
     *
     * @return bool
     */
    public function httpPut(RequestInterface $request, ResponseInterface $hR, $tempLocation)
    {
        $hR->setHeader('X-Sabre-Temp', 'true');

        $newFile = !file_exists($tempLocation);

        if (!$newFile && ($this->server->httpRequest->getHeader('If-None-Match'))) {
            throw new Exception\PreconditionFailed('The resource already exists, and an If-None-Match header was supplied');
        }

        file_put_contents($tempLocation, $this->server->httpRequest->getBody());
        $hR->setStatus($newFile ? 201 : 200);

        return false;
    }

    /**
     * This method handles the DELETE method.
     *
     * If the file didn't exist, it will return false, which will make the
     * standard HTTP DELETE handler kick in.
     *
     * @param string $tempLocation
     *
     * @return bool
     */
    public function httpDelete(RequestInterface $request, ResponseInterface $hR, $tempLocation)
    {
        if (!file_exists($tempLocation)) {
            return;
        }

        unlink($tempLocation);
        $hR->setHeader('X-Sabre-Temp', 'true');
        $hR->setStatus(204);

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
     *
     * @return bool
     */
    public function httpPropfind(RequestInterface $request, ResponseInterface $hR, $tempLocation)
    {
        if (!file_exists($tempLocation)) {
            return;
        }

        $hR->setHeader('X-Sabre-Temp', 'true');
        $hR->setStatus(207);
        $hR->setHeader('Content-Type', 'application/xml; charset=utf-8');

        $properties = [
            'href' => $request->getPath(),
            200 => [
                '{DAV:}getlastmodified' => new Xml\Property\GetLastModified(filemtime($tempLocation)),
                '{DAV:}getcontentlength' => filesize($tempLocation),
                '{DAV:}resourcetype' => new Xml\Property\ResourceType(null),
                '{'.Server::NS_SABREDAV.'}tempFile' => true,
            ],
        ];

        $data = $this->server->generateMultiStatus([$properties]);
        $hR->setBody($data);

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
