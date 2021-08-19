<?php

declare(strict_types=1);

namespace Sabre\DAVACL;

/**
 * This trait is a default implementation of the IACL interface.
 *
 * In many cases you only want to implement 1 or to of the IACL functions,
 * this trait allows you to be a bit lazier.
 *
 * By default this trait grants all privileges to the owner of the resource.
 *
 * @copyright Copyright (C) fruux GmbH (https://fruux.com/)
 * @author Evert Pot (https://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
trait ACLTrait
{
    /**
     * Returns the owner principal.
     *
     * This must be a url to a principal, or null if there's no owner
     *
     * @return string|null
     */
    public function getOwner()
    {
        return null;
    }

    /**
     * Returns a group principal.
     *
     * This must be a url to a principal, or null if there's no owner
     *
     * @return string|null
     */
    public function getGroup()
    {
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
    public function getACL()
    {
        return [
            [
                'privilege' => '{DAV:}all',
                'principal' => '{DAV:}owner',
                'protected' => true,
            ],
        ];
    }

    /**
     * Updates the ACL.
     *
     * This method will receive a list of new ACE's as an array argument.
     */
    public function setACL(array $acl)
    {
        throw new \Sabre\DAV\Exception\Forbidden('Setting ACL is not supported on this node');
    }

    /**
     * Returns the list of supported privileges for this node.
     *
     * The returned data structure is a list of nested privileges.
     * See Sabre\DAVACL\Plugin::getDefaultSupportedPrivilegeSet for a simple
     * standard structure.
     *
     * If null is returned from this method, the default privilege set is used,
     * which is fine for most common usecases.
     *
     * @return array|null
     */
    public function getSupportedPrivilegeSet()
    {
        return null;
    }
}
