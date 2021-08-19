<?php

declare(strict_types=1);

namespace Sabre\CardDAV\Backend;

/**
 * CardDAV abstract Backend.
 *
 * This class serves as a base-class for addressbook backends
 *
 * This class doesn't do much, but it was added for consistency.
 *
 * @copyright Copyright (C) fruux GmbH (https://fruux.com/)
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
abstract class AbstractBackend implements BackendInterface
{
    /**
     * Returns a list of cards.
     *
     * This method should work identical to getCard, but instead return all the
     * cards in the list as an array.
     *
     * If the backend supports this, it may allow for some speed-ups.
     *
     * @param mixed $addressBookId
     *
     * @return array
     */
    public function getMultipleCards($addressBookId, array $uris)
    {
        return array_map(function ($uri) use ($addressBookId) {
            return $this->getCard($addressBookId, $uri);
        }, $uris);
    }
}
