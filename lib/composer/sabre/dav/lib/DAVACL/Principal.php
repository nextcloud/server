<?php

declare(strict_types=1);

namespace Sabre\DAVACL;

use Sabre\DAV;
use Sabre\Uri;

/**
 * Principal class.
 *
 * This class is a representation of a simple principal
 *
 * Many WebDAV specs require a user to show up in the directory
 * structure.
 *
 * This principal also has basic ACL settings, only allowing the principal
 * access it's own principal.
 *
 * @copyright Copyright (C) fruux GmbH (https://fruux.com/)
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
class Principal extends DAV\Node implements IPrincipal, DAV\IProperties, IACL
{
    use ACLTrait;

    /**
     * Struct with principal information.
     *
     * @var array
     */
    protected $principalProperties;

    /**
     * Principal backend.
     *
     * @var PrincipalBackend\BackendInterface
     */
    protected $principalBackend;

    /**
     * Creates the principal object.
     */
    public function __construct(PrincipalBackend\BackendInterface $principalBackend, array $principalProperties = [])
    {
        if (!isset($principalProperties['uri'])) {
            throw new DAV\Exception('The principal properties must at least contain the \'uri\' key');
        }
        $this->principalBackend = $principalBackend;
        $this->principalProperties = $principalProperties;
    }

    /**
     * Returns the full principal url.
     *
     * @return string
     */
    public function getPrincipalUrl()
    {
        return $this->principalProperties['uri'];
    }

    /**
     * Returns a list of alternative urls for a principal.
     *
     * This can for example be an email address, or ldap url.
     *
     * @return array
     */
    public function getAlternateUriSet()
    {
        $uris = [];
        if (isset($this->principalProperties['{DAV:}alternate-URI-set'])) {
            $uris = $this->principalProperties['{DAV:}alternate-URI-set'];
        }

        if (isset($this->principalProperties['{http://sabredav.org/ns}email-address'])) {
            $uris[] = 'mailto:'.$this->principalProperties['{http://sabredav.org/ns}email-address'];
        }

        return array_unique($uris);
    }

    /**
     * Returns the list of group members.
     *
     * If this principal is a group, this function should return
     * all member principal uri's for the group.
     *
     * @return array
     */
    public function getGroupMemberSet()
    {
        return $this->principalBackend->getGroupMemberSet($this->principalProperties['uri']);
    }

    /**
     * Returns the list of groups this principal is member of.
     *
     * If this principal is a member of a (list of) groups, this function
     * should return a list of principal uri's for it's members.
     *
     * @return array
     */
    public function getGroupMembership()
    {
        return $this->principalBackend->getGroupMemberShip($this->principalProperties['uri']);
    }

    /**
     * Sets a list of group members.
     *
     * If this principal is a group, this method sets all the group members.
     * The list of members is always overwritten, never appended to.
     *
     * This method should throw an exception if the members could not be set.
     */
    public function setGroupMemberSet(array $groupMembers)
    {
        $this->principalBackend->setGroupMemberSet($this->principalProperties['uri'], $groupMembers);
    }

    /**
     * Returns this principals name.
     *
     * @return string
     */
    public function getName()
    {
        $uri = $this->principalProperties['uri'];
        list(, $name) = Uri\split($uri);

        return $name;
    }

    /**
     * Returns the name of the user.
     *
     * @return string
     */
    public function getDisplayName()
    {
        if (isset($this->principalProperties['{DAV:}displayname'])) {
            return $this->principalProperties['{DAV:}displayname'];
        } else {
            return $this->getName();
        }
    }

    /**
     * Returns a list of properties.
     *
     * @param array $requestedProperties
     *
     * @return array
     */
    public function getProperties($requestedProperties)
    {
        $newProperties = [];
        foreach ($requestedProperties as $propName) {
            if (isset($this->principalProperties[$propName])) {
                $newProperties[$propName] = $this->principalProperties[$propName];
            }
        }

        return $newProperties;
    }

    /**
     * Updates properties on this node.
     *
     * This method received a PropPatch object, which contains all the
     * information about the update.
     *
     * To update specific properties, call the 'handle' method on this object.
     * Read the PropPatch documentation for more information.
     */
    public function propPatch(DAV\PropPatch $propPatch)
    {
        return $this->principalBackend->updatePrincipal(
            $this->principalProperties['uri'],
            $propPatch
        );
    }

    /**
     * Returns the owner principal.
     *
     * This must be a url to a principal, or null if there's no owner
     *
     * @return string|null
     */
    public function getOwner()
    {
        return $this->principalProperties['uri'];
    }
}
