<?php

declare(strict_types=1);

namespace OpenStack\Common\Resource;

/**
 * Represents a resource that can be deleted.
 */
interface Deletable
{
    /**
     * Permanently delete this resource.
     */
    public function delete();
}
