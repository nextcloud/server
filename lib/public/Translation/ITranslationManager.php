<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */


namespace OCP\Translation;

use InvalidArgumentException;
use OCP\PreConditionNotMetException;

/**
 * @since 26.0.0
 * @deprecated 30.0.0
 */
interface ITranslationManager {
	/**
	 * @since 26.0.0
	 */
	public function hasProviders(): bool;

	/**
	 * @return ITranslationProvider[]
	 * @since 27.1.0
	 */
	public function getProviders(): array;

	/**
	 * @since 26.0.0
	 */
	public function canDetectLanguage(): bool;

	/**
	 * @since 26.0.0
	 * @return LanguageTuple[]
	 */
	public function getLanguages(): array;

	/**
	 * @since 26.0.0
	 * @throws PreConditionNotMetException If no provider was registered but this method was still called
	 * @throws InvalidArgumentException If no matching provider was found that can detect a language
	 * @throws CouldNotTranslateException If the translation failed for other reasons
	 */
	public function translate(string $text, ?string &$fromLanguage, string $toLanguage): string;
}
