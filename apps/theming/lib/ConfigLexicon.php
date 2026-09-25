<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Theming;

use OCP\Config\Lexicon\Entry;
use OCP\Config\Lexicon\ILexicon;
use OCP\Config\Lexicon\Strictness;
use OCP\Config\ValueType;

/**
 * Config Lexicon for theming.
 *
 * Please Add & Manage your Config Keys in that file and keep the Lexicon up to date!
 *
 * {@see ILexicon}
 */
class ConfigLexicon implements ILexicon {
	/** The cache buster index */
	public const CACHE_BUSTER = 'cachebuster';
	public const USER_THEMING_DISABLED = 'disable-user-theming';

	/** Name of the software running on this instance (usually "Nextcloud") */
	public const PRODUCT_NAME = 'productName';
	/** Short name of this instance */
	public const INSTANCE_NAME = 'name';
	/** Slogan of this instance */
	public const INSTANCE_SLOGAN = 'slogan';
	/** Imprint URL of this instance */
	public const INSTANCE_IMPRINT_URL = 'imprintUrl';
	/** Privacy URL of this instance */
	public const INSTANCE_PRIVACY_URL = 'privacyUrl';

	// legacy theming
	/** Base URL of this instance */
	public const BASE_URL = 'url';
	/** Base URL for documentation */
	public const DOC_BASE_URL = 'docBaseUrl';

	public function getStrictness(): Strictness {
		return Strictness::NOTICE;
	}

	public function getAppConfigs(): array {
		return [
			// internals
			new Entry(
				self::CACHE_BUSTER,
				ValueType::INT,
				defaultRaw: 0,
				definition: 'The current cache buster key for theming assets.',
			),
			new Entry(
				self::USER_THEMING_DISABLED,
				ValueType::BOOL,
				defaultRaw: false,
				definition: 'Whether user theming is disabled.',
			),

			// instance theming
			new Entry(
				self::PRODUCT_NAME,
				ValueType::STRING,
				defaultRaw: 'Nextcloud',
				definition: 'The name of the software running on this instance (usually "Nextcloud").',
			),
			new Entry(
				self::INSTANCE_NAME,
				ValueType::STRING,
				defaultRaw: '',
				definition: 'Short name of this instance.',
			),
			new Entry(
				self::INSTANCE_SLOGAN,
				ValueType::STRING,
				defaultRaw: '',
				definition: 'Slogan of this instance.',
			),
			new Entry(
				self::INSTANCE_IMPRINT_URL,
				ValueType::STRING,
				defaultRaw: '',
				definition: 'Imprint URL of this instance.',
			),
			new Entry(
				self::INSTANCE_PRIVACY_URL,
				ValueType::STRING,
				defaultRaw: '',
				definition: 'Privacy URL of this instance.',
			),

			// legacy theming
			new Entry(
				self::BASE_URL,
				ValueType::STRING,
				defaultRaw: '',
				definition: 'Base URL of this instance.',
			),
			new Entry(
				self::DOC_BASE_URL,
				ValueType::STRING,
				defaultRaw: '',
				definition: 'Base URL for documentation.',
			),
		];
	}

	public function getUserConfigs(): array {
		return [];
	}
}
