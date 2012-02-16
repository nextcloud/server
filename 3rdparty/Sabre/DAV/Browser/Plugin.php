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
 * @copyright Copyright (C) 2007-2011 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/)
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
class Sabre_DAV_Browser_Plugin extends Sabre_DAV_ServerPlugin {

    /**
     * reference to server class 
     * 
     * @var Sabre_DAV_Server 
     */
    protected $server;

    /**
     * enableEditing
     * 
     * @var bool 
     */
    protected $enablePost = true;

    /**
     * Creates the object.
     *
     * By default it will allow file creation and uploads.
     * Specify the first argument as false to disable this
     * 
     * @param bool $enablePost 
     * @return void
     */
    public function __construct($enablePost=true) {

        $this->enablePost = $enablePost; 

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
        if ($this->enablePost) $this->server->subscribeEvent('unknownMethod',array($this,'httpPOSTHandler'));
    }

    /**
     * This method intercepts GET requests to collections and returns the html 
     * 
     * @param string $method 
     * @return bool 
     */
    public function httpGetInterceptor($method, $uri) {

        if ($method!='GET') return true;

        try { 
            $node = $this->server->tree->getNodeForPath($uri);
        } catch (Sabre_DAV_Exception_FileNotFound $e) {
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
     * Handles POST requests for tree operations
     * 
     * This method is not yet used.
     * 
     * @param string $method 
     * @return bool
     */
    public function httpPOSTHandler($method, $uri) {

        if ($method!='POST') return true;
        if (isset($_POST['sabreAction'])) switch($_POST['sabreAction']) {

            case 'mkcol' :
                if (isset($_POST['name']) && trim($_POST['name'])) {
                    // Using basename() because we won't allow slashes
                    list(, $folderName) = Sabre_DAV_URLUtil::splitPath(trim($_POST['name']));
                    $this->server->createDirectory($uri . '/' . $folderName);
                }
                break;
            case 'put' :
                if ($_FILES) $file = current($_FILES);
                else break;
                $newName = trim($file['name']);
                list(, $newName) = Sabre_DAV_URLUtil::splitPath(trim($file['name']));
                if (isset($_POST['name']) && trim($_POST['name']))
                    $newName = trim($_POST['name']);

                // Making sure we only have a 'basename' component
                list(, $newName) = Sabre_DAV_URLUtil::splitPath($newName);
                    
               
                if (is_uploaded_file($file['tmp_name'])) {
                    $parent = $this->server->tree->getNodeForPath(trim($uri,'/'));
                    $parent->createFile($newName,fopen($file['tmp_name'],'r'));
                }

        }
        $this->server->httpResponse->setHeader('Location',$this->server->httpRequest->getUri());
        return false;

    }

    /**
     * Escapes a string for html. 
     * 
     * @param string $value 
     * @return void
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

        $html = "<html>
<head>
  <title>Index for " . $this->escapeHTML($path) . "/ - SabreDAV " . Sabre_DAV_Version::VERSION . "</title>
  <style type=\"text/css\"> body { Font-family: arial}</style>
</head>
<body>
  <h1>Index for " . $this->escapeHTML($path) . "/</h1>
  <table>
    <tr><th>Name</th><th>Type</th><th>Size</th><th>Last modified</th></tr>
    <tr><td colspan=\"4\"><hr /></td></tr>";
    
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

        $html.= "<tr>
<td><a href=\"{$fullPath}\">..</a></td>
<td>[parent]</td>
<td></td>
<td></td>
</tr>";

    }

    foreach($files as $k=>$file) {

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

        $name = $this->escapeHTML($name);
        $displayName = $this->escapeHTML($displayName);
        $type = $this->escapeHTML($type);

        $html.= "<tr>
<td><a href=\"{$fullPath}\">{$displayName}</a></td>
<td>{$type}</td>
<td>{$size}</td>
<td>{$lastmodified}</td>
</tr>";

    }

  $html.= "<tr><td colspan=\"4\"><hr /></td></tr>";

  if ($this->enablePost && $parent instanceof Sabre_DAV_ICollection) {
      $html.= '<tr><td><form method="post" action="">
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

  $html.= "</table>
  <address>Generated by SabreDAV " . Sabre_DAV_Version::VERSION ."-". Sabre_DAV_Version::STABILITY . " (c)2007-2011 <a href=\"http://code.google.com/p/sabredav/\">http://code.google.com/p/sabredav/</a></address>
</body>
</html>";

        return $html; 

    }

}
