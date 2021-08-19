<?php

namespace Doctrine\DBAL\Event;

use Doctrine\Common\EventArgs;

/**
 * Base class for schema related events.
 */
class SchemaEventArgs extends EventArgs
{
    /** @var bool */
    private $preventDefault = false;

    /**
     * @return SchemaEventArgs
     */
    public function preventDefault()
    {
        $this->preventDefault = true;

        return $this;
    }

    /**
     * @return bool
     */
    public function isDefaultPrevented()
    {
        return $this->preventDefault;
    }
}
