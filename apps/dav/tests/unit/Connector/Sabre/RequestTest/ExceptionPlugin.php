<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\DAV\Tests\unit\Connector\Sabre\RequestTest;

class ExceptionPlugin extends \OCA\DAV\Connector\Sabre\ExceptionLoggerPlugin {
	/**
	 * @var \Throwable[]
	 */
	protected $exceptions = [];

	public function logException(\Throwable $ex): void {
		$exceptionClass = get_class($ex);
		if (!isset($this->nonFatalExceptions[$exceptionClass])) {
			$this->exceptions[] = $ex;
		}
	}

	/**
	 * @return \Throwable[]
	 */
	public function getExceptions() {
		return $this->exceptions;
	}
}
