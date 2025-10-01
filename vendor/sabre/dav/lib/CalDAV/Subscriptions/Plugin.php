<?php

declare(strict_types=1);

namespace Sabre\CalDAV\Subscriptions;

use Sabre\DAV\INode;
use Sabre\DAV\PropFind;
use Sabre\DAV\Server;
use Sabre\DAV\ServerPlugin;

/**
 * This plugin adds calendar-subscription support to your CalDAV server.
 *
 * Some clients support 'managed subscriptions' server-side. This is basically
 * a list of subscription urls a user is using.
 *
 * @copyright Copyright (C) fruux GmbH (https://fruux.com/)
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
class Plugin extends ServerPlugin
{
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
        $server->resourceTypeMapping['Sabre\\CalDAV\\Subscriptions\\ISubscription'] =
            '{http://calendarserver.org/ns/}subscribed';

        $server->xml->elementMap['{http://calendarserver.org/ns/}source'] =
            'Sabre\\DAV\\Xml\\Property\\Href';

        $server->on('propFind', [$this, 'propFind'], 150);
    }

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
        return ['calendarserver-subscribed'];
    }

    /**
     * Triggered after properties have been fetched.
     */
    public function propFind(PropFind $propFind, INode $node)
    {
        // There's a bunch of properties that must appear as a self-closing
        // xml-element. This event handler ensures that this will be the case.
        $props = [
            '{http://calendarserver.org/ns/}subscribed-strip-alarms',
            '{http://calendarserver.org/ns/}subscribed-strip-attachments',
            '{http://calendarserver.org/ns/}subscribed-strip-todos',
        ];

        foreach ($props as $prop) {
            if (200 === $propFind->getStatus($prop)) {
                $propFind->set($prop, '', 200);
            }
        }
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
        return 'subscriptions';
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
            'description' => 'This plugin allows users to store iCalendar subscriptions in their calendar-home.',
            'link' => null,
        ];
    }
}
