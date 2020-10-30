<?php

require_once(dirname(__DIR__) . '/vendor/autoload.php');

$issue_types = \Psalm\Config\IssueHandler::getAllIssueTypes();

$grouped_issues = [];

foreach ($issue_types as $issue_type) {
    $issue_class = 'Psalm\\Issue\\' . $issue_type;

    if (!class_exists($issue_class) || !is_a($issue_class, \Psalm\Issue\CodeIssue::class, true)) {
        throw new Exception($issue_class . ' is not a Codeissue');
    }

    /** @var int */
    $issue_level = $issue_class::ERROR_LEVEL;

    $grouped_issues[$issue_level][] = $issue_type;
}
?>

## Always treated as errors

<?php
foreach ($grouped_issues[-1] as $issue_type) {
    echo ' - [' . $issue_type . '](issues/' . $issue_type . '.md)' . "\n";
}
?>

## Errors that only appear at level 1

<?php
foreach ($grouped_issues[1] as $issue_type) {
    echo ' - [' . $issue_type . '](issues/' . $issue_type . '.md)' . "\n";
}
?>

<?php
foreach ([2, 3, 4, 5, 6, 7] as $level) {
    echo '## Errors ignored at level ' . ($level + 1) . ($level < 7 ? ' and higher' : '') . "\n\n";

    echo 'These issues are treated as errors at level ' . $level . ' and below.' . "\n\n";

    foreach ($grouped_issues[$level] as $issue_type) {
        echo ' - [' . $issue_type . '](issues/' . $issue_type . '.md)' . "\n";
    }

    echo "\n";
}
?>

## Feature-specific errors

<?php
foreach ($grouped_issues[-2] as $issue_type) {
    echo ' - [' . $issue_type . '](issues/' . $issue_type . '.md)' . "\n";
}
?>
