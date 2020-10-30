<?php

require_once(dirname(__DIR__) . '/vendor/autoload.php');

$issue_types = \Psalm\Config\IssueHandler::getAllIssueTypes();

$shortcodes = array_map(
    function ($issue_type): int {
        $issue_class = '\\Psalm\\Issue\\' . $issue_type;
        /** @var int */
        return $issue_class::SHORTCODE;
    },
    $issue_types
);

echo 'Max used shortcode: ' . max($shortcodes) . PHP_EOL;
