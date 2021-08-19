<?php

declare(strict_types=1);

namespace Sabre\CalDAV;

use Sabre\DAVACL\PrincipalBackend;

/**
 * Calendars collection.
 *
 * This object is responsible for generating a list of calendar-homes for each
 * user.
 *
 * This is the top-most node for the calendars tree. In most servers this class
 * represents the "/calendars" path.
 *
 * @copyright Copyright (C) fruux GmbH (https://fruux.com/)
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
class CalendarRoot extends \Sabre\DAVACL\AbstractPrincipalCollection
{
    /**
     * CalDAV backend.
     *
     * @var Backend\BackendInterface
     */
    protected $caldavBackend;

    /**
     * Constructor.
     *
     * This constructor needs both an authentication and a caldav backend.
     *
     * By default this class will show a list of calendar collections for
     * principals in the 'principals' collection. If your main principals are
     * actually located in a different path, use the $principalPrefix argument
     * to override this.
     *
     * @param string $principalPrefix
     */
    public function __construct(PrincipalBackend\BackendInterface $principalBackend, Backend\BackendInterface $caldavBackend, $principalPrefix = 'principals')
    {
        parent::__construct($principalBackend, $principalPrefix);
        $this->caldavBackend = $caldavBackend;
    }

    /**
     * Returns the nodename.
     *
     * We're overriding this, because the default will be the 'principalPrefix',
     * and we want it to be Sabre\CalDAV\Plugin::CALENDAR_ROOT
     *
     * @return string
     */
    public function getName()
    {
        return Plugin::CALENDAR_ROOT;
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
        return new CalendarHome($this->caldavBackend, $principal);
    }
}
