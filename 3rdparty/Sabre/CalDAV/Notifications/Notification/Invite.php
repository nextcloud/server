<?php

use Sabre_CalDAV_SharingPlugin as SharingPlugin;

/**
 * This class represents the cs:invite-notification notification element.
 *
 * @package Sabre
 * @subpackage CalDAV
 * @copyright Copyright (C) 2007-2012 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/)
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
class Sabre_CalDAV_Notifications_Notification_Invite extends Sabre_DAV_Property implements Sabre_CalDAV_Notifications_INotificationType {

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
     * Url to the sharer of the calendar
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
     * A description of the share request
     *
     * @var string
     */
    protected $summary;

    /**
     * The Etag for the notification
     *
     * @var string
     */
    protected $etag;

    /**
     * The list of supported components
     *
     * @var Sabre_CalDAV_Property_SupportedCalendarComponentSet
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
     *   * summary      - Description of the share, can be the same as the
     *                    calendar, but may also be modified (optional).
     *   * supportedComponents - An instance of
     *                    Sabre_CalDAV_Property_SupportedCalendarComponentSet.
     *                    This allows the client to determine which components
     *                    will be supported in the shared calendar. This is
     *                    also optional.
     *
     * @param array $values All the options
     */
    public function __construct(array $values) {

        $required = array(
            'id',
            'etag',
            'href',
            'dtStamp',
            'type',
            'readOnly',
            'hostUrl',
            'organizer',
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

        $prop = $node->ownerDocument->createElement('cs:invite-notification');
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

        $prop = $doc->createElement('cs:invite-notification');
        $node->appendChild($prop);

        $uid = $doc->createElement('cs:uid');
        $uid->appendChild( $doc->createTextNode($this->id) );
        $prop->appendChild($uid);

        $href = $doc->createElement('d:href');
        $href->appendChild( $doc->createTextNode( $this->href ) );
        $prop->appendChild($href);

        $nodeName = null;
        switch($this->type) {

            case SharingPlugin::STATUS_ACCEPTED :
                $nodeName = 'cs:invite-accepted';
                break;
            case SharingPlugin::STATUS_DECLINED :
                $nodeName = 'cs:invite-declined';
                break;
            case SharingPlugin::STATUS_DELETED :
                $nodeName = 'cs:invite-deleted';
                break;
            case SharingPlugin::STATUS_NORESPONSE :
                $nodeName = 'cs:invite-noresponse';
                break;

        }
        $prop->appendChild(
            $doc->createElement($nodeName)
        );
        $hostHref = $doc->createElement('d:href', $server->getBaseUri() . $this->hostUrl);
        $hostUrl  = $doc->createElement('cs:hosturl');
        $hostUrl->appendChild($hostHref);
        $prop->appendChild($hostUrl);

        $access = $doc->createElement('cs:access');
        if ($this->readOnly) {
            $access->appendChild($doc->createElement('cs:read'));
        } else {
            $access->appendChild($doc->createElement('cs:read-write'));
        }
        $prop->appendChild($access);

        $organizerHref = $doc->createElement('d:href', $server->getBaseUri() . $this->organizer);
        $organizerUrl  = $doc->createElement('cs:organizer');
        if ($this->commonName) {
            $commonName = $doc->createElement('cs:common-name');
            $commonName->appendChild($doc->createTextNode($this->commonName));
            $organizerUrl->appendChild($commonName);
        }
        $organizerUrl->appendChild($organizerHref);
        $prop->appendChild($organizerUrl);

        if ($this->summary) {
            $summary = $doc->createElement('cs:summary');
            $summary->appendChild($doc->createTextNode($this->summary));
            $prop->appendChild($summary);
        }
        if ($this->supportedComponents) {

            $xcomp = $doc->createElement('cal:supported-calendar-component-set');
            $this->supportedComponents->serialize($server, $xcomp);
            $prop->appendChild($xcomp);

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
