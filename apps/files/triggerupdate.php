<?php

require_once __DIR__ . '/../../lib/base.php';

if (OC::$CLI) {
	if (count($argv) === 2) {
		$file = $argv[1];
		list(, $user) = explode('/', $file);
		OCP\JSON::checkUserExists($user);
		OC_Util::setupFS($user);
		$view = new \OC\Files\View('');
		/**
		 * @var \OC\Files\Storage\Storage $storage
		 */
		list($storage, $internalPath) = $view->resolvePath($file);
		$watcher = $storage->getWatcher($internalPath);
		$watcher->checkUpdate($internalPath);
	} else {
		echo "Usage: php triggerupdate.php /path/to/file\n";
	}
} else {
	echo "This script can be run from the command line only\n";
}
