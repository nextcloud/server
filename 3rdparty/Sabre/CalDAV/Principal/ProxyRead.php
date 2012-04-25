<?php

/**
 * ProxyRead principal
 *
 * This class represents a principal group, hosted under the main principal.
 * This is needed to implement 'Calendar delegation' support. This class is
 * instantiated by Sabre_CalDAV_Principal_User.
 *
 * @package Sabre
 * @subpackage CalDAV
 * @copyright Copyright (C) 2007-2012 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/) 
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
class Sabre_CalDAV_Principal_ProxyRead implements Sabre_DAVACL_IPrincipal {

    /**
     * Principal information from the parent principal.
     *
     * @var array
     */
    protected $principalInfo;

    /**
     * Principal backend
     *
     * @var Sabre_DAVACL_IPrincipalBackend
     */
    protected $principalBackend;

    /**
     * Creates the object.
     *
     * Note that you MUST supply the parent principal information.
     *
     * @param Sabre_DAVACL_IPrincipalBackend $principalBackend
     * @param array $principalInfo
     */
    public function __construct(Sabre_DAVACL_IPrincipalBackend $principalBackend, array $principalInfo) {

        $this->principalInfo = $principalInfo;
        $this->principalBackend = $principalBackend;

    }

    /**
     * Returns this principals name.
     *
     * @return string
     */
    public function getName() {

        return 'calendar-proxy-read';

    }

    /**
     * Returns the last modification time
     *
     * @return null
     */
    public function getLastModified() {

        return null;

    }

    /**
     * Deletes the current node
     *
     * @throws Sabre_DAV_Exception_Forbidden
     * @return void
     */
    public function delete() {

        throw new Sabre_DAV_Exception_Forbidden('Permission denied to delete node');

    }

    /**
     * Renames the node
     *
     * @throws Sabre_DAV_Exception_Forbidden
     * @param string $name The new name
     * @return void
     */
    public function setName($name) {

        throw new Sabre_DAV_Exception_Forbidden('Permission denied to rename file');

    }


    /**
     * Returns a list of alternative urls for a principal
     *
     * This can for example be an email address, or ldap url.
     *
     * @return array
     */
    public function getAlternateUriSet() {

        return array();

    }

    /**
     * Returns the full principal url
     *
     * @return string
     */
    public function getPrincipalUrl() {

        return $this->principalInfo['uri'] . '/' . $this->getName();

    }

    /**
     * Returns the list of group members
     *
     * If this principal is a group, this function should return
     * all member principal uri's for the group.
     *
     * @return array
     */
    public function getGroupMemberSet() {

        return $this->principalBackend->getGroupMemberSet($this->getPrincipalUrl());

    }

    /**
     * Returns the list of groups this principal is member of
     *
     * If this principal is a member of a (list of) groups, this function
     * should return a list of principal uri's for it's members.
     *
     * @return array
     */
    public function getGroupMembership() {

        return $this->principalBackend->getGroupMembership($this->getPrincipalUrl());

    }

    /**
     * Sets a list of group members
     *
     * If this principal is a group, this method sets all the group members.
     * The list of members is always overwritten, never appended to.
     *
     * This method should throw an exception if the members could not be set.
     *
     * @param array $principals
     * @return void
     */
    public function setGroupMemberSet(array $principals) {

        $this->principalBackend->setGroupMemberSet($this->getPrincipalUrl(), $principals);

    }

    /**
     * Returns the displayname
     *
     * This should be a human readable name for the principal.
     * If none is available, return the nodename.
     *
     * @return string
     */
    public function getDisplayName() {

        return $this->getName();

    }

}
