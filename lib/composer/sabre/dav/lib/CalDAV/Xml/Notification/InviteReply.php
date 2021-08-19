<?php

declare(strict_types=1);

namespace Sabre\CalDAV\Xml\Notification;

use Sabre\CalDAV;
use Sabre\CalDAV\SharingPlugin;
use Sabre\DAV;
use Sabre\Xml\Writer;

/**
 * This class represents the cs:invite-reply notification element.
 *
 * @copyright Copyright (C) fruux GmbH (https://fruux.com/)
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
class InviteReply implements NotificationInterface
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
     * The unique id of the notification this was a reply to.
     *
     * @var string
     */
    protected $inReplyTo;

    /**
     * A url to the recipient of the original (!) notification.
     *
     * @var string
     */
    protected $href;

    /**
     * The type of message, see the SharingPlugin::STATUS_ constants.
     *
     * @var int
     */
    protected $type;

    /**
     * A url to the shared calendar.
     *
     * @var string
     */
    protected $hostUrl;

    /**
     * A description of the share request.
     *
     * @var string
     */
    protected $summary;

    /**
     * Notification Etag.
     *
     * @var string
     */
    protected $etag;

    /**
     * Creates the Invite Reply Notification.
     *
     * This constructor receives an array with the following elements:
     *
     *   * id           - A unique id
     *   * etag         - The etag
     *   * dtStamp      - A DateTime object with a timestamp for the notification.
     *   * inReplyTo    - This should refer to the 'id' of the notification
     *                    this is a reply to.
     *   * type         - The type of notification, see SharingPlugin::STATUS_*
     *                    constants for details.
     *   * hostUrl      - A url to the shared calendar.
     *   * summary      - Description of the share, can be the same as the
     *                    calendar, but may also be modified (optional).
     */
    public function __construct(array $values)
    {
        $required = [
            'id',
            'etag',
            'href',
            'dtStamp',
            'inReplyTo',
            'type',
            'hostUrl',
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
        $writer->writeElement('{'.CalDAV\Plugin::NS_CALENDARSERVER.'}invite-reply');
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

        $writer->startElement($cs.'invite-reply');

        $writer->writeElement($cs.'uid', $this->id);
        $writer->writeElement($cs.'in-reply-to', $this->inReplyTo);
        $writer->writeElement('{DAV:}href', $this->href);

        switch ($this->type) {
            case DAV\Sharing\Plugin::INVITE_ACCEPTED:
                $writer->writeElement($cs.'invite-accepted');
                break;
            case DAV\Sharing\Plugin::INVITE_DECLINED:
                $writer->writeElement($cs.'invite-declined');
                break;
        }

        $writer->writeElement($cs.'hosturl', [
            '{DAV:}href' => $writer->contextUri.$this->hostUrl,
            ]);

        if ($this->summary) {
            $writer->writeElement($cs.'summary', $this->summary);
        }
        $writer->endElement(); // invite-reply
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
