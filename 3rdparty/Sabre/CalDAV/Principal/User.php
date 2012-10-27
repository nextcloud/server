<?php

/**
 * CalDAV principal
 *
 * This is a standard user-principal for CalDAV. This principal is also a
 * collection and returns the caldav-proxy-read and caldav-proxy-write child
 * principals.
 *
 * @package Sabre
 * @subpackage CalDAV
 * @copyright Copyright (C) 2007-2012 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/) 
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
class Sabre_CalDAV_Principal_User extends Sabre_DAVACL_Principal implements Sabre_DAV_ICollection {

    /**
     * Creates a new file in the directory
     *
     * @param string $name Name of the file
     * @param resource $data Initial payload, passed as a readable stream resource.
     * @throws Sabre_DAV_Exception_Forbidden
     * @return void
     */
    public function createFile($name, $data = null) {

        throw new Sabre_DAV_Exception_Forbidden('Permission denied to create file (filename ' . $name . ')');

    }

    /**
     * Creates a new subdirectory
     *
     * @param string $name
     * @throws Sabre_DAV_Exception_Forbidden
     * @return void
     */
    public function createDirectory($name) {

        throw new Sabre_DAV_Exception_Forbidden('Permission denied to create directory');

    }

    /**
     * Returns a specific child node, referenced by its name
     *
     * @param string $name
     * @return Sabre_DAV_INode
     */
    public function getChild($name) {

        $principal = $this->principalBackend->getPrincipalByPath($this->getPrincipalURL() . '/' . $name);
        if (!$principal) {
            throw new Sabre_DAV_Exception_NotFound('Node with name ' . $name . ' was not found');
        }
        if ($name === 'calendar-proxy-read')
            return new Sabre_CalDAV_Principal_ProxyRead($this->principalBackend, $this->principalProperties);

        if ($name === 'calendar-proxy-write')
            return new Sabre_CalDAV_Principal_ProxyWrite($this->principalBackend, $this->principalProperties);

        throw new Sabre_DAV_Exception_NotFound('Node with name ' . $name . ' was not found');

    }

    /**
     * Returns an array with all the child nodes
     *
     * @return Sabre_DAV_INode[]
     */
    public function getChildren() {

        $r = array();
        if ($this->principalBackend->getPrincipalByPath($this->getPrincipalURL() . '/calendar-proxy-read')) {
            $r[] = new Sabre_CalDAV_Principal_ProxyRead($this->principalBackend, $this->principalProperties);
        }
        if ($this->principalBackend->getPrincipalByPath($this->getPrincipalURL() . '/calendar-proxy-write')) {
            $r[] = new Sabre_CalDAV_Principal_ProxyWrite($this->principalBackend, $this->principalProperties);
        }

        return $r;

    }

    /**
     * Returns whether or not the child node exists
     *
     * @param string $name
     * @return bool
     */
    public function childExists($name) {

        try {
            $this->getChild($name);
            return true;
        } catch (Sabre_DAV_Exception_NotFound $e) {
            return false;
        }

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

        $acl = parent::getACL();
        $acl[] = array(
            'privilege' => '{DAV:}read',
            'principal' => $this->principalProperties['uri'] . '/calendar-proxy-read',
            'protected' => true,
        );
        $acl[] = array(
            'privilege' => '{DAV:}read',
            'principal' => $this->principalProperties['uri'] . '/calendar-proxy-write',
            'protected' => true,
        );
        return $acl;

    }

}
