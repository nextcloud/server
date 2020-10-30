<?php
namespace Psalm;

use function sha1;
use function strlen;
use function strrpos;
use function substr;
use function trim;

class FileManipulation
{
    /** @var int */
    public $start;

    /** @var int */
    public $end;

    /** @var string */
    public $insertion_text;

    /** @var bool */
    public $preserve_indentation;

    /** @var bool */
    public $remove_trailing_newline;

    public function __construct(
        int $start,
        int $end,
        string $insertion_text,
        bool $preserve_indentation = false,
        bool $remove_trailing_newline = false
    ) {
        $this->start = $start;
        $this->end = $end;
        $this->insertion_text = $insertion_text;
        $this->preserve_indentation = $preserve_indentation;
        $this->remove_trailing_newline = $remove_trailing_newline;
    }

    public function getKey() : string
    {
        return $this->start === $this->end
            ? ($this->start . ':' . sha1($this->insertion_text))
            : ($this->start . ':' . $this->end);
    }

    public function transform(string $existing_contents) : string
    {
        if ($this->preserve_indentation) {
            $newline_pos = strrpos($existing_contents, "\n", $this->start - strlen($existing_contents));

            $newline_pos = $newline_pos !== false ? $newline_pos + 1 : 0;

            $indentation = substr($existing_contents, $newline_pos, $this->start - $newline_pos);

            if (trim($indentation) === '') {
                $this->insertion_text = $this->insertion_text . $indentation;
            }
        }

        if ($this->remove_trailing_newline
            && strlen($existing_contents) > $this->end
            && $existing_contents[$this->end] === "\n"
        ) {
            $newline_pos = strrpos($existing_contents, "\n", $this->start - strlen($existing_contents));

            $newline_pos = $newline_pos !== false ? $newline_pos + 1 : 0;

            $indentation = substr($existing_contents, $newline_pos, $this->start - $newline_pos);

            if (trim($indentation) === '') {
                $this->start -= strlen($indentation);
                $this->end++;
            }
        }

        return substr($existing_contents, 0, $this->start)
            . $this->insertion_text
            . substr($existing_contents, $this->end);
    }
}
