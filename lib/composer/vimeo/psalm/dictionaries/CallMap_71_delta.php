<?php // phpcs:ignoreFile

/**
 * This contains the information needed to convert the function signatures for php 7.1 to php 7.0 (and vice versa)
 *
 * This has two sections.
 * The 'new' section contains function/method names from FunctionSignatureMap (And alternates, if applicable) that do not exist in php7.0 or have different signatures in php 7.1.
 *   If they were just updated, the function/method will be present in the 'added' signatures.
 * The 'old' signatures contains the signatures that are different in php 7.0.
 *   Functions are expected to be removed only in major releases of php. (e.g. php 7.0 removed various functions that were deprecated in 5.6)
 *
 * @see FunctionSignatureMap.php
 *
 * @phan-file-suppress PhanPluginMixedKeyNoKey (read by Phan when analyzing this file)
 */
return [
'new' => [
    'Closure::fromCallable' => ['Closure', 'callable'=>'callable'],
    'SQLite3::createFunction' => ['bool', 'name'=>'string', 'callback'=>'callable', 'argument_count='=>'int', 'flags='=>'int'],
    'curl_multi_errno' => ['int', 'mh'=>'resource'],
    'curl_share_errno' => ['int', 'sh'=>'resource'],
    'curl_share_strerror' => ['string', 'code'=>'int'],
    'get_headers' => ['array|false', 'url'=>'string', 'format='=>'int', 'context='=>'resource'],
    'getenv\'1' => ['array<string,string>'],
    'getopt' => ['array<string,string>|array<string,false>|array<string,array<int,string|false>>', 'options'=>'string', 'longopts='=>'array', '&w_optind='=>'int'],
    'hash_hkdf' => ['string', 'algo'=>'string', 'ikm'=>'string', 'length='=>'int', 'info='=>'string', 'salt='=>'string'],
    'is_iterable' => ['bool', 'var'=>'mixed'],
    'openssl_get_curve_names' => ['array<int,string>'],
    'pcntl_async_signals' => ['bool', 'on='=>'bool'],
    'pcntl_signal_get_handler' => ['int|string', 'signo'=>'int'],
    'pg_fetch_all' => ['array', 'result'=>'resource', 'result_type='=>'int'],
    'pg_last_error' => ['string', 'connection='=>'resource', 'operation='=>'int'],
    'pg_select' => ['mixed', 'db'=>'resource', 'table'=>'string', 'ids'=>'array', 'options='=>'int', 'result_type='=>'int'],
    'sapi_windows_cp_conv' => ['string', 'in_codepage'=>'int|string', 'out_codepage'=>'int|string', 'subject'=>'string'],
    'sapi_windows_cp_get' => ['int'],
    'sapi_windows_cp_is_utf8' => ['bool'],
    'sapi_windows_cp_set' => ['bool', 'code_page'=>'int'],
    'session_create_id' => ['string', 'prefix='=>'string'],
    'session_gc' => ['int'],
    'unpack' => ['array', 'format'=>'string', 'data'=>'string', 'offset='=>'int'],
],
'old' => [
    'SQLite3::createFunction' => ['bool', 'name'=>'string', 'callback'=>'callable', 'argument_count='=>'int'],
    'get_headers' => ['array|false', 'url'=>'string', 'format='=>'int'],
    'getopt' => ['array<string,string>|array<string,false>|array<string,array<int,string|false>>', 'options'=>'string', 'longopts='=>'array'],
    'pg_fetch_all' => ['array', 'result'=>'resource'],
    'pg_last_error' => ['string', 'connection='=>'resource'],
    'pg_select' => ['mixed', 'db'=>'resource', 'table'=>'string', 'ids'=>'array', 'options='=>'int'],
    'unpack' => ['array', 'format'=>'string', 'data'=>'string'],
],
];
