<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Core\AppInfo;

use OCP\Config\Lexicon\Entry;
use OCP\Config\Lexicon\ILexicon;
use OCP\Config\Lexicon\Strictness;
use OCP\Config\ValueType;

/**
 * Config Lexicon for core.
 *
 * Please Add & Manage your Config Keys in that file and keep the Lexicon up to date!
 */
class ConfigLexicon implements ILexicon {
	public const SHAREAPI_ALLOW_FEDERATION_ON_PUBLIC_SHARES = 'shareapi_allow_federation_on_public_shares';

	public function getStrictness(): Strictness {
		return Strictness::IGNORE;
	}

	public function getAppConfigs(): array {
		return [
			new Entry(
				key: self::SHAREAPI_ALLOW_FEDERATION_ON_PUBLIC_SHARES,
				type: ValueType::BOOL,
				defaultRaw: true,
				definition: 'adds share permission to public shares to allow adding them to your Nextcloud (federation)',
				lazy: true,
			),
		];
	}

	public function getUserConfigs(): array {
		return [];
	}
}
