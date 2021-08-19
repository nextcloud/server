<?php

declare(strict_types=1);

namespace Sabre\DAVACL\PrincipalBackend;

/**
 * Abstract Principal Backend.
 *
 * Currently this class has no function. It's here for consistency and so we
 * have a non-bc-breaking way to add a default generic implementation to
 * functions we may add in the future.
 *
 * @copyright Copyright (C) fruux GmbH (https://fruux.com/)
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
abstract class AbstractBackend implements BackendInterface
{
    /**
     * Finds a principal by its URI.
     *
     * This method may receive any type of uri, but mailto: addresses will be
     * the most common.
     *
     * Implementation of this API is optional. It is currently used by the
     * CalDAV system to find principals based on their email addresses. If this
     * API is not implemented, some features may not work correctly.
     *
     * This method must return a relative principal path, or null, if the
     * principal was not found or you refuse to find it.
     *
     * @param string $uri
     * @param string $principalPrefix
     *
     * @return string
     */
    public function findByUri($uri, $principalPrefix)
    {
        // Note that the default implementation here is a bit slow and could
        // likely be optimized.
        if ('mailto:' !== substr($uri, 0, 7)) {
            return;
        }
        $result = $this->searchPrincipals(
            $principalPrefix,
            ['{http://sabredav.org/ns}email-address' => substr($uri, 7)]
        );

        if ($result) {
            return $result[0];
        }
    }
}
