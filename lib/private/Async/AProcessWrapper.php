<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Async;

use OC\Async\Enum\ProcessActivity;
use OC\Async\Model\Process;
use OC\Async\Model\SessionInterface;

abstract class AProcessWrapper {
	abstract public function session(array $metadata): void;
	abstract public function init(Process $process): void;
	abstract public function activity(ProcessActivity $activity, string $line = ''): void;
	abstract public function end(string $line = ''): void;

	private SessionInterface $sessionInterface;
	public function getSessionInterface(): SessionInterface {
		return $this->sessionInterface;
	}

	public function setSessionInterface(SessionInterface $iface): void {
		$this->sessionInterface = $iface;
	}
}
