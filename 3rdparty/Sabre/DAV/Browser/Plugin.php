<?php

/**
 * Browser Plugin
 *
 * This plugin provides a html representation, so that a WebDAV server may be accessed
 * using a browser.
 *
 * The class intercepts GET requests to collection resources and generates a simple
 * html index.
 *
 * @package Sabre
 * @subpackage DAV
 * @copyright Copyright (C) 2007-2012 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/)
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
class Sabre_DAV_Browser_Plugin extends Sabre_DAV_ServerPlugin {

    /**
     * List of default icons for nodes.
     *
     * This is an array with class / interface names as keys, and asset names
     * as values.
     *
     * The evaluation order is reversed. The last item in the list gets
     * precendence.
     *
     * @var array
     */
    public $iconMap = array(
        'Sabre_DAV_IFile' => 'icons/file',
        'Sabre_DAV_ICollection' => 'icons/collection',
        'Sabre_DAVACL_IPrincipal' => 'icons/principal',
        'Sabre_CalDAV_ICalendar' => 'icons/calendar',
        'Sabre_CardDAV_IAddressBook' => 'icons/addressbook',
        'Sabre_CardDAV_ICard' => 'icons/card',
    );

    /**
     * The file extension used for all icons
     *
     * @var string
     */
    public $iconExtension = '.png';

    /**
     * reference to server class
     *
     * @var Sabre_DAV_Server
     */
    protected $server;

    /**
     * enablePost turns on the 'actions' panel, which allows people to create
     * folders and upload files straight from a browser.
     *
     * @var bool
     */
    protected $enablePost = true;

    /**
     * By default the browser plugin will generate a favicon and other images.
     * To turn this off, set this property to false.
     *
     * @var bool
     */
    protected $enableAssets = true;

    /**
     * Creates the object.
     *
     * By default it will allow file creation and uploads.
     * Specify the first argument as false to disable this
     *
     * @param bool $enablePost
     * @param bool $enableAssets
     */
    public function __construct($enablePost=true, $enableAssets = true) {

        $this->enablePost = $enablePost;
        $this->enableAssets = $enableAssets;

    }

    /**
     * Initializes the plugin and subscribes to events
     *
     * @param Sabre_DAV_Server $server
     * @return void
     */
    public function initialize(Sabre_DAV_Server $server) {

        $this->server = $server;
        $this->server->subscribeEvent('beforeMethod',array($this,'httpGetInterceptor'));
        $this->server->subscribeEvent('onHTMLActionsPanel', array($this, 'htmlActionsPanel'),200);
        if ($this->enablePost) $this->server->subscribeEvent('unknownMethod',array($this,'httpPOSTHandler'));
    }

    /**
     * This method intercepts GET requests to collections and returns the html
     *
     * @param string $method
     * @param string $uri
     * @return bool
     */
    public function httpGetInterceptor($method, $uri) {

        if ($method !== 'GET') return true;

        // We're not using straight-up $_GET, because we want everything to be
        // unit testable.
        $getVars = array();
        parse_str($this->server->httpRequest->getQueryString(), $getVars);

        if (isset($getVars['sabreAction']) && $getVars['sabreAction'] === 'asset' && isset($getVars['assetName'])) {
            $this->serveAsset($getVars['assetName']);
            return false;
        }

        try {
            $node = $this->server->tree->getNodeForPath($uri);
        } catch (Sabre_DAV_Exception_NotFound $e) {
            // We're simply stopping when the file isn't found to not interfere
            // with other plugins.
            return;
        }
        if ($node instanceof Sabre_DAV_IFile)
            return;

        $this->server->httpResponse->sendStatus(200);
        $this->server->httpResponse->setHeader('Content-Type','text/html; charset=utf-8');

        $this->server->httpResponse->sendBody(
            $this->generateDirectoryIndex($uri)
        );

        return false;

    }

    /**
     * Handles POST requests for tree operations.
     *
     * @param string $method
     * @param string $uri
     * @return bool
     */
    public function httpPOSTHandler($method, $uri) {

        if ($method!='POST') return;
        $contentType = $this->server->httpRequest->getHeader('Content-Type');
        list($contentType) = explode(';', $contentType);
        if ($contentType !== 'application/x-www-form-urlencoded' &&
            $contentType !== 'multipart/form-data') {
                return;
        }
        $postVars = $this->server->httpRequest->getPostVars();

        if (!isset($postVars['sabreAction']))
            return;

        if ($this->server->broadcastEvent('onBrowserPostAction', array($uri, $postVars['sabreAction'], $postVars))) {

            switch($postVars['sabreAction']) {

                case 'mkcol' :
                    if (isset($postVars['name']) && trim($postVars['name'])) {
                        // Using basename() because we won't allow slashes
                        list(, $folderName) = Sabre_DAV_URLUtil::splitPath(trim($postVars['name']));
                        $this->server->createDirectory($uri . '/' . $folderName);
                    }
                    break;
                case 'put' :
                    if ($_FILES) $file = current($_FILES);
                    else break;

                    list(, $newName) = Sabre_DAV_URLUtil::splitPath(trim($file['name']));
                    if (isset($postVars['name']) && trim($postVars['name']))
                        $newName = trim($postVars['name']);

                    // Making sure we only have a 'basename' component
                    list(, $newName) = Sabre_DAV_URLUtil::splitPath($newName);

                    if (is_uploaded_file($file['tmp_name'])) {
                        $this->server->createFile($uri . '/' . $newName, fopen($file['tmp_name'],'r'));
                    }
                    break;

            }

        }
        $this->server->httpResponse->setHeader('Location',$this->server->httpRequest->getUri());
        $this->server->httpResponse->sendStatus(302);
        return false;

    }

    /**
     * Escapes a string for html.
     *
     * @param string $value
     * @return string
     */
    public function escapeHTML($value) {

        return htmlspecialchars($value,ENT_QUOTES,'UTF-8');

    }

    /**
     * Generates the html directory index for a given url
     *
     * @param string $path
     * @return string
     */
    public function generateDirectoryIndex($path) {

        $version = '';
        if (Sabre_DAV_Server::$exposeVersion) {
            $version = Sabre_DAV_Version::VERSION ."-". Sabre_DAV_Version::STABILITY;
        }

        $html = "<html>
<head>
  <title>Index for " . $this->escapeHTML($path) . "/ - SabreDAV " . $version . "</title>
  <style type=\"text/css\">
  body { Font-family: arial}
  h1 { font-size: 150% }
  </style>
        ";

        if ($this->enableAssets) {
            $html.='<link rel="shortcut icon" href="'.$this->getAssetUrl('favicon.ico').'" type="image/vnd.microsoft.icon" />';
        }

        $html .= "</head>
<body>
  <h1>Index for " . $this->escapeHTML($path) . "/</h1>
  <table>
    <tr><th width=\"24\"></th><th>Name</th><th>Type</th><th>Size</th><th>Last modified</th></tr>
    <tr><td colspan=\"5\"><hr /></td></tr>";

        $files = $this->server->getPropertiesForPath($path,array(
            '{DAV:}displayname',
            '{DAV:}resourcetype',
            '{DAV:}getcontenttype',
            '{DAV:}getcontentlength',
            '{DAV:}getlastmodified',
        ),1);

        $parent = $this->server->tree->getNodeForPath($path);


        if ($path) {

            list($parentUri) = Sabre_DAV_URLUtil::splitPath($path);
            $fullPath = Sabre_DAV_URLUtil::encodePath($this->server->getBaseUri() . $parentUri);

            $icon = $this->enableAssets?'<a href="' . $fullPath . '"><img src="' . $this->getAssetUrl('icons/parent' . $this->iconExtension) . '" width="24" alt="Parent" /></a>':'';
            $html.= "<tr>
    <td>$icon</td>
    <td><a href=\"{$fullPath}\">..</a></td>
    <td>[parent]</td>
    <td></td>
    <td></td>
    </tr>";

        }

        foreach($files as $file) {

            // This is the current directory, we can skip it
            if (rtrim($file['href'],'/')==$path) continue;

            list(, $name) = Sabre_DAV_URLUtil::splitPath($file['href']);

            $type = null;


            if (isset($file[200]['{DAV:}resourcetype'])) {
                $type = $file[200]['{DAV:}resourcetype']->getValue();

                // resourcetype can have multiple values
                if (!is_array($type)) $type = array($type);

                foreach($type as $k=>$v) {

                    // Some name mapping is preferred
                    switch($v) {
                        case '{DAV:}collection' :
                            $type[$k] = 'Collection';
                            break;
                        case '{DAV:}principal' :
                            $type[$k] = 'Principal';
                            break;
                        case '{urn:ietf:params:xml:ns:carddav}addressbook' :
                            $type[$k] = 'Addressbook';
                            break;
                        case '{urn:ietf:params:xml:ns:caldav}calendar' :
                            $type[$k] = 'Calendar';
                            break;
                        case '{urn:ietf:params:xml:ns:caldav}schedule-inbox' :
                            $type[$k] = 'Schedule Inbox';
                            break;
                        case '{urn:ietf:params:xml:ns:caldav}schedule-outbox' :
                            $type[$k] = 'Schedule Outbox';
                            break;
                        case '{http://calendarserver.org/ns/}calendar-proxy-read' :
                            $type[$k] = 'Proxy-Read';
                            break;
                        case '{http://calendarserver.org/ns/}calendar-proxy-write' :
                            $type[$k] = 'Proxy-Write';
                            break;
                    }

                }
                $type = implode(', ', $type);
            }

            // If no resourcetype was found, we attempt to use
            // the contenttype property
            if (!$type && isset($file[200]['{DAV:}getcontenttype'])) {
                $type = $file[200]['{DAV:}getcontenttype'];
            }
            if (!$type) $type = 'Unknown';

            $size = isset($file[200]['{DAV:}getcontentlength'])?(int)$file[200]['{DAV:}getcontentlength']:'';
            $lastmodified = isset($file[200]['{DAV:}getlastmodified'])?$file[200]['{DAV:}getlastmodified']->getTime()->format(DateTime::ATOM):'';

            $fullPath = Sabre_DAV_URLUtil::encodePath('/' . trim($this->server->getBaseUri() . ($path?$path . '/':'') . $name,'/'));

            $displayName = isset($file[200]['{DAV:}displayname'])?$file[200]['{DAV:}displayname']:$name;

            $displayName = $this->escapeHTML($displayName);
            $type = $this->escapeHTML($type);

            $icon = '';

            if ($this->enableAssets) {
                $node = $this->server->tree->getNodeForPath(($path?$path.'/':'') . $name);
                foreach(array_reverse($this->iconMap) as $class=>$iconName) {

                    if ($node instanceof $class) {
                        $icon = '<a href="' . $fullPath . '"><img src="' . $this->getAssetUrl($iconName . $this->iconExtension) . '" alt="" width="24" /></a>';
                        break;
                    }


                }

            }

            $html.= "<tr>
    <td>$icon</td>
    <td><a href=\"{$fullPath}\">{$displayName}</a></td>
    <td>{$type}</td>
    <td>{$size}</td>
    <td>{$lastmodified}</td>
    </tr>";

        }

        $html.= "<tr><td colspan=\"5\"><hr /></td></tr>";

        $output = '';

        if ($this->enablePost) {
            $this->server->broadcastEvent('onHTMLActionsPanel',array($parent, &$output));
        }

        $html.=$output;

        $html.= "</table>
        <address>Generated by SabreDAV " . $version . " (c)2007-2012 <a href=\"http://code.google.com/p/sabredav/\">http://code.google.com/p/sabredav/</a></address>
        </body>
        </html>";

        return $html;

    }

    /**
     * This method is used to generate the 'actions panel' output for
     * collections.
     *
     * This specifically generates the interfaces for creating new files, and
     * creating new directories.
     *
     * @param Sabre_DAV_INode $node
     * @param mixed $output
     * @return void
     */
    public function htmlActionsPanel(Sabre_DAV_INode $node, &$output) {

        if (!$node instanceof Sabre_DAV_ICollection)
            return;

        // We also know fairly certain that if an object is a non-extended
        // SimpleCollection, we won't need to show the panel either.
        if (get_class($node)==='Sabre_DAV_SimpleCollection')
            return;

        $output.= '<tr><td colspan="2"><form method="post" action="">
            <h3>Create new folder</h3>
            <input type="hidden" name="sabreAction" value="mkcol" />
            Name: <input type="text" name="name" /><br />
            <input type="submit" value="create" />
            </form>
            <form method="post" action="" enctype="multipart/form-data">
            <h3>Upload file</h3>
            <input type="hidden" name="sabreAction" value="put" />
            Name (optional): <input type="text" name="name" /><br />
            File: <input type="file" name="file" /><br />
            <input type="submit" value="upload" />
            </form>
            </td></tr>';

    }

    /**
     * This method takes a path/name of an asset and turns it into url
     * suiteable for http access.
     *
     * @param string $assetName
     * @return string
     */
    protected function getAssetUrl($assetName) {

        return $this->server->getBaseUri() . '?sabreAction=asset&assetName=' . urlencode($assetName);

    }

    /**
     * This method returns a local pathname to an asset.
     *
     * @param string $assetName
     * @return string
     */
    protected function getLocalAssetPath($assetName) {

        // Making sure people aren't trying to escape from the base path.
        $assetSplit = explode('/', $assetName);
        if (in_array('..',$assetSplit)) {
            throw new Sabre_DAV_Exception('Incorrect asset path');
        }
        $path = __DIR__ . '/assets/' . $assetName;
        return $path;

    }

    /**
     * This method reads an asset from disk and generates a full http response.
     *
     * @param string $assetName
     * @return void
     */
    protected function serveAsset($assetName) {

        $assetPath = $this->getLocalAssetPath($assetName);
        if (!file_exists($assetPath)) {
            throw new Sabre_DAV_Exception_NotFound('Could not find an asset with this name');
        }
        // Rudimentary mime type detection
        switch(strtolower(substr($assetPath,strpos($assetPath,'.')+1))) {

        case 'ico' :
            $mime = 'image/vnd.microsoft.icon';
            break;

        case 'png' :
            $mime = 'image/png';
            break;

        default:
            $mime = 'application/octet-stream';
            break;

        }

        $this->server->httpResponse->setHeader('Content-Type', $mime);
        $this->server->httpResponse->setHeader('Content-Length', filesize($assetPath));
        $this->server->httpResponse->setHeader('Cache-Control', 'public, max-age=1209600');
        $this->server->httpResponse->sendStatus(200);
        $this->server->httpResponse->sendBody(fopen($assetPath,'r'));

    }

}
