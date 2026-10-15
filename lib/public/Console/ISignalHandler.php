<?php

declare(strict_types=1);

// SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace OCP\Console;

use OCP\AppFramework\Attribute\Consumable;
use OCP\Console\Exception\InterruptedException;

/**
 * Interface that lets a command cooperatively check whether it was
 * interrupted by the user (Ctrl-C/SIGTERM) and abort accordingly.
 *
 * ```
 * #[AsCommand(name: 'app:long-running-task')]
 * class LongRunningTaskCommand {
 *     public function __invoke(IOutput $output, ISignalHandler $signalHandler): ExitCode {
 *         try {
 *             foreach ($this->items() as $item) {
 *                 $signalHandler->abortIfInterrupted();
 *                 $this->process($item);
 *             }
 *         } catch (InterruptedException) {
 *             $output->writeln('Interrupted by user');
 *             return ExitCode::Failure;
 *         }
 *
 *         return ExitCode::Success;
 *     }
 * }
 * ```
 *
 * @since 35.0.0
 */
#[Consumable(since: '35.0.0')]
interface ISignalHandler {
	/**
	 * Throw when interrupted by user (Ctrl-C/SIGTERM)
	 *
	 * @throws InterruptedException
	 * @since 35.0.0
	 */
	public function abortIfInterrupted(): void;
}
