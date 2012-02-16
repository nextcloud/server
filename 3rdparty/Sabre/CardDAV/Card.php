<?php

/**
 * The Card object represents a single Card from an addressbook
 * 
 * @package Sabre
 * @subpackage CardDAV
 * @copyright Copyright (C) 2007-2011 Rooftop Solutions. All rights reserved.
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

        $cardData = $this->cardData['carddata'];
        $s = fopen('php://temp','r+');
        fwrite($s, $cardData);
        rewind($s);
        return $s;

    }

    /**
     * Updates the VCard-formatted object 
     * 
     * @param string $cardData 
     * @return void 
     */
    public function put($cardData) {

        if (is_resource($cardData))
            $cardData = stream_get_contents($cardData);

        // Converting to UTF-8, if needed
        $cardData = Sabre_DAV_StringUtil::ensureUTF8($cardData);

        $this->carddavBackend->updateCard($this->addressBookInfo['id'],$this->cardData['uri'],$cardData);
        $this->cardData['carddata'] = $cardData;

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

        return 'text/x-vcard';

    }

    /**
     * Returns an ETag for this object 
     * 
     * @return string 
     */
    public function getETag() {

        return '"' . md5($this->cardData['carddata']) . '"';

    }

    /**
     * Returns the last modification date as a unix timestamp
     * 
     * @return time 
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

        return strlen($this->cardData['carddata']);

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

}

