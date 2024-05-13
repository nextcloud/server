<?php

declare(strict_types=1);
/**
 * @copyright Copyright (c) 2024 Maxence Lange <maxence@artificial-owl.com>
 *
 * @author Maxence Lange <maxence@artificial-owl.com>
 *
 * @license AGPL-3.0 or later
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program. If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OCP\ConfigLexicon;

use OCP\IAppConfig;

/**
 * Model that represent config values within an app config lexicon.
 *
 * @see IConfigLexicon
 * @since 30.0.0
 */
interface IConfigLexiconEntry {
	/**
	 * returns the config key.
	 *
	 * @return string config key
	 * @since 30.0.0
	 */
	public function getKey(): string;

	/**
	 * returns the type of the config value.
	 *
	 * @return ConfigLexiconValueType
	 * @see self::TYPE_STRING and others
	 * @since 30.0.0
	 */
	public function getValueType(): ConfigLexiconValueType;

	/**
	 * returns the default value set for this config key.
	 * default value is returned as string or NULL if not set.
	 *
	 * @return string|null NULL if no default is set
	 * @since 30.0.0
	 */
	public function getDefault(): ?string;

	/**
	 * returns the description for config key, only available when process is initiated from occ.
	 * returns empty string if not set or if process is not initiated from occ.
	 *
	 * @return string
	 * @since 30.0.0
	 */
	public function getDefinition(): string;

	/**
	 * returns if config value is set as LAZY.
	 *
	 * @see IAppConfig for details on lazy config values
	 * @return bool TRUE if config value is lazy
	 * @since 30.0.0
	 */
	public function isLazy(): bool;

	/**
	 * returns if config value is set as SENSITIVE.
	 *
	 * @see IAppConfig for details on sensitive config values
	 * @return bool TRUE if config value is sensitive
	 * @since 30.0.0
	 */
	public function isSensitive(): bool;

	/**
	 * returns if config key is deprecated.
	 *
	 * @return bool TRUE if config si deprecated
	 * @since 30.0.0
	 */
	public function isDeprecated(): bool;
}
