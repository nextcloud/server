<?php

const XDEBUG_TRACE_APPEND = 1;
const XDEBUG_TRACE_COMPUTERIZED = 2;
const XDEBUG_TRACE_HTML = 4;
const XDEBUG_TRACE_NAKED_FILENAME = 8;
const XDEBUG_CC_UNUSED = 1;
const XDEBUG_CC_DEAD_CODE = 2;
const XDEBUG_CC_BRANCH_CHECK = 4;
const XDEBUG_STACK_NO_DESC = 1;
const XDEBUG_FILTER_TRACING = 256;
const XDEBUG_FILTER_CODE_COVERAGE = 512;
const XDEBUG_FILTER_NONE = 0;
const XDEBUG_PATH_WHITELIST = 1;
const XDEBUG_PATH_BLACKLIST = 2;
const XDEBUG_NAMESPACE_WHITELIST = 17;
const XDEBUG_NAMESPACE_BLACKLIST = 18;

function xdebug_code_coverage_started() : bool
{
}

/**
* @return array<string, array<int, int>>
*/
function xdebug_get_code_coverage() : array
{
}

/**
* @param array<int, string> $configuration
*/
function xdebug_set_filter(int $group, int $list_type, array $configuration) : array
{
}

function xdebug_start_code_coverage(int $options) : void
{
}

function xdebug_stop_code_coverage(int $cleanup = 1) : void
{
}
