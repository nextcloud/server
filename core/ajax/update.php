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
