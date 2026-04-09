<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-FileCopyrightText: 2014  Christian Kampka <christian@kampka.net>
 * SPDX-License-Identifier: MIT
 */
namespace OC\Log;

use OC\SystemConfig;
use OCP\Log\IWriter;

class Errorlog extends LogDetails implements IWriter {
	public function __construct(
		SystemConfig $config,
		protected string $tag = 'nextcloud',
	) {
		parent::__construct($config);
	}

	/**
	 * Write a message in the log
	 *
	 * @param string|array $message
	 */
	public function write(string $app, $message, int $level): void {
		error_log('[' . $this->tag . '][' . $app . '][' . $level . '] ' . $this->logDetailsAsJSON($app, $message, $level));
	}
}
