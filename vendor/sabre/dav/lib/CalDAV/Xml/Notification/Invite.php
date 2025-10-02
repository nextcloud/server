<?php

declare(strict_types=1);

namespace Sabre\CalDAV\Xml\Notification;

use Sabre\CalDAV;
use Sabre\CalDAV\SharingPlugin as SharingPlugin;
use Sabre\DAV;
use Sabre\Xml\Writer;

/**
 * This class represents the cs:invite-notification notification element.
 *
 * This element is defined here:
 * http://svn.calendarserver.org/repository/calendarserver/CalendarServer/trunk/doc/Extensions/caldav-sharing.txt
 *
 * @copyright Copyright (C) fruux GmbH (https://fruux.com/)
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
class Invite implements NotificationInterface
{
    /**
     * A unique id for the message.
     *
     * @var string
     */
    protected $id;

    /**
     * Timestamp of the notification.
     *
     * @var \DateTime
     */
    protected $dtStamp;

    /**
     * A url to the recipient of the notification. This can be an email
     * address (mailto:), or a principal url.
     *
     * @var string
     */
    protected $href;

    /**
     * The type of message, see the SharingPlugin::STATUS_* constants.
     *
     * @var int
     */
    protected $type;

    /**
     * True if access to a calendar is read-only.
     *
     * @var bool
     */
    protected $readOnly;

    /**
     * A url to the shared calendar.
     *
     * @var string
     */
    protected $hostUrl;

    /**
     * Url to the sharer of the calendar.
     *
     * @var string
     */
    protected $organizer;

    /**
     * The name of the sharer.
     *
     * @var string
     */
    protected $commonName;

    /**
     * The name of the sharer.
     *
     * @var string
     */
    protected $firstName;

    /**
     * The name of the sharer.
     *
     * @var string
     */
    protected $lastName;

    /**
     * A description of the share request.
     *
     * @var string
     */
    protected $summary;

    /**
     * The Etag for the notification.
     *
     * @var string
     */
    protected $etag;

    /**
     * The list of supported components.
     *
     * @var CalDAV\Xml\Property\SupportedCalendarComponentSet
     */
    protected $supportedComponents;

    /**
     * Creates the Invite notification.
     *
     * This constructor receives an array with the following elements:
     *
     *   * id           - A unique id
     *   * etag         - The etag
     *   * dtStamp      - A DateTime object with a timestamp for the notification.
     *   * type         - The type of notification, see SharingPlugin::STATUS_*
     *                    constants for details.
     *   * readOnly     - This must be set to true, if this is an invite for
     *                    read-only access to a calendar.
     *   * hostUrl      - A url to the shared calendar.
     *   * organizer    - Url to the sharer principal.
     *   * commonName   - The real name of the sharer (optional).
     *   * firstName    - The first name of the sharer (optional).
     *   * lastName     - The last name of the sharer (optional).
     *   * summary      - Description of the share, can be the same as the
     *                    calendar, but may also be modified (optional).
     *   * supportedComponents - An instance of
     *                    Sabre\CalDAV\Property\SupportedCalendarComponentSet.
     *                    This allows the client to determine which components
     *                    will be supported in the shared calendar. This is
     *                    also optional.
     *
     * @param array $values All the options
     */
    public function __construct(array $values)
    {
        $required = [
            'id',
            'etag',
            'href',
            'dtStamp',
            'type',
            'readOnly',
            'hostUrl',
            'organizer',
        ];
        foreach ($required as $item) {
            if (!isset($values[$item])) {
                throw new \InvalidArgumentException($item.' is a required constructor option');
            }
        }

        foreach ($values as $key => $value) {
            if (!property_exists($this, $key)) {
                throw new \InvalidArgumentException('Unknown option: '.$key);
            }
            $this->$key = $value;
        }
    }

    /**
     * The xmlSerialize method is called during xml writing.
     *
     * Use the $writer argument to write its own xml serialization.
     *
     * An important note: do _not_ create a parent element. Any element
     * implementing XmlSerializable should only ever write what's considered
     * its 'inner xml'.
     *
     * The parent of the current element is responsible for writing a
     * containing element.
     *
     * This allows serializers to be re-used for different element names.
     *
     * If you are opening new elements, you must also close them again.
     */
    public function xmlSerialize(Writer $writer)
    {
        $writer->writeElement('{'.CalDAV\Plugin::NS_CALENDARSERVER.'}invite-notification');
    }

    /**
     * This method serializes the entire notification, as it is used in the
     * response body.
     */
    public function xmlSerializeFull(Writer $writer)
    {
        $cs = '{'.CalDAV\Plugin::NS_CALENDARSERVER.'}';

        $this->dtStamp->setTimezone(new \DateTimeZone('GMT'));
        $writer->writeElement($cs.'dtstamp', $this->dtStamp->format('Ymd\\THis\\Z'));

        $writer->startElement($cs.'invite-notification');

        $writer->writeElement($cs.'uid', $this->id);
        $writer->writeElement('{DAV:}href', $this->href);

        switch ($this->type) {
            case DAV\Sharing\Plugin::INVITE_ACCEPTED:
                $writer->writeElement($cs.'invite-accepted');
                break;
            case DAV\Sharing\Plugin::INVITE_NORESPONSE:
                $writer->writeElement($cs.'invite-noresponse');
                break;
        }

        $writer->writeElement($cs.'hosturl', [
            '{DAV:}href' => $writer->contextUri.$this->hostUrl,
            ]);

        if ($this->summary) {
            $writer->writeElement($cs.'summary', $this->summary);
        }

        $writer->startElement($cs.'access');
        if ($this->readOnly) {
            $writer->writeElement($cs.'read');
        } else {
            $writer->writeElement($cs.'read-write');
        }
        $writer->endElement(); // access

        $writer->startElement($cs.'organizer');
        // If the organizer contains a 'mailto:' part, it means it should be
        // treated as absolute.
        if ('mailto:' === strtolower(substr($this->organizer, 0, 7))) {
            $writer->writeElement('{DAV:}href', $this->organizer);
        } else {
            $writer->writeElement('{DAV:}href', $writer->contextUri.$this->organizer);
        }
        if ($this->commonName) {
            $writer->writeElement($cs.'common-name', $this->commonName);
        }
        if ($this->firstName) {
            $writer->writeElement($cs.'first-name', $this->firstName);
        }
        if ($this->lastName) {
            $writer->writeElement($cs.'last-name', $this->lastName);
        }
        $writer->endElement(); // organizer

        if ($this->commonName) {
            $writer->writeElement($cs.'organizer-cn', $this->commonName);
        }
        if ($this->firstName) {
            $writer->writeElement($cs.'organizer-first', $this->firstName);
        }
        if ($this->lastName) {
            $writer->writeElement($cs.'organizer-last', $this->lastName);
        }
        if ($this->supportedComponents) {
            $writer->writeElement('{'.CalDAV\Plugin::NS_CALDAV.'}supported-calendar-component-set', $this->supportedComponents);
        }

        $writer->endElement(); // invite-notification
    }

    /**
     * Returns a unique id for this notification.
     *
     * This is just the base url. This should generally be some kind of unique
     * id.
     *
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Returns the ETag for this notification.
     *
     * The ETag must be surrounded by literal double-quotes.
     *
     * @return string
     */
    public function getETag()
    {
        return $this->etag;
    }
}
