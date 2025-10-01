<?php

declare(strict_types=1);

namespace Sabre\CalDAV\Notifications;

use Sabre\CalDAV;
use Sabre\CalDAV\Xml\Notification\NotificationInterface;
use Sabre\DAV;
use Sabre\DAVACL;

/**
 * This node represents a single notification.
 *
 * The signature is mostly identical to that of Sabre\DAV\IFile, but the get() method
 * MUST return an xml document that matches the requirements of the
 * 'caldav-notifications.txt' spec.
 *
 * @copyright Copyright (C) fruux GmbH (https://fruux.com/)
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
class Node extends DAV\File implements INode, DAVACL\IACL
{
    use DAVACL\ACLTrait;

    /**
     * The notification backend.
     *
     * @var CalDAV\Backend\NotificationSupport
     */
    protected $caldavBackend;

    /**
     * The actual notification.
     *
     * @var NotificationInterface
     */
    protected $notification;

    /**
     * Owner principal of the notification.
     *
     * @var string
     */
    protected $principalUri;

    /**
     * Constructor.
     *
     * @param string $principalUri
     */
    public function __construct(CalDAV\Backend\NotificationSupport $caldavBackend, $principalUri, NotificationInterface $notification)
    {
        $this->caldavBackend = $caldavBackend;
        $this->principalUri = $principalUri;
        $this->notification = $notification;
    }

    /**
     * Returns the path name for this notification.
     *
     * @return string
     */
    public function getName()
    {
        return $this->notification->getId().'.xml';
    }

    /**
     * Returns the etag for the notification.
     *
     * The etag must be surrounded by literal double-quotes.
     *
     * @return string
     */
    public function getETag()
    {
        return $this->notification->getETag();
    }

    /**
     * This method must return an xml element, using the
     * Sabre\CalDAV\Xml\Notification\NotificationInterface classes.
     *
     * @return NotificationInterface
     */
    public function getNotificationType()
    {
        return $this->notification;
    }

    /**
     * Deletes this notification.
     */
    public function delete()
    {
        $this->caldavBackend->deleteNotification($this->getOwner(), $this->notification);
    }

    /**
     * Returns the owner principal.
     *
     * This must be an url to a principal, or null if there's no owner
     *
     * @return string|null
     */
    public function getOwner()
    {
        return $this->principalUri;
    }
}
