<?php

namespace Psalm\Internal\DataFlow;

/**
 * @psalm-immutable
 */
class Path
{
    public $type;

    public $unescaped_taints;

    public $escaped_taints;

    /**
     * @param ?array<string> $unescaped_taints
     * @param ?array<string> $escaped_taints
     */
    public function __construct(string $type, ?array $unescaped_taints, ?array $escaped_taints)
    {
        $this->type = $type;
        $this->unescaped_taints = $unescaped_taints;
        $this->escaped_taints = $escaped_taints;
    }
}
