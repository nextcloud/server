<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Files_External;

use OCP\Config\Lexicon\Entry;
use OCP\Config\Lexicon\ILexicon;
use OCP\Config\Lexicon\Strictness;
use OCP\Config\ValueType;

/**
 * Config Lexicon for files_sharing.
 *
 * Please Add & Manage your Config Keys in that file and keep the Lexicon up to date!
 *
 * {@see ILexicon}
 */
class ConfigLexicon implements ILexicon {
	public const ALLOW_USER_MOUNTING = 'allow_user_mounting';
	public const USER_MOUNTING_BACKENDS = 'user_mounting_backends';

	public function getStrictness(): Strictness {
		return Strictness::NOTICE;
	}

	public function getAppConfigs(): array {
		return [
			new Entry(self::ALLOW_USER_MOUNTING, ValueType::BOOL, false, 'allow users to mount their own external filesystems', true),
			new Entry(self::USER_MOUNTING_BACKENDS, ValueType::STRING, '', 'list of mounting backends available for users', true),
		];
	}

	public function getUserConfigs(): array {
		return [];
	}
}
