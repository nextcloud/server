<?php
/**
 * Copyright (c) 2013 Thomas Müller thomas.mueller@tmit.eu
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 *
 */
namespace OC;


use OCP\Activity\IConsumer;

class ActivityManager implements \OCP\Activity\IManager {

	private $consumers = array();

	/**
	 * @param $app
	 * @param $subject
	 * @param $message
	 * @param $file
	 * @param $link
	 * @return mixed
	 */
	function publishActivity($app, $subject, $message, $file, $link) {
		foreach($this->consumers as $consumer) {
			$c = $consumer();
			if ($c instanceof IConsumer) {
				$c->receive($app, $subject, $message, $file, $link);
			}

		}
	}

	/**
	 * In order to improve lazy loading a closure can be registered which will be called in case
	 * activity consumers are actually requested
	 *
	 * $callable has to return an instance of OCA\Activity\IConsumer
	 *
	 * @param string $key
	 * @param \Closure $callable
	 */
	function registerConsumer(\Closure $callable) {
		array_push($this->consumers, $callable);
	}
}
