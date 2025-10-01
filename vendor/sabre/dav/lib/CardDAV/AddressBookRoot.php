<?php

declare(strict_types=1);

namespace Sabre\CardDAV;

use Sabre\DAVACL;

/**
 * AddressBook rootnode.
 *
 * This object lists a collection of users, which can contain addressbooks.
 *
 * @copyright Copyright (C) fruux GmbH (https://fruux.com/)
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
class AddressBookRoot extends DAVACL\AbstractPrincipalCollection
{
    /**
     * Principal Backend.
     *
     * @var DAVACL\PrincipalBackend\BackendInterface
     */
    protected $principalBackend;

    /**
     * CardDAV backend.
     *
     * @var Backend\BackendInterface
     */
    protected $carddavBackend;

    /**
     * Constructor.
     *
     * This constructor needs both a principal and a carddav backend.
     *
     * By default this class will show a list of addressbook collections for
     * principals in the 'principals' collection. If your main principals are
     * actually located in a different path, use the $principalPrefix argument
     * to override this.
     *
     * @param string $principalPrefix
     */
    public function __construct(DAVACL\PrincipalBackend\BackendInterface $principalBackend, Backend\BackendInterface $carddavBackend, $principalPrefix = 'principals')
    {
        $this->carddavBackend = $carddavBackend;
        parent::__construct($principalBackend, $principalPrefix);
    }

    /**
     * Returns the name of the node.
     *
     * @return string
     */
    public function getName()
    {
        return Plugin::ADDRESSBOOK_ROOT;
    }

    /**
     * This method returns a node for a principal.
     *
     * The passed array contains principal information, and is guaranteed to
     * at least contain a uri item. Other properties may or may not be
     * supplied by the authentication backend.
     *
     * @return \Sabre\DAV\INode
     */
    public function getChildForPrincipal(array $principal)
    {
        return new AddressBookHome($this->carddavBackend, $principal['uri']);
    }
}
