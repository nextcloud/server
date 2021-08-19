<?php

declare(strict_types=1);

namespace Sabre\CalDAV\Schedule;

use Sabre\CalDAV;
use Sabre\DAV;
use Sabre\DAVACL;

/**
 * The CalDAV scheduling outbox.
 *
 * The outbox is mainly used as an endpoint in the tree for a client to do
 * free-busy requests. This functionality is completely handled by the
 * Scheduling plugin, so this object is actually mostly static.
 *
 * @copyright Copyright (C) fruux GmbH (https://fruux.com/)
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
class Outbox extends DAV\Collection implements IOutbox
{
    use DAVACL\ACLTrait;

    /**
     * The principal Uri.
     *
     * @var string
     */
    protected $principalUri;

    /**
     * Constructor.
     *
     * @param string $principalUri
     */
    public function __construct($principalUri)
    {
        $this->principalUri = $principalUri;
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
        return 'outbox';
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
     * Returns the owner principal.
     *
     * This must be a url to a principal, or null if there's no owner
     *
     * @return string|null
     */
    public function getOwner()
    {
        return $this->principalUri;
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
                'privilege' => '{'.CalDAV\Plugin::NS_CALDAV.'}schedule-send',
                'principal' => $this->getOwner(),
                'protected' => true,
            ],
            [
                'privilege' => '{DAV:}read',
                'principal' => $this->getOwner(),
                'protected' => true,
            ],
            [
                'privilege' => '{'.CalDAV\Plugin::NS_CALDAV.'}schedule-send',
                'principal' => $this->getOwner().'/calendar-proxy-write',
                'protected' => true,
            ],
            [
                'privilege' => '{DAV:}read',
                'principal' => $this->getOwner().'/calendar-proxy-read',
                'protected' => true,
            ],
            [
                'privilege' => '{DAV:}read',
                'principal' => $this->getOwner().'/calendar-proxy-write',
                'protected' => true,
            ],
        ];
    }
}
