<?php

/**
 * The Card object represents a single Card from an addressbook
 *
 * @package Sabre
 * @subpackage CardDAV
 * @copyright Copyright (C) 2007-2012 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/)
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
class Sabre_CardDAV_Card extends Sabre_DAV_File implements Sabre_CardDAV_ICard, Sabre_DAVACL_IACL {

    /**
     * CardDAV backend
     *
     * @var Sabre_CardDAV_Backend_Abstract
     */
    private $carddavBackend;

    /**
     * Array with information about this Card
     *
     * @var array
     */
    private $cardData;

    /**
     * Array with information about the containing addressbook
     *
     * @var array
     */
    private $addressBookInfo;

    /**
     * Constructor
     *
     * @param Sabre_CardDAV_Backend_Abstract $carddavBackend
     * @param array $addressBookInfo
     * @param array $cardData
     */
    public function __construct(Sabre_CardDAV_Backend_Abstract $carddavBackend,array $addressBookInfo,array $cardData) {

        $this->carddavBackend = $carddavBackend;
        $this->addressBookInfo = $addressBookInfo;
        $this->cardData = $cardData;

    }

    /**
     * Returns the uri for this object
     *
     * @return string
     */
    public function getName() {

        return $this->cardData['uri'];

    }

    /**
     * Returns the VCard-formatted object
     *
     * @return string
     */
    public function get() {

        // Pre-populating 'carddata' is optional. If we don't yet have it
        // already, we fetch it from the backend.
        if (!isset($this->cardData['carddata'])) {
            $this->cardData = $this->carddavBackend->getCard($this->addressBookInfo['id'], $this->cardData['uri']);
        }
        return $this->cardData['carddata'];

    }

    /**
     * Updates the VCard-formatted object
     *
     * @param string $cardData
     * @return string|null
     */
    public function put($cardData) {

        if (is_resource($cardData))
            $cardData = stream_get_contents($cardData);

        // Converting to UTF-8, if needed
        $cardData = Sabre_DAV_StringUtil::ensureUTF8($cardData);

        $etag = $this->carddavBackend->updateCard($this->addressBookInfo['id'],$this->cardData['uri'],$cardData);
        $this->cardData['carddata'] = $cardData;
        $this->cardData['etag'] = $etag;

        return $etag;

    }

    /**
     * Deletes the card
     *
     * @return void
     */
    public function delete() {

        $this->carddavBackend->deleteCard($this->addressBookInfo['id'],$this->cardData['uri']);

    }

    /**
     * Returns the mime content-type
     *
     * @return string
     */
    public function getContentType() {

        return 'text/x-vcard; charset=utf-8';

    }

    /**
     * Returns an ETag for this object
     *
     * @return string
     */
    public function getETag() {

        if (isset($this->cardData['etag'])) {
            return $this->cardData['etag'];
        } else {
            $data = $this->get();
            if (is_string($data)) {
                return '"' . md5($data) . '"';
            } else {
                // We refuse to calculate the md5 if it's a stream.
                return null;
            }
        }

    }

    /**
     * Returns the last modification date as a unix timestamp
     *
     * @return int
     */
    public function getLastModified() {

        return isset($this->cardData['lastmodified'])?$this->cardData['lastmodified']:null;

    }

    /**
     * Returns the size of this object in bytes
     *
     * @return int
     */
    public function getSize() {

        if (array_key_exists('size', $this->cardData)) {
            return $this->cardData['size'];
        } else {
            return strlen($this->get());
        }

    }

    /**
     * Returns the owner principal
     *
     * This must be a url to a principal, or null if there's no owner
     *
     * @return string|null
     */
    public function getOwner() {

        return $this->addressBookInfo['principaluri'];

    }

    /**
     * Returns a group principal
     *
     * This must be a url to a principal, or null if there's no owner
     *
     * @return string|null
     */
    public function getGroup() {

        return null;

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
    public function getACL() {

        return array(
            array(
                'privilege' => '{DAV:}read',
                'principal' => $this->addressBookInfo['principaluri'],
                'protected' => true,
            ),
            array(
                'privilege' => '{DAV:}write',
                'principal' => $this->addressBookInfo['principaluri'],
                'protected' => true,
            ),
        );

    }

    /**
     * Updates the ACL
     *
     * This method will receive a list of new ACE's.
     *
     * @param array $acl
     * @return void
     */
    public function setACL(array $acl) {

        throw new Sabre_DAV_Exception_MethodNotAllowed('Changing ACL is not yet supported');

    }

    /**
     * Returns the list of supported privileges for this node.
     *
     * The returned data structure is a list of nested privileges.
     * See Sabre_DAVACL_Plugin::getDefaultSupportedPrivilegeSet for a simple
     * standard structure.
     *
     * If null is returned from this method, the default privilege set is used,
     * which is fine for most common usecases.
     *
     * @return array|null
     */
    public function getSupportedPrivilegeSet() {

        return null;

    }

}

