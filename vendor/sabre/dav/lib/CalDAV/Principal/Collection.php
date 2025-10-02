<?php

declare(strict_types=1);

namespace Sabre\CalDAV\Principal;

use Sabre\DAVACL;

/**
 * Principal collection.
 *
 * This is an alternative collection to the standard ACL principal collection.
 * This collection adds support for the calendar-proxy-read and
 * calendar-proxy-write sub-principals, as defined by the caldav-proxy
 * specification.
 *
 * @copyright Copyright (C) fruux GmbH (https://fruux.com/)
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
class Collection extends DAVACL\PrincipalCollection
{
    /**
     * Returns a child object based on principal information.
     *
     * @return User
     */
    public function getChildForPrincipal(array $principalInfo)
    {
        return new User($this->principalBackend, $principalInfo);
    }
}
