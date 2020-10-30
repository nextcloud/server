<?php
declare(strict_types=1);
namespace Psalm\Internal\Diff;

/**
 * @internal
 *
 * @psalm-immutable
 */
class DiffElem
{
    public const TYPE_KEEP = 0;
    public const TYPE_REMOVE = 1;
    public const TYPE_ADD = 2;
    public const TYPE_REPLACE = 3;
    public const TYPE_KEEP_SIGNATURE = 4;

    /** @var int One of the TYPE_* constants */
    public $type;
    /** @var mixed Is null for add operations */
    public $old;
    /** @var mixed Is null for remove operations */
    public $new;

    /**
     * @param mixed  $old
     * @param mixed  $new
     */
    public function __construct(int $type, $old, $new)
    {
        $this->type = $type;
        $this->old = $old;
        $this->new = $new;
    }
}
