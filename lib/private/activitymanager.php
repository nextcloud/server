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
use OCP\Activity\IManager;

class ActivityManager implements IManager {

	private $consumers = array();

	/**
	 * @param $app
	 * @param $subject
	 * @param $subjectParams
	 * @param $message
	 * @param $messageParams
	 * @param $file
	 * @param $link
	 * @param $affectedUser
	 * @param $type
	 * @param $priority
	 * @return mixed
	 */
	function publishActivity($app, $subject, $subjectParams, $message, $messageParams, $file, $link, $affectedUser, $type, $priority) {
		foreach($this->consumers as $consumer) {
			$c = $consumer();
			if ($c instanceof IConsumer) {
				try {
				$c->receive(
					$app,
					$subject,
					$subjectParams,
					$message,
					$messageParams,
					$file,
					$link,
					$affectedUser,
					$type,
					$priority);
				} catch (\Exception $ex) {
					// TODO: log the exception
				}
			}

		}
	}

	/**
	 * In order to improve lazy loading a closure can be registered which will be called in case
	 * activity consumers are actually requested
	 *
	 * $callable has to return an instance of OCA\Activity\IConsumer
	 *
	 * @param \Closure $callable
	 */
	function registerConsumer(\Closure $callable) {
		array_push($this->consumers, $callable);
	}

}
