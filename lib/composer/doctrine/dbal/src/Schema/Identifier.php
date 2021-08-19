<?php

namespace Doctrine\DBAL\Schema;

/**
 * An abstraction class for an asset identifier.
 *
 * Wraps identifier names like column names in indexes / foreign keys
 * in an abstract class for proper quotation capabilities.
 */
class Identifier extends AbstractAsset
{
    /**
     * @param string $identifier Identifier name to wrap.
     * @param bool   $quote      Whether to force quoting the given identifier.
     */
    public function __construct($identifier, $quote = false)
    {
        $this->_setName($identifier);

        if (! $quote || $this->_quoted) {
            return;
        }

        $this->_setName('"' . $this->getName() . '"');
    }
}
