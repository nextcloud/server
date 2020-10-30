<?php
namespace Psalm\CodeLocation;

use function substr;
use function substr_count;

class Raw extends \Psalm\CodeLocation
{
    public function __construct(
        string $file_contents,
        string $file_path,
        string $file_name,
        int $file_start,
        int $file_end
    ) {
        $this->file_start = $file_start;
        $this->file_end = $file_end;
        $this->raw_file_start = $this->file_start;
        $this->raw_file_end = $this->file_end;
        $this->file_path = $file_path;
        $this->file_name = $file_name;
        $this->single_line = false;

        $this->preview_start = $this->file_start;
        $this->raw_line_number = substr_count(
            substr($file_contents, 0, $this->file_start),
            "\n"
        ) + 1;
    }
}
