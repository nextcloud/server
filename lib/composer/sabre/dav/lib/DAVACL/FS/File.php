<?php

declare(strict_types=1);

namespace Sabre\DAVACL\FS;

use Sabre\DAV\FSExt\File as BaseFile;
use Sabre\DAVACL\ACLTrait;
use Sabre\DAVACL\IACL;

/**
 * This is an ACL-enabled file node.
 *
 * @copyright Copyright (C) fruux GmbH (https://fruux.com/)
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
class File extends BaseFile implements IACL
{
    use ACLTrait;

    /**
     * A list of ACL rules.
     *
     * @var array
     */
    protected $acl;

    /**
     * Owner uri, or null for no owner.
     *
     * @var string|null
     */
    protected $owner;

    /**
     * Constructor.
     *
     * @param string      $path  on-disk path
     * @param array       $acl   ACL rules
     * @param string|null $owner principal owner string
     */
    public function __construct($path, array $acl, $owner = null)
    {
        parent::__construct($path);
        $this->acl = $acl;
        $this->owner = $owner;
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
        return $this->owner;
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
        return $this->acl;
    }
}
