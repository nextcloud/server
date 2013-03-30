<?php
set_time_limit(0);
$RUNTIME_NOAPPS = true;
require_once '../../lib/base.php';

if (OC::checkUpgrade(false)) {
	\OC_DB::enableCaching(false);
	$updateEventSource = new OC_EventSource();
	$watcher = new UpdateWatcher($updateEventSource);
	OC_Hook::connect('update', 'success', $watcher, 'success');
	OC_Hook::connect('update', 'error', $watcher, 'error');
	OC_Hook::connect('update', 'error', $watcher, 'failure');
	$watcher->success('Turned on maintenance mode');
	try {
		$result = OC_DB::updateDbFromStructure(OC::$SERVERROOT.'/db_structure.xml');
		$watcher->success('Updated database');

		// do a file cache upgrade for users with files
		// this can take loooooooooooooooooooooooong
		__doFileCacheUpgrade($watcher);
	} catch (Exception $exception) {
		$watcher->failure($exception->getMessage());
	}
	OC_Config::setValue('version', implode('.', OC_Util::getVersion()));
	OC_App::checkAppsRequirements();
	// load all apps to also upgrade enabled apps
	OC_App::loadApps();
	OC_Config::setValue('maintenance', false);
	$watcher->success('Turned off maintenance mode');
	$watcher->done();
}

/**
 * The FileCache Upgrade routine
 *
 * @param UpdateWatcher $watcher
 */
function __doFileCacheUpgrade($watcher) {
	try {
		$query = \OC_DB::prepare('
			SELECT DISTINCT `user`
			FROM `*PREFIX*fscache`
		');
		$result = $query->execute();
	} catch (\Exception $e) {
		return;
	}
	$users = $result->fetchAll();
	if(count($users) == 0) {
		return;
	}
	$step = 100 / count($users);
	$percentCompleted = 0;
	$lastPercentCompletedOutput = 0;
	$startInfoShown = false;
	foreach($users as $userRow) {
		$user = $userRow['user'];
		\OC\Files\Filesystem::initMountPoints($user);
		\OC\Files\Cache\Upgrade::doSilentUpgrade($user);
		if(!$startInfoShown) {
			//We show it only now, because otherwise Info about upgraded apps
			//will appear between this and progress info
			$watcher->success('Updating filecache, this may take really long...');
			$startInfoShown = true;
		}
		$percentCompleted += $step;
		$out = floor($percentCompleted);
		if($out != $lastPercentCompletedOutput) {
			$watcher->success('... '. $out.'% done ...');
			$lastPercentCompletedOutput = $out;
		}
	}
	$watcher->success('Updated filecache');
}

class UpdateWatcher {
	/**
	 * @var \OC_EventSource $eventSource;
	 */
	private $eventSource;

	public function __construct($eventSource) {
		$this->eventSource = $eventSource;
	}

	public function success($message) {
		OC_Util::obEnd();
		$this->eventSource->send('success', $message);
		ob_start();
	}

	public function error($message) {
		OC_Util::obEnd();
		$this->eventSource->send('error', $message);
		ob_start();
	}

	public function failure($message) {
		OC_Util::obEnd();
		$this->eventSource->send('failure', $message);
		$this->eventSource->close();
		die();
	}

	public function done() {
		OC_Util::obEnd();
		$this->eventSource->send('done', '');
		$this->eventSource->close();
	}

}
