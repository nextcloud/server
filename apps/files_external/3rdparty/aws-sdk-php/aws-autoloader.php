<?php
/**
 * Copyright 2010-2013 Amazon.com, Inc. or its affiliates. All Rights Reserved.
 *
 * Licensed under the Apache License, Version 2.0 (the "License").
 * You may not use this file except in compliance with the License.
 * A copy of the License is located at
 *
 * http://aws.amazon.com/apache2.0
 *
 * or in the "license" file accompanying this file. This file is distributed
 * on an "AS IS" BASIS, WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either
 * express or implied. See the License for the specific language governing
 * permissions and limitations under the License.
 */

require_once __DIR__ . '/Symfony/Component/ClassLoader/UniversalClassLoader.php';

if (!defined('AWS_FILE_PREFIX')) {
    define('AWS_FILE_PREFIX', __DIR__);
}

$classLoader = new Symfony\Component\ClassLoader\UniversalClassLoader();
$classLoader->registerNamespaces(array(
    'Aws'      => AWS_FILE_PREFIX,
    'Guzzle'   => AWS_FILE_PREFIX,
    'Symfony'  => AWS_FILE_PREFIX,
    'Doctrine' => AWS_FILE_PREFIX,
    'Psr'      => AWS_FILE_PREFIX,
    'Monolog'  => AWS_FILE_PREFIX
));

$classLoader->register();

return $classLoader;
