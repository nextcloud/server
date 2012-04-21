<?php

/**
 * UserAddressBooks class
 *
 * The UserAddressBooks collection contains a list of addressbooks associated with a user
 *
 * @package Sabre
 * @subpackage CardDAV
 * @copyright Copyright (C) 2007-2012 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/) 
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
class Sabre_CardDAV_UserAddressBooks extends Sabre_DAV_Collection implements Sabre_DAV_IExtendedCollection, Sabre_DAVACL_IACL {

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
        throw new Sabre_DAV_Exception_NotFound('Addressbook with name \'' . $name . '\' could not be found');

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

    /**
     * Returns the owner principal
     *
     * This must be a url to a principal, or null if there's no owner
     *
     * @return string|null
     */
    public function getOwner() {

        return $this->principalUri;

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
                'principal' => $this->principalUri,
                'protected' => true,
            ),
            array(
                'privilege' => '{DAV:}write',
                'principal' => $this->principalUri,
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
