<?php

declare(strict_types=1);

namespace Sabre\DAVACL\PrincipalBackend;

use Sabre\DAV\MkCol;

/**
 * Implement this interface to add support for creating new principals to your
 * principal backend.
 *
 * @copyright Copyright (C) fruux GmbH (https://fruux.com/)
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
interface CreatePrincipalSupport extends BackendInterface
{
    /**
     * Creates a new principal.
     *
     * This method receives a full path for the new principal. The mkCol object
     * contains any additional webdav properties specified during the creation
     * of the principal.
     *
     * @param string $path
     */
    public function createPrincipal($path, MkCol $mkCol);
}
