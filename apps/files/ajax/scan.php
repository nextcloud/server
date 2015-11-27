<?php
/**
 * @author Bart Visscher <bartv@thisnet.nl>
 * @author Jörn Friedrich Dreyer <jfd@butonic.de>
 * @author Lukas Reschke <lukas@owncloud.com>
 * @author Robin Appelman <icewind@owncloud.com>
 * @author Vincent Petry <pvince81@owncloud.com>
 *
 * @copyright Copyright (c) 2015, ownCloud, Inc.
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */
set_time_limit(0); //scanning can take ages

\OCP\JSON::checkLoggedIn();
\OCP\JSON::callCheck();

\OC::$server->getSession()->close();

$force = (isset($_GET['force']) and ($_GET['force'] === 'true'));
$dir = isset($_GET['dir']) ? (string)$_GET['dir'] : '';
if (isset($_GET['users'])) {
	\OCP\JSON::checkAdminUser();
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
	$scanner = new \OC\Files\Utils\Scanner($user, \OC::$server->getDatabaseConnection(), \OC::$server->getLogger());
	$scanner->listen('\OC\Files\Utils\Scanner', 'scanFile', array($listener, 'file'));
	try {
		if ($force) {
			$scanner->scan($dir);
		} else {
			$scanner->backgroundScan($dir);
		}
	} catch (\Exception $e) {
		$eventSource->send('error', get_class($e) . ': ' . $e->getMessage());
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
