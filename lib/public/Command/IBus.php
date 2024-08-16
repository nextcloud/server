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
 * @since 8.1.0
 */
interface IBus {
	/**
	 * Schedule a command to be fired
	 *
	 * @param \OCP\Command\ICommand | callable $command
	 * @since 8.1.0
	 */
	public function push($command);

	/**
	 * Require all commands using a trait to be run synchronous
	 *
	 * @param string $trait
	 * @since 8.1.0
	 */
	public function requireSync($trait);
}
