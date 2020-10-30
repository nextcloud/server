<?php
namespace Psalm\Report;

use Psalm\Internal\Json\Json;
use function max;
use Psalm\Config;
use Psalm\Report;

/**
 * JSON report format suitable for import into SonarQube or SonarCloud as
 * generic (external) issue data via `sonar.externalIssuesReportPaths`.
 *
 * https://docs.sonarqube.org/latest/analysis/generic-issue/
 */
class SonarqubeReport extends Report
{
    public function create(): string
    {
        $report = ['issues' => []];

        foreach ($this->issues_data as $issue_data) {
            $report['issues'][] = [
                'engineId' => 'Psalm',
                'ruleId' => $issue_data->type,
                'primaryLocation' => [
                    'message' => $issue_data->message,
                    'filePath' => $issue_data->file_name,
                    'textRange' => [
                        'startLine' => $issue_data->line_from,
                        'endLine' => $issue_data->line_to,
                        // Columns in external issue reports are indexed from 0
                        'startColumn' => max(0, $issue_data->column_from - 1),
                        'endColumn' => max(0, $issue_data->column_to - 1),
                    ],
                ],
                'type' => 'CODE_SMELL',
                'severity' => $issue_data->severity === Config::REPORT_ERROR ? 'CRITICAL' : 'MINOR',
            ];
        }

        $options = $this->pretty ? Json::PRETTY : Json::DEFAULT;

        return Json::encode($report, $options) . "\n";
    }
}
