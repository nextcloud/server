<?php
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

namespace OCA\Testing\Provider;

use OCP\Translation\ITranslationProvider;
use OCP\Translation\LanguageTuple;

class FakeTranslationProvider implements ITranslationProvider {

	public function getName(): string {
		return 'Fake translation';
	}

	public function getAvailableLanguages(): array {
		return [
			new LanguageTuple('de', 'German', 'en', 'English'),
			new LanguageTuple('en', 'English', 'de', 'German'),
		];
	}

	public function translate(?string $fromLanguage, string $toLanguage, string $text): string {
		return strrev($text);
	}
}
