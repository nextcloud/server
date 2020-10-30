<?php
namespace Psalm\Internal\Type\TypeAlias;

/**
 * @psalm-immutable
 */
class LinkableTypeAlias implements \Psalm\Internal\Type\TypeAlias
{
    public $declaring_fq_classlike_name;

    public $alias_name;

    public $line_number;

    public $start_offset;

    public $end_offset;

    public function __construct(
        string $declaring_fq_classlike_name,
        string $alias_name,
        int $line_number,
        int $start_offset,
        int $end_offset
    ) {
        $this->declaring_fq_classlike_name = $declaring_fq_classlike_name;
        $this->alias_name = $alias_name;
        $this->line_number = $line_number;
        $this->start_offset = $start_offset;
        $this->end_offset = $end_offset;
    }
}
