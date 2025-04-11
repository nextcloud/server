<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\TextToImage;

use RuntimeException;

/**
 * This is the interface that is implemented by apps that
 * implement a text to image provider
 * @since 28.0.0
 * @deprecated 30.0.0
 */
interface IProvider {
	/**
	 * An arbitrary unique text string identifying this provider
	 * @since 28.0.0
	 */
	public function getId(): string;

	/**
	 * The localized name of this provider
	 * @since 28.0.0
	 */
	public function getName(): string;

	/**
	 * Processes a text
	 *
	 * @param string $prompt The input text
	 * @param resource[] $resources The file resources to write the images to
	 * @return void
	 * @since 28.0.0
	 * @throws RuntimeException If the text could not be processed
	 */
	public function generate(string $prompt, array $resources): void;

	/**
	 * The expected runtime for one task with this provider in seconds
	 * @since 28.0.0
	 */
	public function getExpectedRuntime(): int;
}
