<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\BackgroundJob;

/**
 * @since 27.0.0
 */
interface IParallelAwareJob {
	/**
	 * Set this to false to prevent two Jobs from the same class from running in parallel
	 *
	 * @param bool $allow
	 * @return void
	 * @since 27.0.0
	 */
	public function setAllowParallelRuns(bool $allow): void;

	/**
	 * @return bool
	 * @since 27.0.0
	 */
	public function getAllowParallelRuns(): bool;
}
