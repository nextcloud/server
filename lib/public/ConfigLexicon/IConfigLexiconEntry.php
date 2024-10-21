<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCP\ConfigLexicon;

use OCP\IAppConfig;
use OCP\UserPreferences\IUserPreferences;

/**
 * Model that represent config values within an app config lexicon.
 *
 * @see IConfigLexicon
 * @since 31.0.0
 */
interface IConfigLexiconEntry {
	/**
	 * returns the config key.
	 *
	 * @return string config key
	 * @since 31.0.0
	 */
	public function getKey(): string;

	/**
	 * returns the type of the config value.
	 *
	 * @return ValueType
	 * @see self::TYPE_STRING and others
	 * @since 31.0.0
	 */
	public function getValueType(): ValueType;

	/**
	 * returns the default value set for this config key.
	 * default value is returned as string or NULL if not set.
	 *
	 * @return string|null NULL if no default is set
	 * @since 31.0.0
	 */
	public function getDefault(): ?string;

	/**
	 * returns the description for config key, only available when process is initiated from occ.
	 * returns empty string if not set or if process is not initiated from occ.
	 *
	 * @return string
	 * @since 31.0.0
	 */
	public function getDefinition(): string;

	/**
	 * returns if config value is set as LAZY.
	 *
	 * @see IAppConfig for details on lazy config values
	 * @see IUserPreferences for details on lazy config values
	 * @return bool TRUE if config/preference value is lazy
	 * @since 31.0.0
	 */
	public function isLazy(): bool;

	/**
	 * returns if config value is set as SENSITIVE.
	 *
	 * @see IAppConfig for details on sensitive config values
	 * @see IUserPreferences for details on sensitive config values
	 * @return bool TRUE if config/preference value is sensitive
	 * @since 31.0.0
	 */
	public function isSensitive(): bool;

	/**
	 * returns if config/preference key is deprecated.
	 *
	 * @return bool TRUE if config/preference si deprecated
	 * @since 31.0.0
	 */
	public function isDeprecated(): bool;
}
