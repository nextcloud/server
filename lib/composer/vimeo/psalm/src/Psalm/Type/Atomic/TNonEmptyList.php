<?php
namespace Psalm\Type\Atomic;

/**
 * Represents a non-empty array
 */
class TNonEmptyList extends TList
{
    /**
     * @var int|null
     */
    public $count;

    public const KEY = 'non-empty-list';

    public function getAssertionString(): string
    {
        return 'non-empty-list';
    }
}
