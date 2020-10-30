<?php
namespace Psalm\Report;

use function count;
use function implode;
use Psalm\Config;
use Psalm\Report;
use function str_split;
use function strlen;
use function strtoupper;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Output\BufferedOutput;

class CompactReport extends Report
{
    /**
     * @psalm-suppress PossiblyNullReference
     */
    public function create(): string
    {
        /** @var BufferedOutput|null $buffer */
        $buffer = null;

        /** @var Table|null $table */
        $table = null;

        /** @var string|null $current_file */
        $current_file = null;

        $output = [];
        foreach ($this->issues_data as $i => $issue_data) {
            if (!$this->show_info && $issue_data->severity === Config::REPORT_INFO) {
                continue;
            } elseif ($current_file === null || $current_file !== $issue_data->file_name) {
                // If we're processing a new file, then wrap up the last table and render it out.
                if ($buffer !== null) {
                    $table->render();
                    $output[] = $buffer->fetch();
                }

                $output[] = 'FILE: ' . $issue_data->file_name . "\n";

                $buffer = new BufferedOutput();
                $table = new Table($buffer);
                $table->setHeaders(['SEVERITY', 'LINE', 'ISSUE', 'DESCRIPTION']);
            }

            $is_error = $issue_data->severity === Config::REPORT_ERROR;
            if ($is_error) {
                $severity = ($this->use_color ? "\e[0;31mERROR\e[0m" : 'ERROR');
            } else {
                $severity = strtoupper($issue_data->severity);
            }

            // Since `Table::setColumnMaxWidth` is only available in symfony/console 4.2+ we need do something similar
            // so we have clean tables.
            $message = $issue_data->message;
            if (strlen($message) > 70) {
                $message = implode("\n", str_split($message, 70));
            }

            $table->addRow([
                $severity,
                $issue_data->line_from,
                $issue_data->type,
                $message,
            ]);

            $current_file = $issue_data->file_name;

            // If we're at the end of the issue sets, then wrap up the last table and render it out.
            if ($i === count($this->issues_data) - 1) {
                $table->render();
                $output[] = $buffer->fetch();
            }
        }

        return implode("\n", $output);
    }
}
