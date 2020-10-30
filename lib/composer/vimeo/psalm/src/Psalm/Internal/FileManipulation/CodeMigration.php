<?php
namespace Psalm\Internal\FileManipulation;

/**
 * @psalm-immutable
 */
class CodeMigration
{
    /** @var string */
    public $source_file_path;

    /** @var int */
    public $source_start;

    /** @var int */
    public $source_end;

    /** @var string */
    public $destination_file_path;

    /** @var int */
    public $destination_start;

    public function __construct(
        string $source_file_path,
        int $source_start,
        int $source_end,
        string $destination_file_path,
        int $destination_start
    ) {
        $this->source_file_path = $source_file_path;
        $this->source_start = $source_start;
        $this->source_end = $source_end;
        $this->destination_file_path = $destination_file_path;
        $this->destination_start = $destination_start;
    }
}
