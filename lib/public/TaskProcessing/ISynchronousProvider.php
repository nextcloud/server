<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */


namespace OCP\TaskProcessing;

use OCP\Files\File;
use OCP\TaskProcessing\Exception\ProcessingException;

/**
 * This is the interface that is implemented by apps that
 * implement a task processing provider
 * @since 30.0.0
 */
interface ISynchronousProvider extends IProvider {

	/**
	 * Returns the shape of optional output parameters
	 *
	 * @param null|string $userId The user that created the current task
	 * @param array<string, list<numeric|string|File>|numeric|string|File> $input The task input
	 * @param callable(float):bool $reportProgress Report the task progress. If this returns false, that means the task was cancelled and processing should be stopped.
	 * @psalm-return array<string, list<numeric|string>|numeric|string>
	 * @throws ProcessingException
	 * @since 30.0.0
	 */
	public function process(?string $userId, array $input, callable $reportProgress): array;
}
