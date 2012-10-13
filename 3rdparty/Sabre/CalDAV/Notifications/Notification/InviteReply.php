<?php

use Sabre_CalDAV_SharingPlugin as SharingPlugin;

/**
 * This class represents the cs:invite-reply notification element.
 *
 * @package Sabre
 * @subpackage CalDAV
 * @copyright Copyright (C) 2007-2012 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/)
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
class Sabre_CalDAV_Notifications_Notification_InviteReply extends Sabre_DAV_Property implements Sabre_CalDAV_Notifications_INotificationType {

    /**
     * A unique id for the message
     *
     * @var string
     */
    protected $id;

    /**
     * Timestamp of the notification
     *
     * @var DateTime
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
     * A description of the share request
     *
     * @var string
     */
    protected $summary;

    /**
     * Notification Etag
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
    public function __construct(array $values) {

        $required = array(
            'id',
            'etag',
            'href',
            'dtStamp',
            'inReplyTo',
            'type',
            'hostUrl',
        );
        foreach($required as $item) {
            if (!isset($values[$item])) {
                throw new InvalidArgumentException($item . ' is a required constructor option');
            }
        }

        foreach($values as $key=>$value) {
            if (!property_exists($this, $key)) {
                throw new InvalidArgumentException('Unknown option: ' . $key);
            }
            $this->$key = $value;
        }

    }

    /**
     * Serializes the notification as a single property.
     *
     * You should usually just encode the single top-level element of the
     * notification.
     *
     * @param Sabre_DAV_Server $server
     * @param DOMElement $node
     * @return void
     */
    public function serialize(Sabre_DAV_Server $server, \DOMElement $node) {

        $prop = $node->ownerDocument->createElement('cs:invite-reply');
        $node->appendChild($prop);

    }

    /**
     * This method serializes the entire notification, as it is used in the
     * response body.
     *
     * @param Sabre_DAV_Server $server
     * @param DOMElement $node
     * @return void
     */
    public function serializeBody(Sabre_DAV_Server $server, \DOMElement $node) {

        $doc = $node->ownerDocument;

        $dt = $doc->createElement('cs:dtstamp');
        $this->dtStamp->setTimezone(new \DateTimezone('GMT'));
        $dt->appendChild($doc->createTextNode($this->dtStamp->format('Ymd\\THis\\Z')));
        $node->appendChild($dt);

        $prop = $doc->createElement('cs:invite-reply');
        $node->appendChild($prop);

        $uid = $doc->createElement('cs:uid');
        $uid->appendChild($doc->createTextNode($this->id));
        $prop->appendChild($uid);

        $inReplyTo = $doc->createElement('cs:in-reply-to');
        $inReplyTo->appendChild( $doc->createTextNode($this->inReplyTo) );
        $prop->appendChild($inReplyTo);

        $href = $doc->createElement('d:href');
        $href->appendChild( $doc->createTextNode($this->href) );
        $prop->appendChild($href);

        $nodeName = null;
        switch($this->type) {

            case SharingPlugin::STATUS_ACCEPTED :
                $nodeName = 'cs:invite-accepted';
                break;
            case SharingPlugin::STATUS_DECLINED :
                $nodeName = 'cs:invite-declined';
                break;

        }
        $prop->appendChild(
            $doc->createElement($nodeName)
        );
        $hostHref = $doc->createElement('d:href', $server->getBaseUri() . $this->hostUrl);
        $hostUrl  = $doc->createElement('cs:hosturl');
        $hostUrl->appendChild($hostHref);
        $prop->appendChild($hostUrl);

        if ($this->summary) {
            $summary = $doc->createElement('cs:summary');
            $summary->appendChild($doc->createTextNode($this->summary));
            $prop->appendChild($summary);
        }

    }

    /**
     * Returns a unique id for this notification
     *
     * This is just the base url. This should generally be some kind of unique
     * id.
     *
     * @return string
     */
    public function getId() {

        return $this->id;

    }

    /**
     * Returns the ETag for this notification.
     *
     * The ETag must be surrounded by literal double-quotes.
     *
     * @return string
     */
    public function getETag() {

        return $this->etag;

    }
}
