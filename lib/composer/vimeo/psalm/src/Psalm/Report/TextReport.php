<?php
namespace Psalm\Report;

use Psalm\Config;
use Psalm\Report;
use function sprintf;

class TextReport extends Report
{
    public function create(): string
    {
        $output = '';
        foreach ($this->issues_data as $issue_data) {
            $output .= sprintf(
                '%s:%s:%s:%s - %s: %s',
                $issue_data->file_path,
                $issue_data->line_from,
                $issue_data->column_from,
                ($issue_data->severity === Config::REPORT_ERROR ? 'error' : 'warning'),
                $issue_data->type,
                $issue_data->message
            ) . "\n";
        }

        return $output;
    }
}
