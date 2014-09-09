<?php
set_time_limit(0); //scanning can take ages
\OC::$server->getSession()->close();

$force = (isset($_GET['force']) and ($_GET['force'] === 'true'));
$dir = isset($_GET['dir']) ? $_GET['dir'] : '';
if (isset($_GET['users'])) {
	OC_JSON::checkAdminUser();
	if ($_GET['users'] === 'all') {
		$users = OC_User::getUsers();
	} else {
		$users = json_decode($_GET['users']);
	}
} else {
	$users = array(OC_User::getUser());
}

$eventSource = \OC::$server->createEventSource();
$listener = new ScanListener($eventSource);

foreach ($users as $user) {
	$eventSource->send('user', $user);
	$scanner = new \OC\Files\Utils\Scanner($user, \OC::$server->getDatabaseConnection());
	$scanner->listen('\OC\Files\Utils\Scanner', 'scanFile', array($listener, 'file'));
	$scanner->listen('\OC\Files\Utils\Scanner', 'scanFolder', array($listener, 'folder'));
	if ($force) {
		$scanner->scan($dir);
	} else {
		$scanner->backgroundScan($dir);
	}
}

$eventSource->send('done', $listener->getCount());
$eventSource->close();

class ScanListener {

	private $fileCount = 0;
	private $lastCount = 0;

	/**
	 * @var \OCP\IEventSource event source to pass events to
	 */
	private $eventSource;

	/**
	 * @param \OCP\IEventSource $eventSource
	 */
	public function __construct($eventSource) {
		$this->eventSource = $eventSource;
	}

	/**
	 * @param string $path
	 */
	public function folder($path) {
		$this->eventSource->send('folder', $path);
	}

	public function file() {
		$this->fileCount++;
		if ($this->fileCount > $this->lastCount + 20) { //send a count update every 20 files
			$this->lastCount = $this->fileCount;
			$this->eventSource->send('count', $this->fileCount);
		}
	}

	public function getCount() {
		return $this->fileCount;
	}
}
