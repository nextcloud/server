<?php

declare(strict_types=1);

namespace Sabre\CardDAV;

/**
 * IDirectory interface.
 *
 * Implement this interface to have an addressbook marked as a 'directory'. A
 * directory is an (often) global addressbook.
 *
 * A full description can be found in the IETF draft:
 *   - draft-daboo-carddav-directory-gateway
 *
 * @copyright Copyright (C) fruux GmbH (https://fruux.com/)
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
interface IDirectory extends IAddressBook
{
}
