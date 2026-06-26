<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */


namespace OCP\Translation;

use RuntimeException;

/**
 * @since 26.0.0
 * @deprecated 30.0.0
 */
interface ITranslationProvider {
	/**
	 * @since 26.0.0
	 */
	public function getName(): string;

	/**
	 * @since 26.0.0
	 */
	public function getAvailableLanguages(): array;

	/**
	 * @since 26.0.0
	 * @throws RuntimeException If the text could not be translated
	 */
	public function translate(?string $fromLanguage, string $toLanguage, string $text): string;
}
