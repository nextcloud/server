<?php

namespace Psalm\Internal\Analyzer;

class IssueData
{
    /**
     * @var string
     */
    public $severity;

    /**
     * @var int
     * @readonly
     */
    public $line_from;

    /**
     * @var int
     * @readonly
     */
    public $line_to;

    /**
     * @var string
     * @readonly
     */
    public $type;

    /**
     * @var string
     * @readonly
     */
    public $message;

    /**
     * @var string
     * @readonly
     */
    public $file_name;

    /**
     * @var string
     * @readonly
     */
    public $file_path;

    /**
     * @var string
     * @readonly
     */
    public $snippet;

    /**
     * @var string
     * @readonly
     */
    public $selected_text;

    /**
     * @var int
     * @readonly
     */
    public $from;

    /**
     * @var int
     * @readonly
     */
    public $to;

    /**
     * @var int
     * @readonly
     */
    public $snippet_from;

    /**
     * @var int
     * @readonly
     */
    public $snippet_to;

    /**
     * @var int
     * @readonly
     */
    public $column_from;

    /**
     * @var int
     * @readonly
     */
    public $column_to;

    /**
     * @var int
     */
    public $error_level;

    /**
     * @var int
     * @readonly
     */
    public $shortcode;

    /**
     * @var string
     * @readonly
     */
    public $link;

    /**
     * @var ?list<DataFlowNodeData|array{label: string, entry_path_type: string}>
     */
    public $taint_trace;

    /**
     * @var ?string
     * @readonly
     */
    public $dupe_key;

    /**
     * @param ?list<DataFlowNodeData|array{label: string, entry_path_type: string}> $taint_trace
     */
    public function __construct(
        string $severity,
        int $line_from,
        int $line_to,
        string $type,
        string $message,
        string $file_name,
        string $file_path,
        string $snippet,
        string $selected_text,
        int $from,
        int $to,
        int $snippet_from,
        int $snippet_to,
        int $column_from,
        int $column_to,
        int $shortcode = 0,
        int $error_level = -1,
        ?array $taint_trace = null,
        ?string $dupe_key = null
    ) {
        $this->severity = $severity;
        $this->line_from = $line_from;
        $this->line_to = $line_to;
        $this->type = $type;
        $this->message = $message;
        $this->file_name = $file_name;
        $this->file_path = $file_path;
        $this->snippet = $snippet;
        $this->selected_text = $selected_text;
        $this->from = $from;
        $this->to = $to;
        $this->snippet_from = $snippet_from;
        $this->snippet_to = $snippet_to;
        $this->column_from = $column_from;
        $this->column_to = $column_to;
        $this->shortcode = $shortcode;
        $this->error_level = $error_level;
        $this->link = 'https://psalm.dev/' . \str_pad((string) $shortcode, 3, "0", \STR_PAD_LEFT);
        $this->taint_trace = $taint_trace;
        $this->dupe_key = $dupe_key;
    }
}
