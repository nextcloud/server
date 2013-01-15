<?php
set_time_limit(0); //scanning can take ages
session_write_close();

$user = OC_User::getUser();
$eventSource = new OC_EventSource();
$listener = new UpgradeListener($eventSource);
$legacy = new \OC\Files\Cache\Legacy($user);

if ($legacy->hasItems()) {
	OC_Hook::connect('\OC\Files\Cache\Upgrade', 'migrate_path', $listener, 'upgradePath');

	OC_DB::beginTransaction();
	$upgrade = new \OC\Files\Cache\Upgrade($legacy);
	$count = $legacy->getCount();
	$eventSource->send('total', $count);
	$upgrade->upgradePath('/' . $user . '/files');
	OC_DB::commit();
}
\OC\Files\Cache\Upgrade::upgradeDone($user);
$eventSource->send('done', true);
$eventSource->close();

class UpgradeListener {
	/**
	 * @var OC_EventSource $eventSource
	 */
	private $eventSource;

	private $count = 0;
	private $lastSend = 0;

	public function __construct($eventSource) {
		$this->eventSource = $eventSource;
	}

	public function upgradePath($path) {
		$this->count++;
		if ($this->count > ($this->lastSend + 5)) {
			$this->lastSend = $this->count;
			$this->eventSource->send('count', $this->count);
		}
	}
}
