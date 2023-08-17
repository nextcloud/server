<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2023 Julius Härtl <jus@bitgrid.net>
 *
 * @author Julius Härtl <jus@bitgrid.net>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */


namespace OCP\Translation;

use InvalidArgumentException;
use OCP\PreConditionNotMetException;

/**
 * @since 26.0.0
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
