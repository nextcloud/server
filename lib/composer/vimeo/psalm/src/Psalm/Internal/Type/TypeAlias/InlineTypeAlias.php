<?php
namespace Psalm\Internal\Type\TypeAlias;

/**
 * @psalm-immutable
 */
class InlineTypeAlias implements \Psalm\Internal\Type\TypeAlias
{
    /**
     * @var list<array{0: string, 1: int}>
     */
    public $replacement_tokens;

    /**
     * @param list<array{0: string, 1: int}> $replacement_tokens
     */
    public function __construct(array $replacement_tokens)
    {
        $this->replacement_tokens = $replacement_tokens;
    }
}
