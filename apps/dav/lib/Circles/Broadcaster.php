<?php

namespace OCA\DAV\Circles;

use OCA\Circles\IBroadcaster;
use OCA\Circles\Model\SharingFrame;

class Broadcaster implements IBroadcaster {

	/**
	 * {@inheritdoc}
	 */
	public function init() {
//		$app = new Application();
//		$c = $app->getContainer();
//
//		$this->activityManager = $c->query('ActivityManager');
	}

	/**
	 * {@inheritdoc}
	 */
	public function createShareToUser($userId, SharingFrame $frame) {
		return true;
	}


	/**
	 * {@inheritdoc}
	 */
	public function createShareToCircle(SharingFrame $frame) {

		try {
			$data = $frame->getPayload();

			$userPrincipalBackend = new \OCA\DAV\Connector\Sabre\Principal(
				\OC::$server->getUserManager(),
				\OC::$server->getGroupManager()
			);

			$caldavBackend = new \OCA\DAV\CalDAV\CalDavBackend(\OC::$server->getDatabaseConnection(),
				$userPrincipalBackend, \OC::$server->getUserManager(), \OC::$server->getSecureRandom(), \OC::$server->getEventDispatcher());

			$calendar = new \OCA\DAV\CalDAV\Calendar($caldavBackend, $data['calendar'], \OC::$server->getL10N('calendar'));
			$caldavBackend->updateShares($calendar, [$data['add']], []);

			return true;
		} catch (\Exception $e) {
			return false;
		}
	}


	/**
	 * {@inheritdoc}
	 */
	public function deleteShareToCircle(SharingFrame $frame) {
		return true;
	}


	/**
	 * {@inheritdoc}
	 */
	public function editShareToCircle(SharingFrame $frame) {
		return true;
	}


	/**
	 * {@inheritdoc}
	 */
	public function deleteShareToUser($userId, SharingFrame $frame) {
		return true;
	}


	/**
	 * {@inheritdoc}
	 */
	public function editShareToUser($userId, SharingFrame $frame) {
		return true;
	}

}