<?php
namespace Psalm;

use function explode;
use function max;
use function min;
use PhpParser;
use function preg_match;
use const PREG_OFFSET_CAPTURE;
use function preg_quote;
use function preg_replace;
use Psalm\Internal\Analyzer\CommentAnalyzer;
use function str_replace;
use function strlen;
use function strpos;
use function strrpos;
use function substr;
use function substr_count;
use function trim;

class CodeLocation
{
    /** @var string */
    public $file_path;

    /** @var string */
    public $file_name;

    /** @var int */
    public $raw_line_number;

    /** @var int */
    private $end_line_number = -1;

    /** @var int */
    public $raw_file_start;

    /** @var int */
    public $raw_file_end;

    /** @var int */
    protected $file_start;

    /** @var int */
    protected $file_end;

    /** @var bool */
    protected $single_line;

    /** @var int */
    protected $preview_start;

    /** @var int */
    private $preview_end = -1;

    /** @var int */
    private $selection_start = -1;

    /** @var int */
    private $selection_end = -1;

    /** @var int */
    private $column_from = -1;

    /** @var int */
    private $column_to = -1;

    /** @var string */
    private $snippet = '';

    /** @var null|string */
    private $text;

    /** @var int|null */
    public $docblock_start;

    /** @var int|null */
    public $docblock_end;

    /** @var int|null */
    private $docblock_start_line_number;

    /** @var int|null */
    private $docblock_line_number;

    /** @var null|int */
    private $regex_type;

    /** @var bool */
    private $have_recalculated = false;

    /** @var null|CodeLocation */
    public $previous_location;

    public const VAR_TYPE = 0;
    public const FUNCTION_RETURN_TYPE = 1;
    public const FUNCTION_PARAM_TYPE = 2;
    public const FUNCTION_PHPDOC_RETURN_TYPE = 3;
    public const FUNCTION_PHPDOC_PARAM_TYPE = 4;
    public const FUNCTION_PARAM_VAR = 5;
    public const CATCH_VAR = 6;
    public const FUNCTION_PHPDOC_METHOD = 7;

    public function __construct(
        FileSource $file_source,
        PhpParser\Node $stmt,
        ?CodeLocation $previous_location = null,
        bool $single_line = false,
        ?int $regex_type = null,
        ?string $selected_text = null
    ) {
        $this->file_start = (int)$stmt->getAttribute('startFilePos');
        $this->file_end = (int)$stmt->getAttribute('endFilePos');
        $this->raw_file_start = $this->file_start;
        $this->raw_file_end = $this->file_end;
        $this->file_path = $file_source->getFilePath();
        $this->file_name = $file_source->getFileName();
        $this->single_line = $single_line;
        $this->regex_type = $regex_type;
        $this->previous_location = $previous_location;
        $this->text = $selected_text;

        $doc_comment = $stmt->getDocComment();

        $this->docblock_start = $doc_comment ? $doc_comment->getStartFilePos() : null;
        $this->docblock_end = $doc_comment ? $this->file_start : null;
        $this->docblock_start_line_number = $doc_comment ? $doc_comment->getStartLine() : null;

        $this->preview_start = $this->docblock_start ?: $this->file_start;

        $this->raw_line_number = $stmt->getLine();
    }

    public function setCommentLine(int $line): void
    {
        $this->docblock_line_number = $line;
    }

