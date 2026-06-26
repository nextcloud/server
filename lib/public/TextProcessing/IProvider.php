<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */


namespace OCP\TextProcessing;

use RuntimeException;

/**
 * This is the interface that is implemented by apps that
 * implement a text processing provider
 * @psalm-template-covariant  T of ITaskType
 * @since 27.1.0
 * @deprecated 30.0.0
 */
interface IProvider {
	/**
	 * The localized name of this provider
	 * @since 27.1.0
	 */
	public function getName(): string;

	/**
	 * Processes a text
	 *
	 * @param string $prompt The input text
	 * @return string the output text
	 * @since 27.1.0
	 * @throws RuntimeException If the text could not be processed
	 */
	public function process(string $prompt): string;

	/**
	 * Returns the task type class string of the task type, that this
	 * provider handles
	 *
	 * @since 27.1.0
	 * @return class-string<T>
	 */
	public function getTaskType(): string;
}
