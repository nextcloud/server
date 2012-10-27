<?php

/**
 * SystemStatus notification
 *
 * This notification can be used to indicate to the user that the system is
 * down.
 *
 * @package Sabre
 * @subpackage CalDAV
 * @copyright Copyright (C) 2007-2012 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/)
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
class Sabre_CalDAV_Notifications_Notification_SystemStatus extends Sabre_DAV_Property implements Sabre_CalDAV_Notifications_INotificationType {

    const TYPE_LOW = 1;
    const TYPE_MEDIUM = 2;
    const TYPE_HIGH = 3;

    /**
     * A unique id
     *
     * @var string
     */
    protected $id;

    /**
     * The type of alert. This should be one of the TYPE_ constants.
     *
     * @var int
     */
    protected $type;

    /**
     * A human-readable description of the problem.
     *
     * @var string
     */
    protected $description;

    /**
     * A url to a website with more information for the user.
     *
     * @var string
     */
    protected $href;

    /**
     * Notification Etag
     *
     * @var string
     */
    protected $etag;

    /**
     * Creates the notification.
     *
     * Some kind of unique id should be provided. This is used to generate a
     * url.
     *
     * @param string $id
     * @param string $etag
     * @param int $type
     * @param string $description
     * @param string $href
     */
    public function __construct($id, $etag, $type = self::TYPE_HIGH, $description = null, $href = null) {

        $this->id = $id;
        $this->type = $type;
        $this->description = $description;
        $this->href = $href;
        $this->etag = $etag;

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

        switch($this->type) {
            case self::TYPE_LOW :
                $type = 'low';
                break;
            case self::TYPE_MEDIUM :
                $type = 'medium';
                break;
            default :
            case self::TYPE_HIGH :
                $type = 'high';
                break;
        }

        $prop = $node->ownerDocument->createElement('cs:systemstatus');
        $prop->setAttribute('type', $type);

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

        switch($this->type) {
            case self::TYPE_LOW :
                $type = 'low';
                break;
            case self::TYPE_MEDIUM :
                $type = 'medium';
                break;
            default :
            case self::TYPE_HIGH :
                $type = 'high';
                break;
        }

        $prop = $node->ownerDocument->createElement('cs:systemstatus');
        $prop->setAttribute('type', $type);

        if ($this->description) {
            $text = $node->ownerDocument->createTextNode($this->description);
            $desc = $node->ownerDocument->createElement('cs:description');
            $desc->appendChild($text);
            $prop->appendChild($desc);
        }
        if ($this->href) {
            $text = $node->ownerDocument->createTextNode($this->href);
            $href = $node->ownerDocument->createElement('d:href');
            $href->appendChild($text);
            $prop->appendChild($href);
        }

        $node->appendChild($prop);

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

    /*
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
