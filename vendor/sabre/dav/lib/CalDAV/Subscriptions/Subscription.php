<?php

declare(strict_types=1);

namespace Sabre\CalDAV\Subscriptions;

use Sabre\CalDAV\Backend\SubscriptionSupport;
use Sabre\DAV\Collection;
use Sabre\DAV\PropPatch;
use Sabre\DAV\Xml\Property\Href;
use Sabre\DAVACL\ACLTrait;
use Sabre\DAVACL\IACL;

/**
 * Subscription Node.
 *
 * This node represents a subscription.
 *
 * @copyright Copyright (C) fruux GmbH (https://fruux.com/)
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
class Subscription extends Collection implements ISubscription, IACL
{
    use ACLTrait;

    /**
     * caldavBackend.
     *
     * @var SubscriptionSupport
     */
    protected $caldavBackend;

    /**
     * subscriptionInfo.
     *
     * @var array
     */
    protected $subscriptionInfo;

    /**
     * Constructor.
     */
    public function __construct(SubscriptionSupport $caldavBackend, array $subscriptionInfo)
    {
        $this->caldavBackend = $caldavBackend;
        $this->subscriptionInfo = $subscriptionInfo;

        $required = [
            'id',
            'uri',
            'principaluri',
            'source',
            ];

        foreach ($required as $r) {
            if (!isset($subscriptionInfo[$r])) {
                throw new \InvalidArgumentException('The '.$r.' field is required when creating a subscription node');
            }
        }
    }

    /**
     * Returns the name of the node.
     *
     * This is used to generate the url.
     *
     * @return string
     */
    public function getName()
    {
        return $this->subscriptionInfo['uri'];
    }

    /**
     * Returns the last modification time.
     *
     * @return int|null
     */
    public function getLastModified()
    {
        if (isset($this->subscriptionInfo['lastmodified'])) {
            return $this->subscriptionInfo['lastmodified'];
        }
    }

    /**
     * Deletes the current node.
     */
    public function delete()
    {
        $this->caldavBackend->deleteSubscription(
            $this->subscriptionInfo['id']
        );
    }

    /**
     * Returns an array with all the child nodes.
     *
     * @return \Sabre\DAV\INode[]
     */
    public function getChildren()
    {
        return [];
    }

    /**
     * Updates properties on this node.
     *
     * This method received a PropPatch object, which contains all the
     * information about the update.
     *
     * To update specific properties, call the 'handle' method on this object.
     * Read the PropPatch documentation for more information.
     */
    public function propPatch(PropPatch $propPatch)
    {
        return $this->caldavBackend->updateSubscription(
            $this->subscriptionInfo['id'],
            $propPatch
        );
    }

    /**
     * Returns a list of properties for this nodes.
     *
     * The properties list is a list of propertynames the client requested,
     * encoded in clark-notation {xmlnamespace}tagname.
     *
     * If the array is empty, it means 'all properties' were requested.
     *
     * Note that it's fine to liberally give properties back, instead of
     * conforming to the list of requested properties.
     * The Server class will filter out the extra.
     *
     * @param array $properties
     *
     * @return array
     */
    public function getProperties($properties)
    {
        $r = [];

        foreach ($properties as $prop) {
            switch ($prop) {
                case '{http://calendarserver.org/ns/}source':
                    $r[$prop] = new Href($this->subscriptionInfo['source']);
                    break;
                default:
                    if (array_key_exists($prop, $this->subscriptionInfo)) {
                        $r[$prop] = $this->subscriptionInfo[$prop];
                    }
                    break;
            }
        }

        return $r;
    }

    /**
     * Returns the owner principal.
     *
     * This must be a url to a principal, or null if there's no owner
     *
     * @return string|null
     */
    public function getOwner()
    {
        return $this->subscriptionInfo['principaluri'];
    }

    /**
     * Returns a list of ACE's for this node.
     *
     * Each ACE has the following properties:
     *   * 'privilege', a string such as {DAV:}read or {DAV:}write. These are
     *     currently the only supported privileges
     *   * 'principal', a url to the principal who owns the node
     *   * 'protected' (optional), indicating that this ACE is not allowed to
     *      be updated.
     *
     * @return array
     */
    public function getACL()
    {
        return [
            [
                'privilege' => '{DAV:}all',
                'principal' => $this->getOwner(),
                'protected' => true,
            ],
            [
                'privilege' => '{DAV:}all',
                'principal' => $this->getOwner().'/calendar-proxy-write',
                'protected' => true,
            ],
            [
                'privilege' => '{DAV:}read',
                'principal' => $this->getOwner().'/calendar-proxy-read',
                'protected' => true,
            ],
        ];
    }
}
