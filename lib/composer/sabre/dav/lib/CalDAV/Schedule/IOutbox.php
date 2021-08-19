<?php

declare(strict_types=1);

namespace Sabre\CalDAV\Schedule;

/**
 * Implement this interface to have a node be recognized as a CalDAV scheduling
 * outbox.
 *
 * @copyright Copyright (C) fruux GmbH (https://fruux.com/)
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
interface IOutbox extends \Sabre\DAV\ICollection, \Sabre\DAVACL\IACL
{
}
