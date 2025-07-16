<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Config\Lexicon;

use OCP\Config\Lexicon\Entry;
use OCP\Config\Lexicon\ILexicon;
use OCP\Config\Lexicon\Strictness;
use OCP\Config\ValueType;

/**
 * ConfigLexicon for 'core' app/user configs
 */
class CoreConfigLexicon implements ILexicon {
	public function getStrictness(): Strictness {
		return Strictness::IGNORE;
	}

	/**
	 * @inheritDoc
	 * @return Entry[]
	 */
	public function getAppConfigs(): array {
		return [
			new Entry('lastcron', ValueType::INT, 0, 'timestamp of last cron execution'),
		];
	}

	/**
	 * @inheritDoc
	 * @return Entry[]
	 */
	public function getUserConfigs(): array {
		return [
			new Entry('lang', ValueType::STRING, null, 'language'),
		];
	}
}
