<?php

class CleanUpAvatarJob extends \OC\BackgroundJob\TimedJob {

	public function __construct () {
		$this->setInterval(7200); // 2 hours
	}

	public function run ($argument) {
		// TODO $view
		// TODO remove ALL the tmpavatars
	}
}
