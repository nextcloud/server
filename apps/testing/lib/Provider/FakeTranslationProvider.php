<?php
/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
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
