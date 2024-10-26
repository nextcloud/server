<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCP\Command;

/**
 * Interface ICommand
 *
 * @since 8.1.0
 */
interface ICommand {
	/**
	 * Run the command
	 * @since 8.1.0
	 */
	public function handle();
}
