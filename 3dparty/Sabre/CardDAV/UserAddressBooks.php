<?php

/**
 * UserAddressBooks class
 * 
 * @package Sabre
 * @subpackage CardDAV
 * @copyright Copyright (C) 2007-2011 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/) 
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */

/**
 * The UserAddressBooks collection contains a list of addressbooks associated with a user
 */
class Sabre_CardDAV_UserAddressBooks extends Sabre_DAV_Directory implements Sabre_DAV_IExtendedCollection {

    /**
     * Principal uri
     * 
     * @var array 
     */
    protected $principalUri;

    /**
     * carddavBackend 
     * 
     * @var Sabre_CardDAV_Backend_Abstract 
     */
    protected $carddavBackend;

    /**
     * Constructor 
     * 
     * @param Sabre_CardDAV_Backend_Abstract $carddavBackend 
     * @param string $principalUri 
     */
    public function __construct(Sabre_CardDAV_Backend_Abstract $carddavBackend, $principalUri) {

        $this->carddavBackend = $carddavBackend;
        $this->principalUri = $principalUri;
       
    }

    /**
     * Returns the name of this object 
     * 
     * @return string
     */
    public function getName() {
      
        list(,$name) = Sabre_DAV_URLUtil::splitPath($this->principalUri);
        return $name; 

    }

    /**
     * Updates the name of this object 
     * 
     * @param string $name 
     * @return void
     */
    public function setName($name) {

        throw new Sabre_DAV_Exception_MethodNotAllowed();

    }

    /**
     * Deletes this object 
     * 
     * @return void
     */
    public function delete() {

        throw new Sabre_DAV_Exception_MethodNotAllowed();

    }

    /**
     * Returns the last modification date 
     * 
     * @return int 
     */
    public function getLastModified() {

        return null; 

    }

    /**
     * Creates a new file under this object.
     *
     * This is currently not allowed
     * 
     * @param string $filename 
     * @param resource $data 
     * @return void
     */
    public function createFile($filename, $data=null) {

        throw new Sabre_DAV_Exception_MethodNotAllowed('Creating new files in this collection is not supported');

    }

    /**
     * Creates a new directory under this object.
     *
     * This is currently not allowed.
     * 
     * @param string $filename 
     * @return void
     */
    public function createDirectory($filename) {

        throw new Sabre_DAV_Exception_MethodNotAllowed('Creating new collections in this collection is not supported');

    }

    /**
     * Returns a single calendar, by name 
     * 
     * @param string $name
     * @todo needs optimizing
     * @return Sabre_CardDAV_AddressBook
     */
    public function getChild($name) {

        foreach($this->getChildren() as $child) {
            if ($name==$child->getName())
                return $child;

        }
        throw new Sabre_DAV_Exception_FileNotFound('Addressbook with name \'' . $name . '\' could not be found');

    }

    /**
     * Returns a list of addressbooks 
     * 
     * @return array 
     */
    public function getChildren() {

        $addressbooks = $this->carddavBackend->getAddressbooksForUser($this->principalUri);
        $objs = array();
        foreach($addressbooks as $addressbook) {
            $objs[] = new Sabre_CardDAV_AddressBook($this->carddavBackend, $addressbook);
        }
        return $objs;

    }

    /**
     * Creates a new addressbook 
     * 
     * @param string $name
     * @param array $resourceType 
     * @param array $properties 
     * @return void
     */
    public function createExtendedCollection($name, array $resourceType, array $properties) {

        if (!in_array('{'.Sabre_CardDAV_Plugin::NS_CARDDAV.'}addressbook',$resourceType) || count($resourceType)!==2) {
            throw new Sabre_DAV_Exception_InvalidResourceType('Unknown resourceType for this collection');
        }
        $this->carddavBackend->createAddressBook($this->principalUri, $name, $properties);

    }

}
