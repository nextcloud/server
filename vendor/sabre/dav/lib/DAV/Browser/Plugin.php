<?php

declare(strict_types=1);

namespace Sabre\DAV\Browser;

use Sabre\DAV;
use Sabre\DAV\MkCol;
use Sabre\HTTP;
use Sabre\HTTP\RequestInterface;
use Sabre\HTTP\ResponseInterface;
use Sabre\Uri;

/**
 * Browser Plugin.
 *
 * This plugin provides a html representation, so that a WebDAV server may be accessed
 * using a browser.
 *
 * The class intercepts GET requests to collection resources and generates a simple
 * html index.
 *
 * @copyright Copyright (C) fruux GmbH (https://fruux.com/)
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
class Plugin extends DAV\ServerPlugin
{
    /**
     * reference to server class.
     *
     * @var DAV\Server
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
     * A list of properties that are usually not interesting. This can cut down
     * the browser output a bit by removing the properties that most people
     * will likely not want to see.
     *
     * @var array
     */
    public $uninterestingProperties = [
        '{DAV:}supportedlock',
        '{DAV:}acl-restrictions',
//        '{DAV:}supported-privilege-set',
        '{DAV:}supported-method-set',
    ];

    /**
     * Creates the object.
     *
     * By default it will allow file creation and uploads.
     * Specify the first argument as false to disable this
     *
     * @param bool $enablePost
     */
    public function __construct($enablePost = true)
    {
        $this->enablePost = $enablePost;
    }

    /**
     * Initializes the plugin and subscribes to events.
     */
    public function initialize(DAV\Server $server)
    {
        $this->server = $server;
        $this->server->on('method:GET', [$this, 'httpGetEarly'], 90);
        $this->server->on('method:GET', [$this, 'httpGet'], 200);
        $this->server->on('onHTMLActionsPanel', [$this, 'htmlActionsPanel'], 200);
        if ($this->enablePost) {
            $this->server->on('method:POST', [$this, 'httpPOST']);
        }
    }

    /**
     * This method intercepts GET requests that have ?sabreAction=info
     * appended to the URL.
     */
    public function httpGetEarly(RequestInterface $request, ResponseInterface $response)
    {
        $params = $request->getQueryParameters();
        if (isset($params['sabreAction']) && 'info' === $params['sabreAction']) {
            return $this->httpGet($request, $response);
        }
    }

    /**
     * This method intercepts GET requests to collections and returns the html.
     *
     * @return bool
     */
    public function httpGet(RequestInterface $request, ResponseInterface $response)
    {
        // We're not using straight-up $_GET, because we want everything to be
        // unit testable.
        $getVars = $request->getQueryParameters();

        // CSP headers
        $response->setHeader('Content-Security-Policy', "default-src 'none'; img-src 'self'; style-src 'self'; font-src 'self';");

        $sabreAction = isset($getVars['sabreAction']) ? $getVars['sabreAction'] : null;

        switch ($sabreAction) {
            case 'asset':
                // Asset handling, such as images
                $this->serveAsset(isset($getVars['assetName']) ? $getVars['assetName'] : null);

                return false;
            default:
            case 'info':
                try {
                    $this->server->tree->getNodeForPath($request->getPath());
                } catch (DAV\Exception\NotFound $e) {
                    // We're simply stopping when the file isn't found to not interfere
                    // with other plugins.
                    return;
                }

                $response->setStatus(200);
                $response->setHeader('Content-Type', 'text/html; charset=utf-8');

                $response->setBody(
                    $this->generateDirectoryIndex($request->getPath())
                );

                return false;

            case 'plugins':
                $response->setStatus(200);
                $response->setHeader('Content-Type', 'text/html; charset=utf-8');

                $response->setBody(
                    $this->generatePluginListing()
                );

                return false;
        }
    }

    /**
     * Handles POST requests for tree operations.
     *
     * @return bool
     */
    public function httpPOST(RequestInterface $request, ResponseInterface $response)
    {
        $contentType = $request->getHeader('Content-Type');
        if (!\is_string($contentType)) {
            return;
        }
        list($contentType) = explode(';', $contentType);
        if ('application/x-www-form-urlencoded' !== $contentType &&
            'multipart/form-data' !== $contentType) {
            return;
        }
        $postVars = $request->getPostData();

        if (!isset($postVars['sabreAction'])) {
            return;
        }

        $uri = $request->getPath();

        if ($this->server->emit('onBrowserPostAction', [$uri, $postVars['sabreAction'], $postVars])) {
            switch ($postVars['sabreAction']) {
                case 'mkcol':
                    if (isset($postVars['name']) && trim($postVars['name'])) {
                        // Using basename() because we won't allow slashes
                        list(, $folderName) = Uri\split(trim($postVars['name']));

                        if (isset($postVars['resourceType'])) {
                            $resourceType = explode(',', $postVars['resourceType']);
                        } else {
                            $resourceType = ['{DAV:}collection'];
                        }

                        $properties = [];
                        foreach ($postVars as $varName => $varValue) {
                            // Any _POST variable in clark notation is treated
                            // like a property.
                            if ('{' === $varName[0]) {
                                // PHP will convert any dots to underscores.
                                // This leaves us with no way to differentiate
                                // the two.
                                // Therefore we replace the string *DOT* with a
                                // real dot. * is not allowed in uris so we
                                // should be good.
                                $varName = str_replace('*DOT*', '.', $varName);
                                $properties[$varName] = $varValue;
                            }
                        }

                        $mkCol = new MkCol(
                            $resourceType,
                            $properties
                        );
                        $this->server->createCollection($uri.'/'.$folderName, $mkCol);
                    }
                    break;

                // @codeCoverageIgnoreStart
                case 'put':
                    if ($_FILES) {
                        $file = current($_FILES);
                    } else {
                        break;
                    }

                    list(, $newName) = Uri\split(trim($file['name']));
                    if (isset($postVars['name']) && trim($postVars['name'])) {
                        $newName = trim($postVars['name']);
                    }

                    // Making sure we only have a 'basename' component
                    list(, $newName) = Uri\split($newName);

                    if (is_uploaded_file($file['tmp_name'])) {
                        $this->server->createFile($uri.'/'.$newName, fopen($file['tmp_name'], 'r'));
                    }
                    break;
                // @codeCoverageIgnoreEnd
            }
        }
        $response->setHeader('Location', $request->getUrl());
        $response->setStatus(302);

        return false;
    }

    /**
     * Escapes a string for html.
     *
     * @param string $value
     *
     * @return string
     */
    public function escapeHTML($value)
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }

    /**
     * Generates the html directory index for a given url.
     *
     * @param string $path
     *
     * @return string
     */
    public function generateDirectoryIndex($path)
    {
        $html = $this->generateHeader($path ? $path : '/', $path);

        $node = $this->server->tree->getNodeForPath($path);
        if ($node instanceof DAV\ICollection) {
            $html .= "<section><h1>Nodes</h1>\n";
            $html .= '<table class="nodeTable">';

            $subNodes = $this->server->getPropertiesForChildren($path, [
                '{DAV:}displayname',
                '{DAV:}resourcetype',
                '{DAV:}getcontenttype',
                '{DAV:}getcontentlength',
                '{DAV:}getlastmodified',
            ]);

            foreach ($subNodes as $subPath => $subProps) {
                $subNode = $this->server->tree->getNodeForPath($subPath);
                $fullPath = $this->server->getBaseUri().HTTP\encodePath($subPath);
                list(, $displayPath) = Uri\split($subPath);

                $subNodes[$subPath]['subNode'] = $subNode;
                $subNodes[$subPath]['fullPath'] = $fullPath;
                $subNodes[$subPath]['displayPath'] = $displayPath;
            }
            uasort($subNodes, [$this, 'compareNodes']);

            foreach ($subNodes as $subProps) {
                $type = [
                    'string' => 'Unknown',
                    'icon' => 'cog',
                ];
                if (isset($subProps['{DAV:}resourcetype'])) {
                    $type = $this->mapResourceType($subProps['{DAV:}resourcetype']->getValue(), $subProps['subNode']);
                }

                $html .= '<tr>';
                $html .= '<td class="nameColumn"><a href="'.$this->escapeHTML($subProps['fullPath']).'"><span class="oi" data-glyph="'.$this->escapeHTML($type['icon']).'"></span> '.$this->escapeHTML($subProps['displayPath']).'</a></td>';
                $html .= '<td class="typeColumn">'.$this->escapeHTML($type['string']).'</td>';
                $html .= '<td>';
                if (isset($subProps['{DAV:}getcontentlength'])) {
                    $html .= $this->escapeHTML($subProps['{DAV:}getcontentlength'].' bytes');
                }
                $html .= '</td><td>';
                if (isset($subProps['{DAV:}getlastmodified'])) {
                    $lastMod = $subProps['{DAV:}getlastmodified']->getTime();
                    $html .= $this->escapeHTML($lastMod->format('F j, Y, g:i a'));
                }
                $html .= '</td><td>';
                if (isset($subProps['{DAV:}displayname'])) {
                    $html .= $this->escapeHTML($subProps['{DAV:}displayname']);
                }
                $html .= '</td>';

                $buttonActions = '';
                if ($subProps['subNode'] instanceof DAV\IFile) {
                    $buttonActions = '<a href="'.$this->escapeHTML($subProps['fullPath']).'?sabreAction=info"><span class="oi" data-glyph="info"></span></a>';
                }
                $this->server->emit('browserButtonActions', [$subProps['fullPath'], $subProps['subNode'], &$buttonActions]);

                $html .= '<td>'.$buttonActions.'</td>';
                $html .= '</tr>';
            }

            $html .= '</table>';
        }

        $html .= '</section>';
        $html .= '<section><h1>Properties</h1>';
        $html .= '<table class="propTable">';

        // Allprops request
        $propFind = new PropFindAll($path);
        $properties = $this->server->getPropertiesByNode($propFind, $node);

        $properties = $propFind->getResultForMultiStatus()[200];

        foreach ($properties as $propName => $propValue) {
            if (!in_array($propName, $this->uninterestingProperties)) {
                $html .= $this->drawPropertyRow($propName, $propValue);
            }
        }

        $html .= '</table>';
        $html .= '</section>';

        /* Start of generating actions */

        $output = '';
        if ($this->enablePost) {
            $this->server->emit('onHTMLActionsPanel', [$node, &$output, $path]);
        }

        if ($output) {
            $html .= '<section><h1>Actions</h1>';
            $html .= "<div class=\"actions\">\n";
            $html .= $output;
            $html .= "</div>\n";
            $html .= "</section>\n";
        }

        $html .= $this->generateFooter();

        $this->server->httpResponse->setHeader('Content-Security-Policy', "default-src 'none'; img-src 'self'; style-src 'self'; font-src 'self';");

        return $html;
    }

    /**
     * Generates the 'plugins' page.
     *
     * @return string
     */
    public function generatePluginListing()
    {
        $html = $this->generateHeader('Plugins');

        $html .= '<section><h1>Plugins</h1>';
        $html .= '<table class="propTable">';
        foreach ($this->server->getPlugins() as $plugin) {
            $info = $plugin->getPluginInfo();
            $html .= '<tr><th>'.$info['name'].'</th>';
            $html .= '<td>'.$info['description'].'</td>';
            $html .= '<td>';
            if (isset($info['link']) && $info['link']) {
                $html .= '<a href="'.$this->escapeHTML($info['link']).'"><span class="oi" data-glyph="book"></span></a>';
            }
            $html .= '</td></tr>';
        }
        $html .= '</table>';
        $html .= '</section>';

        /* Start of generating actions */

        $html .= $this->generateFooter();

        return $html;
    }

    /**
     * Generates the first block of HTML, including the <head> tag and page
     * header.
     *
     * Returns footer.
     *
     * @param string $title
     * @param string $path
     *
     * @return string
     */
    public function generateHeader($title, $path = null)
    {
        $version = '';
        if (DAV\Server::$exposeVersion) {
            $version = DAV\Version::VERSION;
        }

        $vars = [
            'title' => $this->escapeHTML($title),
            'favicon' => $this->escapeHTML($this->getAssetUrl('favicon.ico')),
            'style' => $this->escapeHTML($this->getAssetUrl('sabredav.css')),
            'iconstyle' => $this->escapeHTML($this->getAssetUrl('openiconic/open-iconic.css')),
            'logo' => $this->escapeHTML($this->getAssetUrl('sabredav.png')),
            'baseUrl' => $this->server->getBaseUri(),
        ];

        $html = <<<HTML
<!DOCTYPE html>
<html>
<head>
    <title>$vars[title] - sabre/dav $version</title>
    <link rel="shortcut icon" href="$vars[favicon]"   type="image/vnd.microsoft.icon" />
    <link rel="stylesheet"    href="$vars[style]"     type="text/css" />
    <link rel="stylesheet"    href="$vars[iconstyle]" type="text/css" />

</head>
<body>
    <header>
        <div class="logo">
            <a href="$vars[baseUrl]"><img src="$vars[logo]" alt="sabre/dav" /> $vars[title]</a>
        </div>
    </header>

    <nav>
HTML;

        // If the path is empty, there's no parent.
        if ($path) {
            list($parentUri) = Uri\split($path);
            $fullPath = $this->server->getBaseUri().HTTP\encodePath($parentUri);
            $html .= '<a href="'.$fullPath.'" class="btn">⇤ Go to parent</a>';
        } else {
            $html .= '<span class="btn disabled">⇤ Go to parent</span>';
        }

        $html .= ' <a href="?sabreAction=plugins" class="btn"><span class="oi" data-glyph="puzzle-piece"></span> Plugins</a>';

        $html .= '</nav>';

        return $html;
    }

    /**
     * Generates the page footer.
     *
     * Returns html.
     *
     * @return string
     */
    public function generateFooter()
    {
        $version = '';
        if (DAV\Server::$exposeVersion) {
            $version = DAV\Version::VERSION;
        }
        $year = date('Y');

        return <<<HTML
<footer>Generated by SabreDAV $version (c)2007-$year <a href="http://sabre.io/">http://sabre.io/</a></footer>
</body>
</html>
HTML;
    }

    /**
     * This method is used to generate the 'actions panel' output for
     * collections.
     *
     * This specifically generates the interfaces for creating new files, and
     * creating new directories.
     *
     * @param mixed  $output
     * @param string $path
     */
    public function htmlActionsPanel(DAV\INode $node, &$output, $path)
    {
        if (!$node instanceof DAV\ICollection) {
            return;
        }

        // We also know fairly certain that if an object is a non-extended
        // SimpleCollection, we won't need to show the panel either.
        if ('Sabre\\DAV\\SimpleCollection' === get_class($node)) {
            return;
        }

        $output .= <<<HTML
<form method="post" action="">
<h3>Create new folder</h3>
<input type="hidden" name="sabreAction" value="mkcol" />
<label>Name:</label> <input type="text" name="name" /><br />
<input type="submit" value="create" />
</form>
<form method="post" action="" enctype="multipart/form-data">
<h3>Upload file</h3>
<input type="hidden" name="sabreAction" value="put" />
<label>Name (optional):</label> <input type="text" name="name" /><br />
<label>File:</label> <input type="file" name="file" /><br />
<input type="submit" value="upload" />
</form>
HTML;
    }

    /**
     * This method takes a path/name of an asset and turns it into url
     * suitable for http access.
     *
     * @param string $assetName
     *
     * @return string
     */
    protected function getAssetUrl($assetName)
    {
        return $this->server->getBaseUri().'?sabreAction=asset&assetName='.urlencode($assetName);
    }

    /**
     * This method returns a local pathname to an asset.
     *
     * @param string $assetName
     *
     * @throws DAV\Exception\NotFound
     *
     * @return string
     */
    protected function getLocalAssetPath($assetName)
    {
        $assetDir = __DIR__.'/assets/';
        $path = $assetDir.$assetName;

        // Making sure people aren't trying to escape from the base path.
        $path = str_replace('\\', '/', $path);
        if (false !== strpos($path, '/../') || '/..' === strrchr($path, '/')) {
            throw new DAV\Exception\NotFound('Path does not exist, or escaping from the base path was detected');
        }
        $realPath = realpath($path);
        if ($realPath && 0 === strpos($realPath, realpath($assetDir)) && file_exists($path)) {
            return $path;
        }
        throw new DAV\Exception\NotFound('Path does not exist, or escaping from the base path was detected');
    }

    /**
     * This method reads an asset from disk and generates a full http response.
     *
     * @param string $assetName
     */
    protected function serveAsset($assetName)
    {
        $assetPath = $this->getLocalAssetPath($assetName);

        // Rudimentary mime type detection
        $mime = 'application/octet-stream';
        $map = [
            'ico' => 'image/vnd.microsoft.icon',
            'png' => 'image/png',
            'css' => 'text/css',
        ];

        $ext = substr($assetName, strrpos($assetName, '.') + 1);
        if (isset($map[$ext])) {
            $mime = $map[$ext];
        }

        $this->server->httpResponse->setHeader('Content-Type', $mime);
        $this->server->httpResponse->setHeader('Content-Length', filesize($assetPath));
        $this->server->httpResponse->setHeader('Cache-Control', 'public, max-age=1209600');
        $this->server->httpResponse->setStatus(200);
        $this->server->httpResponse->setBody(fopen($assetPath, 'r'));
    }

    /**
     * Sort helper function: compares two directory entries based on type and
     * display name. Collections sort above other types.
     *
     * @param array $a
     * @param array $b
     *
     * @return int
     */
    protected function compareNodes($a, $b)
    {
        $typeA = (isset($a['{DAV:}resourcetype']))
            ? (in_array('{DAV:}collection', $a['{DAV:}resourcetype']->getValue()))
            : false;

        $typeB = (isset($b['{DAV:}resourcetype']))
            ? (in_array('{DAV:}collection', $b['{DAV:}resourcetype']->getValue()))
            : false;

        // If same type, sort alphabetically by filename:
        if ($typeA === $typeB) {
            return strnatcasecmp($a['displayPath'], $b['displayPath']);
        }

        return ($typeA < $typeB) ? 1 : -1;
    }

    /**
     * Maps a resource type to a human-readable string and icon.
     *
     * @param DAV\INode $node
     *
     * @return array
     */
    private function mapResourceType(array $resourceTypes, $node)
    {
        if (!$resourceTypes) {
            if ($node instanceof DAV\IFile) {
                return [
                    'string' => 'File',
                    'icon' => 'file',
                ];
            } else {
                return [
                    'string' => 'Unknown',
                    'icon' => 'cog',
                ];
            }
        }

        $types = [
            '{http://calendarserver.org/ns/}calendar-proxy-write' => [
                'string' => 'Proxy-Write',
                'icon' => 'people',
            ],
            '{http://calendarserver.org/ns/}calendar-proxy-read' => [
                'string' => 'Proxy-Read',
                'icon' => 'people',
            ],
            '{urn:ietf:params:xml:ns:caldav}schedule-outbox' => [
                'string' => 'Outbox',
                'icon' => 'inbox',
            ],
            '{urn:ietf:params:xml:ns:caldav}schedule-inbox' => [
                'string' => 'Inbox',
                'icon' => 'inbox',
            ],
            '{urn:ietf:params:xml:ns:caldav}calendar' => [
                'string' => 'Calendar',
                'icon' => 'calendar',
            ],
            '{http://calendarserver.org/ns/}shared-owner' => [
                'string' => 'Shared',
                'icon' => 'calendar',
            ],
            '{http://calendarserver.org/ns/}subscribed' => [
                'string' => 'Subscription',
                'icon' => 'calendar',
            ],
            '{urn:ietf:params:xml:ns:carddav}directory' => [
                'string' => 'Directory',
                'icon' => 'globe',
            ],
            '{urn:ietf:params:xml:ns:carddav}addressbook' => [
                'string' => 'Address book',
                'icon' => 'book',
            ],
            '{DAV:}principal' => [
                'string' => 'Principal',
                'icon' => 'person',
            ],
            '{DAV:}collection' => [
                'string' => 'Collection',
                'icon' => 'folder',
            ],
        ];

        $info = [
            'string' => [],
            'icon' => 'cog',
        ];
        foreach ($resourceTypes as $k => $resourceType) {
            if (isset($types[$resourceType])) {
                $info['string'][] = $types[$resourceType]['string'];
            } else {
                $info['string'][] = $resourceType;
            }
        }
        foreach ($types as $key => $resourceInfo) {
            if (in_array($key, $resourceTypes)) {
                $info['icon'] = $resourceInfo['icon'];
                break;
            }
        }
        $info['string'] = implode(', ', $info['string']);

        return $info;
    }

    /**
     * Draws a table row for a property.
     *
     * @param string $name
     * @param mixed  $value
     *
     * @return string
     */
    private function drawPropertyRow($name, $value)
    {
        $html = new HtmlOutputHelper(
            $this->server->getBaseUri(),
            $this->server->xml->namespaceMap
        );

        return '<tr><th>'.$html->xmlName($name).'</th><td>'.$this->drawPropertyValue($html, $value).'</td></tr>';
    }

    /**
     * Draws a table row for a property.
     *
     * @param HtmlOutputHelper $html
     * @param mixed            $value
     *
     * @return string
     */
    private function drawPropertyValue($html, $value)
    {
        if (is_scalar($value)) {
            return $html->h($value);
        } elseif ($value instanceof HtmlOutput) {
            return $value->toHtml($html);
        } elseif ($value instanceof \Sabre\Xml\XmlSerializable) {
            // There's no default html output for this property, we're going
            // to output the actual xml serialization instead.
            $xml = $this->server->xml->write('{DAV:}root', $value, $this->server->getBaseUri());
            // removing first and last line, as they contain our root
            // element.
            $xml = explode("\n", $xml);
            $xml = array_slice($xml, 2, -2);

            return '<pre>'.$html->h(implode("\n", $xml)).'</pre>';
        } else {
            return '<em>unknown</em>';
        }
    }

    /**
     * Returns a plugin name.
     *
     * Using this name other plugins will be able to access other plugins;
     * using \Sabre\DAV\Server::getPlugin
     *
     * @return string
     */
    public function getPluginName()
    {
        return 'browser';
    }

    /**
     * Returns a bunch of meta-data about the plugin.
     *
     * Providing this information is optional, and is mainly displayed by the
     * Browser plugin.
     *
     * The description key in the returned array may contain html and will not
     * be sanitized.
     *
     * @return array
     */
    public function getPluginInfo()
    {
        return [
            'name' => $this->getPluginName(),
            'description' => 'Generates HTML indexes and debug information for your sabre/dav server',
            'link' => 'http://sabre.io/dav/browser-plugin/',
        ];
    }
}
