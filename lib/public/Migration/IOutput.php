<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCP\Migration;

/**
 * Interface IOutput
 *
 * @since 9.1.0
 */
interface IOutput {
	/**
	 * @param string $message
	 * @return void
	 * @since 28.0.0
	 */
	public function debug(string $message): void;

	/**
	 * @param string $message
	 * @return void
	 * @since 9.1.0
	 */
	public function info($message);

	/**
	 * @param string $message
	 * @return void
	 * @since 9.1.0
	 */
	public function warning($message);

	/**
	 * @param int $max
	 * @return void
	 * @since 9.1.0
	 */
	public function startProgress($max = 0);

	/**
	 * @param int $step
	 * @param string $description
	 * @return void
	 * @since 9.1.0
	 */
	public function advance($step = 1, $description = '');

	/**
	 * @return void
	 * @since 9.1.0
	 */
	public function finishProgress();
}
