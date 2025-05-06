<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Config\Lexicon;

use NCU\Config\Lexicon\ConfigLexiconEntry;
use NCU\Config\Lexicon\ConfigLexiconStrictness;
use NCU\Config\Lexicon\IConfigLexicon;
use NCU\Config\ValueType;

/**
 * ConfigLexicon for 'core' app/user configs
 */
class CoreConfigLexicon implements IConfigLexicon {
	public const ASYNC_LOOPBACK_ADDRESS = 'loopback_address';
	public const ASYNC_LOOPBACK_PING = 'async_loopback_ping';
	public const ASYNC_LOOPBACK_TEST = 'async_loopback_test';

	public function getStrictness(): ConfigLexiconStrictness {
		return ConfigLexiconStrictness::IGNORE;
	}

	/**
	 * @inheritDoc
	 * @return ConfigLexiconEntry[]
	 */
	public function getAppConfigs(): array {
		return [
			new ConfigLexiconEntry('lastcron', ValueType::INT, 0, 'timestamp of last cron execution'),
			new ConfigLexiconEntry(self::ASYNC_LOOPBACK_ADDRESS, ValueType::STRING, '', 'local address of the instance to initiate async process via web request', true),
			new ConfigLexiconEntry(self::ASYNC_LOOPBACK_PING, ValueType::STRING, '', 'temporary random string used to confirm web-async loopback endpoint is valid', true),
			new ConfigLexiconEntry(self::ASYNC_LOOPBACK_TEST, ValueType::STRING, '', 'temporary random string used to confirm web-async is fully functional', true),
		];
	}

	/**
	 * @inheritDoc
	 * @return ConfigLexiconEntry[]
	 */
	public function getUserConfigs(): array {
		return [
			new ConfigLexiconEntry('lang', ValueType::STRING, null, 'language'),
		];
	}
}
