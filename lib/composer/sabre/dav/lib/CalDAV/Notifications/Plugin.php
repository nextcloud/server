<?php

declare(strict_types=1);

namespace Sabre\CalDAV\Notifications;

use Sabre\DAV;
use Sabre\DAV\INode as BaseINode;
use Sabre\DAV\PropFind;
use Sabre\DAV\Server;
use Sabre\DAV\ServerPlugin;
use Sabre\DAVACL;
use Sabre\HTTP\RequestInterface;
use Sabre\HTTP\ResponseInterface;

/**
 * Notifications plugin.
 *
 * This plugin implements several features required by the caldav-notification
 * draft specification.
 *
 * Before version 2.1.0 this functionality was part of Sabre\CalDAV\Plugin but
 * this has since been split up.
 *
 * @copyright Copyright (C) fruux GmbH (https://fruux.com/)
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
class Plugin extends ServerPlugin
{
    /**
     * This is the namespace for the proprietary calendarserver extensions.
     */
    const NS_CALENDARSERVER = 'http://calendarserver.org/ns/';

    /**
     * Reference to the main server object.
     *
     * @var Server
     */
    protected $server;

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
        return 'notifications';
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
        $server->on('method:GET', [$this, 'httpGet'], 90);
        $server->on('propFind', [$this, 'propFind']);

        $server->xml->namespaceMap[self::NS_CALENDARSERVER] = 'cs';
        $server->resourceTypeMapping['\\Sabre\\CalDAV\\Notifications\\ICollection'] = '{'.self::NS_CALENDARSERVER.'}notification';

        array_push($server->protectedProperties,
            '{'.self::NS_CALENDARSERVER.'}notification-URL',
            '{'.self::NS_CALENDARSERVER.'}notificationtype'
        );
    }

    /**
     * PropFind.
     */
    public function propFind(PropFind $propFind, BaseINode $node)
    {
        $caldavPlugin = $this->server->getPlugin('caldav');

        if ($node instanceof DAVACL\IPrincipal) {
            $principalUrl = $node->getPrincipalUrl();

            // notification-URL property
            $propFind->handle('{'.self::NS_CALENDARSERVER.'}notification-URL', function () use ($principalUrl, $caldavPlugin) {
                $notificationPath = $caldavPlugin->getCalendarHomeForPrincipal($principalUrl).'/notifications/';

                return new DAV\Xml\Property\Href($notificationPath);
            });
        }

        if ($node instanceof INode) {
            $propFind->handle(
                '{'.self::NS_CALENDARSERVER.'}notificationtype',
                [$node, 'getNotificationType']
            );
        }
    }

    /**
     * This event is triggered before the usual GET request handler.
     *
     * We use this to intercept GET calls to notification nodes, and return the
     * proper response.
     */
    public function httpGet(RequestInterface $request, ResponseInterface $response)
    {
        $path = $request->getPath();

        try {
            $node = $this->server->tree->getNodeForPath($path);
        } catch (DAV\Exception\NotFound $e) {
            return;
        }

        if (!$node instanceof INode) {
            return;
        }

        $writer = $this->server->xml->getWriter();
        $writer->contextUri = $this->server->getBaseUri();
        $writer->openMemory();
        $writer->startDocument('1.0', 'UTF-8');
        $writer->startElement('{http://calendarserver.org/ns/}notification');
        $node->getNotificationType()->xmlSerializeFull($writer);
        $writer->endElement();

        $response->setHeader('Content-Type', 'application/xml');
        $response->setHeader('ETag', $node->getETag());
        $response->setStatus(200);
        $response->setBody($writer->outputMemory());

        // Return false to break the event chain.
        return false;
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
            'description' => 'Adds support for caldav-notifications, which is required to enable caldav-sharing.',
            'link' => 'http://sabre.io/dav/caldav-sharing/',
        ];
    }
}
