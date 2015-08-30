<?php
/**
 * Copyright (c) 2015 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace Test\Connector\Sabre\RequestTest;

use Sabre\DAV\Exception;

class ExceptionPlugin extends \OCA\DAV\Connector\Sabre\ExceptionLoggerPlugin {
	/**
	 * @var \Exception[]
	 */
	protected $exceptions = [];

	public function logException(\Exception $ex) {
		$exceptionClass = get_class($ex);
		if (!isset($this->nonFatalExceptions[$exceptionClass])) {
			$this->exceptions[] = $ex;
		}
	}

	/**
	 * @return \Exception[]
	 */
	public function getExceptions() {
		return $this->exceptions;
	}
}
