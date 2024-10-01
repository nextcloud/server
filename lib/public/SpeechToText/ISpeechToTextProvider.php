<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */


namespace OCP\SpeechToText;

use OCP\Files\File;
use RuntimeException;

/**
 * @since 27.0.0
 * @deprecated 30.0.0
 */
interface ISpeechToTextProvider {
	/**
	 * @since 27.0.0
	 */
	public function getName(): string;

	/**
	 * @since 27.0.0
	 * @throws RuntimeException If the text could not be transcribed
	 */
	public function transcribeFile(File $file): string;
}
