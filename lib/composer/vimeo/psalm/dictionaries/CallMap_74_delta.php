<?php // phpcs:ignoreFile

/**
 * This contains the information needed to convert the function signatures for php 7.4 to php 7.3 (and vice versa)
 *
 * This has two sections.
 * The 'new' section contains function/method names from FunctionSignatureMap (And alternates, if applicable) that do not exist in php7.3 or have different signatures in php 7.4.
 *   If they were just updated, the function/method will be present in the 'added' signatures.
 * The 'old' signatures contains the signatures that are different in php 7.3.
 *   Functions are expected to be removed only in major releases of php. (e.g. php 7.0 removed various functions that were deprecated in 5.6)
 *
 * @see FunctionSignatureMap.php
 *
 * @phan-file-suppress PhanPluginMixedKeyNoKey (read by Phan when analyzing this file)
 */
return [
'new' => [
    'password_hash' => ['string|null', 'password'=>'string', 'algo'=>'int|string|null', 'options='=>'array'],
    'password_needs_rehash' => ['bool', 'hash'=>'string', 'algo'=>'int|string|null', 'options='=>'array'],
    'proc_open' => ['resource|false', 'command'=>'string|array', 'descriptorspec'=>'array', '&w_pipes'=>'resource[]', 'cwd='=>'?string', 'env='=>'?array', 'other_options='=>'array'],
    'ReflectionProperty::getType' => ['?ReflectionType'],
],
'old' => [
    'password_hash' => ['string|false', 'password'=>'string', 'algo'=>'int', 'options='=>'array'],
    'password_needs_rehash' => ['bool', 'hash'=>'string', 'algo'=>'int', 'options='=>'array'],
    'proc_open' => ['resource|false', 'command'=>'string', 'descriptorspec'=>'array', '&w_pipes'=>'resource[]', 'cwd='=>'?string', 'env='=>'?array', 'other_options='=>'array'],
]
];
