<?php
namespace Psalm\Report;

use Psalm\Config;
use Psalm\Report;
use function sprintf;

class PylintReport extends Report
{
    public function create(): string
    {
        $output = '';
        foreach ($this->issues_data as $issue_data) {
            $output .= $this->format($issue_data) . "\n";
        }

        return $output;
    }

    private function format(\Psalm\Internal\Analyzer\IssueData $issue_data): string
    {
        $message = sprintf(
            '%s: %s',
            $issue_data->type,
            $issue_data->message
        );

        if ($issue_data->severity === Config::REPORT_ERROR) {
            $code = 'E0001';
        } else {
            $code = 'W0001';
        }

        // https://docs.pylint.org/en/1.6.0/output.html doesn't mention what to do about 'column',
        // but it's still useful for users.
        // E.g. jenkins can't parse %s:%d:%d.
        $message = sprintf('%s (column %d)', $message, $issue_data->column_from);
        $issue_string = sprintf(
            '%s:%d: [%s] %s',
            $issue_data->file_name,
            $issue_data->line_from,
            $code,
            $message
        );

        return $issue_string;
    }
}
