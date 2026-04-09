<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCP\Command;

/**
 * Interface IBus
 *
 * @deprecated 33.0.0 The interface is considered internal going forward and should not be implemented by apps anymore
 * @since 8.1.0
 */
interface IBus {
	/**
	 * Schedule a command to be fired
	 *
	 * @param \OCP\Command\ICommand $command
	 * @since 33.0.0 Only allowed for {@see \OCA\Files_Trashbin\Command\Expire} and {@see \OCA\Files_Versions\Command\Expire}
	 * @since 8.1.0
	 */
	public function push(ICommand $command): void;

	/**
	 * Require all commands using a trait to be run synchronous
	 *
	 * @param string $trait
	 * @since 8.1.0
	 */
	public function requireSync(string $trait): void;
}
