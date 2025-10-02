<?php

namespace Doctrine\DBAL\Event;

use Doctrine\Common\EventArgs;

/**
 * Base class for schema related events.
 *
 * @deprecated
 */
class SchemaEventArgs extends EventArgs
{
    private bool $preventDefault = false;

    /** @return SchemaEventArgs */
    public function preventDefault()
    {
        $this->preventDefault = true;

        return $this;
    }

    /** @return bool */
    public function isDefaultPrevented()
    {
        return $this->preventDefault;
    }
}
