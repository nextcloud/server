<?php

declare(strict_types=1);

namespace Sabre\DAV\Locks\Backend;

/**
 * This is an Abstract clas for lock backends.
 *
 * Currently this backend has no function, but it exists for consistency, and
 * to ensure that if default code is required in the backend, there will be a
 * non-bc-breaking way to do so.
 *
 * @copyright Copyright (C) fruux GmbH (https://fruux.com/)
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
abstract class AbstractBackend implements BackendInterface
{
}