    /**
     * @psalm-suppress MixedArrayAccess
     */
    private function calculateRealLocation(): void
    {
        if ($this->have_recalculated) {
            return;
        }

        $this->have_recalculated = true;

        $this->selection_start = $this->file_start;
        $this->selection_end = $this->file_end + 1;

        $project_analyzer = Internal\Analyzer\ProjectAnalyzer::getInstance();

        $codebase = $project_analyzer->getCodebase();

        $file_contents = $codebase->getFileContents($this->file_path);

        $file_length = strlen($file_contents);

        $search_limit = $this->single_line ? $this->selection_start : $this->selection_end;

        if ($search_limit <= $file_length) {
            $preview_end = strpos(
                $file_contents,
                "\n",
                $search_limit
            );
        } else {
            $preview_end = false;
        }

        // if the string didn't contain a newline
        if ($preview_end === false) {
            $preview_end = $this->selection_end;
        }

        $this->preview_end = $preview_end;

        if ($this->docblock_line_number &&
            $this->docblock_start_line_number &&
            $this->preview_start < $this->selection_start
        ) {
            $preview_lines = explode(
                "\n",
                substr(
                    $file_contents,
                    $this->preview_start,
                    $this->selection_start - $this->preview_start - 1
                )
            );

            $preview_offset = 0;

            $comment_line_offset = $this->docblock_line_number - $this->docblock_start_line_number;

            for ($i = 0; $i < $comment_line_offset; ++$i) {
                $preview_offset += strlen($preview_lines[$i]) + 1;
            }

            if (!isset($preview_lines[$i])) {
                throw new \Exception('Should have offset');
            }

            $key_line = $preview_lines[$i];

            $indentation = (int)strpos($key_line, '@');

            $key_line = trim(preg_replace('@\**/\s*@', '', substr($key_line, $indentation)));

            $this->selection_start = $preview_offset + $indentation + $this->preview_start;
            $this->selection_end = $this->selection_start + strlen($key_line);
        }

        if ($this->regex_type !== null) {
            switch ($this->regex_type) {
                case self::VAR_TYPE:
                    $regex = '/@(psalm-)?var[ \t]+' . CommentAnalyzer::TYPE_REGEX . '/';
                    $match_offset = 2;
                    break;

                case self::FUNCTION_RETURN_TYPE:
                    $regex = '/\\:\s+(\\??\s*[A-Za-z0-9_\\\\\[\]]+)/';
                    $match_offset = 1;
                    break;

                case self::FUNCTION_PARAM_TYPE:
                    $regex = '/^(\\??\s*[A-Za-z0-9_\\\\\[\]]+)\s/';
                    $match_offset = 1;
                    break;

                case self::FUNCTION_PHPDOC_RETURN_TYPE:
                    $regex = '/@(psalm-)?return[ \t]+' . CommentAnalyzer::TYPE_REGEX . '/';
                    $match_offset = 2;
                    break;

                case self::FUNCTION_PHPDOC_METHOD:
                    $regex = '/@(psalm-)method[ \t]+.*/';
                    $match_offset = 2;
                    break;

                case self::FUNCTION_PHPDOC_PARAM_TYPE:
                    $regex = '/@(psalm-)?param[ \t]+' . CommentAnalyzer::TYPE_REGEX . '/';
                    $match_offset = 2;
                    break;

                case self::FUNCTION_PARAM_VAR:
                    $regex = '/(\$[^ ]*)/';
                    $match_offset = 1;
                    break;

                case self::CATCH_VAR:
                    $regex = '/(\$[^ ^\)]*)/';
                    $match_offset = 1;
                    break;

                default:
                    throw new \UnexpectedValueException('Unrecognised regex type ' . $this->regex_type);
            }

            $preview_snippet = substr(
                $file_contents,
                $this->selection_start,
                $this->selection_end - $this->selection_start
            );

            if ($this->text) {
                $regex = '/(' . str_replace(',', ',[ ]*', preg_quote($this->text, '/')) . ')/';
                $match_offset = 1;
            }

            if (preg_match($regex, $preview_snippet, $matches, PREG_OFFSET_CAPTURE)) {
                $this->selection_start = $this->selection_start + (int)$matches[$match_offset][1];
                $this->selection_end = $this->selection_start + strlen((string)$matches[$match_offset][0]);
            }
        }

        // reset preview start to beginning of line
        $this->preview_start = (int)strrpos(
            $file_contents,
            "\n",
            min($this->preview_start, $this->selection_start) - strlen($file_contents)
        ) + 1;

        $this->selection_start = max($this->preview_start, $this->selection_start);
        $this->selection_end = min($this->preview_end, $this->selection_end);

        if ($this->preview_end - $this->selection_end > 200) {
            $this->preview_end = (int)strrpos(
                $file_contents,
                "\n",
                $this->selection_end + 200 - strlen($file_contents)
            );

            // if the line is over 200 characters long
            if ($this->preview_end < $this->selection_end) {
                $this->preview_end = $this->selection_end + 50;
            }
        }

        $this->snippet = substr($file_contents, $this->preview_start, $this->preview_end - $this->preview_start);
        $this->text = substr($file_contents, $this->selection_start, $this->selection_end - $this->selection_start);

        // reset preview start to beginning of line
        $this->column_from = $this->selection_start -
            (int)strrpos($file_contents, "\n", $this->selection_start - strlen($file_contents));

        $newlines = substr_count($this->text, "\n");

        if ($newlines) {
            $this->column_to = $this->selection_end -
                (int)strrpos($file_contents, "\n", $this->selection_end - strlen($file_contents));
        } else {
            $this->column_to = $this->column_from + strlen($this->text);
        }

        $this->end_line_number = $this->getLineNumber() + $newlines;
    }

    public function getLineNumber(): int
    {
        return $this->docblock_line_number ?: $this->raw_line_number;
    }

    public function getEndLineNumber(): int
    {
        $this->calculateRealLocation();

        return $this->end_line_number;
    }

    public function getSnippet(): string
    {
        $this->calculateRealLocation();

        return $this->snippet;
    }

    public function getSelectedText(): string
    {
        $this->calculateRealLocation();

        return (string)$this->text;
    }

    public function getColumn(): int
    {
        $this->calculateRealLocation();

        return $this->column_from;
    }

    public function getEndColumn(): int
    {
        $this->calculateRealLocation();

        return $this->column_to;
    }

    /**
     * @return array{0: int, 1: int}
     */
    public function getSelectionBounds(): array
    {
        $this->calculateRealLocation();

        return [$this->selection_start, $this->selection_end];
    }

    /**
     * @return array{0: int, 1: int}
     */
    public function getSnippetBounds(): array
    {
        $this->calculateRealLocation();

        return [$this->preview_start, $this->preview_end];
    }

    public function getHash(): string
    {
        return (string) $this->file_start;
    }

    public function getShortSummary() : string
    {
        return $this->file_name . ':' . $this->getLineNumber() . ':' . $this->getColumn();
    }
}
