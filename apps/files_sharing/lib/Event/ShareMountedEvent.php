<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Files_Sharing\Event;

use OCA\Files_Sharing\SharedMount;
use OCP\EventDispatcher\Event;
use OCP\Files\Mount\IMountPoint;

class ShareMountedEvent extends Event {
	/** @var IMountPoint[] */
	private $additionalMounts = [];

	public function __construct(
		private SharedMount $mount,
	) {
		parent::__construct();
	}

	public function getMount(): SharedMount {
		return $this->mount;
	}

	public function addAdditionalMount(IMountPoint $mountPoint): void {
		$this->additionalMounts[] = $mountPoint;
	}

	/**
	 * @return IMountPoint[]
	 */
	public function getAdditionalMounts(): array {
		return $this->additionalMounts;
	}
}
