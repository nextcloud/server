<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\TaskProcessing;

use OCP\Files\File;
use OCP\TaskProcessing\Exception\ProcessingException;

/**
 * This is the interface that is implemented by apps that
 * implement a task processing provider
 * @since 35.0.0
 */
interface ISynchronousOptionsAwareProvider extends ISynchronousProvider {

	/**
	 * Returns the shape of optional output parameters
	 *
	 * @param null|string $userId The user that created the current task
	 * @param array<string, list<numeric|string|File>|numeric|string|File> $input The task input
	 * @param callable(float):bool $reportProgress Report the task progress. If this returns false, that means the task was cancelled and processing should be stopped.
	 * @param SynchronousProviderOptions $options The task options
	 * @psalm-return array<string, list<numeric|string>|numeric|string>
	 * @throws ProcessingException
	 * @since 35.0.0
	 */
	#[\Override]
	public function process(
		?string $userId,
		array $input,
		callable $reportProgress,
		SynchronousProviderOptions $options = new SynchronousProviderOptions(),
	): array;
}
