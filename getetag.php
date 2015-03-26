<?php
/**
 * Copyright (c) 2015 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */
error_reporting(E_ALL);

require_once __DIR__ . '/lib/autoloader.php';

$loader = new \OC\Autoloader();
$loader->disableGlobalClassPath();
spl_autoload_register(array($loader, 'load'));

$configDir = __DIR__ . '/config/';
OC_Config::$object = new \OC\Config($configDir);
$systemConfig = new \OC\SystemConfig();
$thirdPartyRoot = $systemConfig->getValue('3rdpartyroot', __DIR__);
require_once $thirdPartyRoot . '/3rdparty/autoload.php';

$factory = new \OC\DB\ConnectionFactory();
$type = $systemConfig->getValue('dbtype', 'sqlite');
if (!$factory->isValidType($type)) {
	throw new \OC\DatabaseException('Invalid database type');
}
$connectionParams = $factory->createConnectionParams($systemConfig);
$connection = $factory->getConnection($type, $connectionParams);
$id = $_GET['id'];
$query = $connection->prepare('SELECT `etag` FROM *PREFIX*filecache WHERE `fileid` = ?');
$query->execute([$id]);
$row = $query->fetch();
if ($row) {
	echo $row['etag'];
} else {
	header("HTTP/1.0 404 Not Found");
}
