<?php
namespace Psalm\Report;

use Psalm\Report;

class ReportOptions
{
    /**
     * @var bool
     */
    public $use_color = true;

    /**
     * @var bool
     */
    public $show_snippet = true;

    /**
     * @var bool
     */
    public $show_info = true;

    /**
     * @var value-of<Report::SUPPORTED_OUTPUT_TYPES>
     */
    public $format = Report::TYPE_CONSOLE;

    /**
     * @var bool
     */
    public $pretty = false;

    /**
     * @var ?string
     */
    public $output_path;

    /**
     * @var bool
     */
    public $show_suggestions = true;
}
