<?php

declare(strict_types=1);

namespace OpenStack\Common\Resource;

/**
 * Represents a resource that can be created.
 */
interface Creatable
{
    /**
     * Create a new resource according to the configuration set in the options.
     *
     * @return self
     */
    public function create(array $userOptions): Creatable;
}
