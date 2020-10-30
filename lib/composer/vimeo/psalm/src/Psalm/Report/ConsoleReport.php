<?php
namespace Psalm\Report;

use Psalm\Config;
use Psalm\Report;
use Psalm\Internal\Analyzer\DataFlowNodeData;
use function substr;

class ConsoleReport extends Report
{
    public function create(): string
    {
        $output = '';
        foreach ($this->issues_data as $issue_data) {
            $output .= $this->format($issue_data) . "\n" . "\n";
        }

        return $output;
    }

    private function format(\Psalm\Internal\Analyzer\IssueData $issue_data): string
    {
        $issue_string = '';

        $is_error = $issue_data->severity === Config::REPORT_ERROR;

        if ($is_error) {
            $issue_string .= ($this->use_color ? "\e[0;31mERROR\e[0m" : 'ERROR');
        } else {
            $issue_string .= 'INFO';
        }

        $issue_reference = ' (see ' . $issue_data->link . ')';

        $issue_string .= ': ' . $issue_data->type
            . ' - ' . $issue_data->file_name . ':' . $issue_data->line_from . ':' . $issue_data->column_from
            . ' - ' . $issue_data->message . $issue_reference . "\n";


        if ($issue_data->taint_trace) {
            $issue_string .= $this->getTaintSnippets($issue_data->taint_trace);
        } elseif ($this->show_snippet) {
            $snippet = $issue_data->snippet;

            if (!$this->use_color) {
                $issue_string .= $snippet;
            } else {
                $selection_start = $issue_data->from - $issue_data->snippet_from;
                $selection_length = $issue_data->to - $issue_data->from;

                $issue_string .= substr($snippet, 0, $selection_start)
                    . ($is_error ? "\e[97;41m" : "\e[30;47m") . substr($snippet, $selection_start, $selection_length)
                    . "\e[0m" . substr($snippet, $selection_length + $selection_start) . "\n";
            }
        }

        return $issue_string;
    }

    /**
     * @param non-empty-list<DataFlowNodeData|array{label: string, entry_path_type: string}> $taint_trace
     */
    private function getTaintSnippets(array $taint_trace) : string
    {
        $snippets = '';

        foreach ($taint_trace as $node_data) {
            if ($node_data instanceof DataFlowNodeData) {
                $snippets .= '  ' . $node_data->label
                    . ' - ' . $node_data->file_name
                    . ':' . $node_data->line_from
                    . ':' . $node_data->column_from . "\n";

                if ($this->show_snippet) {
                    $snippet = $node_data->snippet;

                    if (!$this->use_color) {
                        $snippets .= $snippet . "\n\n";
                    } else {
                        $selection_start = $node_data->from - $node_data->snippet_from;
                        $selection_length = $node_data->to - $node_data->from;

                        $snippets .= substr($snippet, 0, $selection_start)
                            . "\e[30;47m" . substr($snippet, $selection_start, $selection_length)
                            . "\e[0m" . substr($snippet, $selection_length + $selection_start) . "\n\n";
                    }
                }
            } else {
                $snippets .= '  ' . $node_data['label'] . "\n";
                $snippets .= '    <no known location>' . "\n\n";
            }
        }

        return $snippets;
    }
}
