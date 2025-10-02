<?php

declare(strict_types=1);

namespace Sabre\DAVACL\FS;

use Sabre\DAVACL\AbstractPrincipalCollection;
use Sabre\DAVACL\ACLTrait;
use Sabre\DAVACL\IACL;
use Sabre\DAVACL\PrincipalBackend\BackendInterface;
use Sabre\Uri;

/**
 * This collection contains a collection for every principal.
 * It is similar to /home on many unix systems.
 *
 * The per-user collections can only be accessed by the user who owns the
 * collection.
 *
 * @copyright Copyright (C) fruux GmbH (https://fruux.com/)
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
class HomeCollection extends AbstractPrincipalCollection implements IACL
{
    use ACLTrait;

    /**
     * Name of this collection.
     *
     * @var string
     */
    public $collectionName = 'home';

    /**
     * Path to where the users' files are actually stored.
     *
     * @var string
     */
    protected $storagePath;

    /**
     * Creates the home collection.
     *
     * @param string $storagePath     where the actual files are stored
     * @param string $principalPrefix list of principals to iterate
     */
    public function __construct(BackendInterface $principalBackend, $storagePath, $principalPrefix = 'principals')
    {
        parent::__construct($principalBackend, $principalPrefix);
        $this->storagePath = $storagePath;
    }

    /**
     * Returns the name of the node.
     *
     * This is used to generate the url.
     *
     * @return string
     */
    public function getName()
    {
        return $this->collectionName;
    }

    /**
     * Returns a principals' collection of files.
     *
     * The passed array contains principal information, and is guaranteed to
     * at least contain a uri item. Other properties may or may not be
     * supplied by the authentication backend.
     *
     * @return \Sabre\DAV\INode
     */
    public function getChildForPrincipal(array $principalInfo)
    {
        $owner = $principalInfo['uri'];
        $acl = [
            [
                'privilege' => '{DAV:}all',
                'principal' => '{DAV:}owner',
                'protected' => true,
            ],
        ];

        list(, $principalBaseName) = Uri\split($owner);

        $path = $this->storagePath.'/'.$principalBaseName;

        if (!is_dir($path)) {
            mkdir($path, 0777, true);
        }

        return new Collection(
            $path,
            $acl,
            $owner
        );
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
                'principal' => '{DAV:}authenticated',
                'privilege' => '{DAV:}read',
                'protected' => true,
            ],
        ];
    }
}
