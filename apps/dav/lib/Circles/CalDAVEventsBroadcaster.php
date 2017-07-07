<?php

namespace OCA\DAV\Circles;

use OCA\Circles\IBroadcaster;
use OCA\Circles\Model\SharingFrame;

class CalDAVEventsBroadcaster implements IBroadcaster {


	/**
	 * {@inheritdoc}
	 */
	public function init() {
	}

	/**
	 * {@inheritdoc}
	 */
	public function createShareToUser(SharingFrame $frame, $userId) {
		return true;
	}


	/**
	 * {@inheritdoc}
	 */
	public function createShareToCircle(SharingFrame $frame) {
		return true;
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
	public function deleteShareToUser(SharingFrame $frame, $userId) {
		return true;
	}


	/**
	 * {@inheritdoc}
	 */
	public function editShareToUser(SharingFrame $frame, $userId) {
		return true;
	}

}