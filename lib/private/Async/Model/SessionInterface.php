<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Async\Model;

use OC\Async\Enum\ProcessStatus;
use OC\Async\ISessionInterface;

class SessionInterface implements ISessionInterface {
	public function __construct(
		/** @var ProcessInterface[] */
		private array $interfaces,
	) {
	}

	public function getAll(): array {
		return $this->interfaces;
	}

	public function byToken(string $token): ?ProcessInterface {
		foreach ($this->interfaces as $iface) {
			if ($iface->getToken() === $token) {
				return $iface;
			}
		}

		return null;
	}

	public function byId(string $id): ?ProcessInterface {
		foreach($this->interfaces as $iface) {
			if ($iface->getId() === $id) {
				return $iface;
			}
		}

		return null;
	}

	/**
	 * return a global status of the session based on the status of each process
	 * - if one entry is still at ::PREP stage, we return ::PREP
	 * - if all SUCCESS, session returns success,
	 * - if not all success, success is ignored,
	 * - session status is the biggest value between all process status
	 */
	public function getStatus(): ProcessStatus {
		$current = -1;
		foreach($this->interfaces as $iface) {
			if ($iface->getStatus() === ProcessStatus::PREP) {
				return ProcessStatus::PREP;
			}
			if ($iface->getStatus() !== ProcessStatus::SUCCESS) {
				$current = max($current, $iface->getStatus()->value);
			}
		}

		if ($current === -1) {
			return ProcessStatus::SUCCESS;
		}

		return ProcessStatus::from($current);
	}
}
