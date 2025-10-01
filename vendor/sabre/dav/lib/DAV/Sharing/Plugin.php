<?php

declare(strict_types=1);

namespace Sabre\DAV\Sharing;

use Sabre\DAV\Exception\BadRequest;
use Sabre\DAV\Exception\Forbidden;
use Sabre\DAV\INode;
use Sabre\DAV\PropFind;
use Sabre\DAV\Server;
use Sabre\DAV\ServerPlugin;
use Sabre\DAV\Xml\Element\Sharee;
use Sabre\DAV\Xml\Property;
use Sabre\HTTP\RequestInterface;
use Sabre\HTTP\ResponseInterface;

/**
 * This plugin implements HTTP requests and properties related to:.
 *
 * draft-pot-webdav-resource-sharing
 *
 * This specification allows people to share webdav resources with others.
 *
 * @copyright Copyright (C) 2007-2015 fruux GmbH. (https://fruux.com/)
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
class Plugin extends ServerPlugin
{
    const ACCESS_NOTSHARED = 0;
    const ACCESS_SHAREDOWNER = 1;
    const ACCESS_READ = 2;
    const ACCESS_READWRITE = 3;
    const ACCESS_NOACCESS = 4;

    const INVITE_NORESPONSE = 1;
    const INVITE_ACCEPTED = 2;
    const INVITE_DECLINED = 3;
    const INVITE_INVALID = 4;

    /**
     * Reference to SabreDAV server object.
     *
     * @var Server
     */
    protected $server;

    /**
     * This method should return a list of server-features.
     *
     * This is for example 'versioning' and is added to the DAV: header
     * in an OPTIONS response.
     *
     * @return array
     */
    public function getFeatures()
    {
        return ['resource-sharing'];
    }

    /**
     * Returns a plugin name.
     *
     * Using this name other plugins will be able to access other plugins
     * using \Sabre\DAV\Server::getPlugin
     *
     * @return string
     */
    public function getPluginName()
    {
        return 'sharing';
    }

    /**
     * This initializes the plugin.
     *
     * This function is called by Sabre\DAV\Server, after
     * addPlugin is called.
     *
     * This method should set up the required event subscriptions.
     */
    public function initialize(Server $server)
    {
        $this->server = $server;

        $server->xml->elementMap['{DAV:}share-resource'] = 'Sabre\\DAV\\Xml\\Request\\ShareResource';

        array_push(
            $server->protectedProperties,
            '{DAV:}share-mode'
        );

        $server->on('method:POST', [$this, 'httpPost']);
        $server->on('propFind', [$this, 'propFind']);
        $server->on('getSupportedPrivilegeSet', [$this, 'getSupportedPrivilegeSet']);
        $server->on('onHTMLActionsPanel', [$this, 'htmlActionsPanel']);
        $server->on('onBrowserPostAction', [$this, 'browserPostAction']);
    }

    /**
     * Updates the list of sharees on a shared resource.
     *
     * The sharees  array is a list of people that are to be added modified
     * or removed in the list of shares.
     *
     * @param string   $path
     * @param Sharee[] $sharees
     */
    public function shareResource($path, array $sharees)
    {
        $node = $this->server->tree->getNodeForPath($path);

        if (!$node instanceof ISharedNode) {
            throw new Forbidden('Sharing is not allowed on this node');
        }

        // Getting ACL info
        $acl = $this->server->getPlugin('acl');

        // If there's no ACL support, we allow everything
        if ($acl) {
            $acl->checkPrivileges($path, '{DAV:}share');
        }

        foreach ($sharees as $sharee) {
            // We're going to attempt to get a local principal uri for a share
            // href by emitting the getPrincipalByUri event.
            $principal = null;
            $this->server->emit('getPrincipalByUri', [$sharee->href, &$principal]);
            $sharee->principal = $principal;
        }
        $node->updateInvites($sharees);
    }

    /**
     * This event is triggered when properties are requested for nodes.
     *
     * This allows us to inject any sharings-specific properties.
     */
    public function propFind(PropFind $propFind, INode $node)
    {
        if ($node instanceof ISharedNode) {
            $propFind->handle('{DAV:}share-access', function () use ($node) {
                return new Property\ShareAccess($node->getShareAccess());
            });
            $propFind->handle('{DAV:}invite', function () use ($node) {
                return new Property\Invite($node->getInvites());
            });
            $propFind->handle('{DAV:}share-resource-uri', function () use ($node) {
                return new Property\Href($node->getShareResourceUri());
            });
        }
    }

    /**
     * We intercept this to handle POST requests on shared resources.
     *
     * @return bool|null
     */
    public function httpPost(RequestInterface $request, ResponseInterface $response)
    {
        $path = $request->getPath();
        $contentType = $request->getHeader('Content-Type');
        if (null === $contentType) {
            return;
        }

        // We're only interested in the davsharing content type.
        if (false === strpos($contentType, 'application/davsharing+xml')) {
            return;
        }

        $message = $this->server->xml->parse(
            $request->getBody(),
            $request->getUrl(),
            $documentType
        );

        switch ($documentType) {
            case '{DAV:}share-resource':
                $this->shareResource($path, $message->sharees);
                $response->setStatus(200);
                // Adding this because sending a response body may cause issues,
                // and I wanted some type of indicator the response was handled.
                $response->setHeader('X-Sabre-Status', 'everything-went-well');

                // Breaking the event chain
                return false;

            default:
                throw new BadRequest('Unexpected document type: '.$documentType.' for this Content-Type');
        }
    }

    /**
     * This method is triggered whenever a subsystem requests the privileges
     * hat are supported on a particular node.
     *
     * We need to add a number of privileges for scheduling purposes.
     */
    public function getSupportedPrivilegeSet(INode $node, array &$supportedPrivilegeSet)
    {
        if ($node instanceof ISharedNode) {
            $supportedPrivilegeSet['{DAV:}share'] = [
                'abstract' => false,
                'aggregates' => [],
            ];
        }
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
            'description' => 'This plugin implements WebDAV resource sharing',
            'link' => 'https://github.com/evert/webdav-sharing',
        ];
    }

    /**
     * This method is used to generate HTML output for the
     * DAV\Browser\Plugin.
     *
     * @param string $output
     * @param string $path
     *
     * @return bool|null
     */
    public function htmlActionsPanel(INode $node, &$output, $path)
    {
        if (!$node instanceof ISharedNode) {
            return;
        }

        $aclPlugin = $this->server->getPlugin('acl');
        if ($aclPlugin) {
            if (!$aclPlugin->checkPrivileges($path, '{DAV:}share', \Sabre\DAVACL\Plugin::R_PARENT, false)) {
                // Sharing is not permitted, we will not draw this interface.
                return;
            }
        }

        $output .= '<tr><td colspan="2"><form method="post" action="">
            <h3>Share this resource</h3>
            <input type="hidden" name="sabreAction" value="share" />
            <label>Share with (uri):</label> <input type="text" name="href" placeholder="mailto:user@example.org"/><br />
            <label>Access</label>
                <select name="access">
                    <option value="readwrite">Read-write</option>
                    <option value="read">Read-only</option>
                    <option value="no-access">Revoke access</option>
                </select><br />
             <input type="submit" value="share" />
            </form>
            </td></tr>';
    }

    /**
     * This method is triggered for POST actions generated by the browser
     * plugin.
     *
     * @param string $path
     * @param string $action
     * @param array  $postVars
     */
    public function browserPostAction($path, $action, $postVars)
    {
        if ('share' !== $action) {
            return;
        }

        if (empty($postVars['href'])) {
            throw new BadRequest('The "href" POST parameter is required');
        }
        if (empty($postVars['access'])) {
            throw new BadRequest('The "access" POST parameter is required');
        }

        $accessMap = [
            'readwrite' => self::ACCESS_READWRITE,
            'read' => self::ACCESS_READ,
            'no-access' => self::ACCESS_NOACCESS,
        ];

        if (!isset($accessMap[$postVars['access']])) {
            throw new BadRequest('The "access" POST must be readwrite, read or no-access');
        }
        $sharee = new Sharee([
            'href' => $postVars['href'],
            'access' => $accessMap[$postVars['access']],
        ]);

        $this->shareResource(
            $path,
            [$sharee]
        );

        return false;
    }
}
