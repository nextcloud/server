<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Async\Model;

use OC\Async\ISessionInterface;
use OCP\Async\Enum\BlockStatus;

class SessionInterface implements ISessionInterface {
	public function __construct(
		/** @var BlockInterface[] */
		private array $interfaces,
	) {
	}

	public function getAll(): array {
		return $this->interfaces;
	}

	public function byToken(string $token): ?BlockInterface {
		foreach ($this->interfaces as $iface) {
			if ($iface->getToken() === $token) {
				return $iface;
			}
		}

		return null;
	}

	public function byId(string $id): ?BlockInterface {
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
	 * - if one ::BLOCKER, returns ::BLOCKER.
	 * - if all ::SUCCESS, returns ::SUCCESS, else ignored.
	 * - if all ::ERROR+::SUCCESS, returns ::ERROR, else ignored.
	 * - session status is the biggest value between all left process status (::STANDBY, ::RUNNING)
	 */
	public function getGlobalStatus(): BlockStatus {
		$current = -1;
		$groupedStatus = [];
		foreach($this->interfaces as $iface) {
			// returns ::PREP if one process is still ::PREP
			if ($iface->getStatus() === BlockStatus::PREP) {
				return BlockStatus::PREP;
			}
			// returns ::BLOCKER if one process is marked ::BLOCKER
			if ($iface->getStatus() === BlockStatus::BLOCKER) {
				return BlockStatus::BLOCKER;
			}
			// we keep trace if process is marked as ::ERROR or ::SUCCESS
			// if not, we keep the highest value
			if (in_array($iface->getStatus(), [BlockStatus::ERROR, BlockStatus::SUCCESS], true)) {
				$groupedStatus[$iface->getStatus()->value] = true;
			} else {
				$current = max($current, $iface->getStatus()->value);
			}
		}

		// in case the all interface were ::ERROR or ::SUCCESS, we check
		// if there was at least one ::ERROR. if none we return ::SUCCESS
		if ($current === -1) {
			return (array_key_exists(BlockStatus::ERROR->value, $groupedStatus)) ? BlockStatus::ERROR : BlockStatus::SUCCESS;
		}

		return BlockStatus::from($current);
	}
}
