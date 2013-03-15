<?php
set_time_limit(0); //scanning can take ages
session_write_close();

$force = (isset($_GET['force']) and ($_GET['force'] === 'true'));
$dir = isset($_GET['dir']) ? $_GET['dir'] : '';

$eventSource = new OC_EventSource();
ScanListener::$eventSource = $eventSource;
ScanListener::$view = \OC\Files\Filesystem::getView();

OC_Hook::connect('\OC\Files\Cache\Scanner', 'scan_folder', 'ScanListener', 'folder');
OC_Hook::connect('\OC\Files\Cache\Scanner', 'scan_file', 'ScanListener', 'file');

$absolutePath = \OC\Files\Filesystem::getView()->getAbsolutePath($dir);

$mountPoints = \OC\Files\Filesystem::getMountPoints($absolutePath);
$mountPoints[] = \OC\Files\Filesystem::getMountPoint($absolutePath);
$mountPoints = array_reverse($mountPoints); //start with the mount point of $dir

foreach ($mountPoints as $mountPoint) {
	$storage = \OC\Files\Filesystem::getStorage($mountPoint);
	if ($storage) {
		ScanListener::$mountPoints[$storage->getId()] = $mountPoint;
		$scanner = $storage->getScanner();
		if ($force) {
			$scanner->scan('');
		} else {
			$scanner->backgroundScan();
		}
	}
}

$eventSource->send('done', ScanListener::$fileCount);
$eventSource->close();

class ScanListener {

	static public $fileCount = 0;
	static public $lastCount = 0;

	/**
	 * @var \OC\Files\View $view
	 */
	static public $view;

	/**
	 * @var array $mountPoints map storage ids to mountpoints
	 */
	static public $mountPoints = array();

	/**
	 * @var \OC_EventSource event source to pass events to
	 */
	static public $eventSource;

	static function folder($params) {
		$internalPath = $params['path'];
		$mountPoint = self::$mountPoints[$params['storage']];
		$path = self::$view->getRelativePath($mountPoint . $internalPath);
		self::$eventSource->send('folder', $path);
	}

	static function file() {
		self::$fileCount++;
		if (self::$fileCount > self::$lastCount + 20) { //send a count update every 20 files
			self::$lastCount = self::$fileCount;
			self::$eventSource->send('count', self::$fileCount);
		}
	}
}
