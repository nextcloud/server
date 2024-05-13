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

/**
 * This interface needs to be implemented if you want to define a config lexicon for your application
 * The config lexicon is used to avoid conflicts and problems when storing/retrieving config values
 *
 * @since 30.0.0
 */
interface IConfigLexicon {

	/**
	 * set your application config lexicon as strict or not.
	 * When set as strict, using a config key not set in the lexicon will throw an exception.
	 *
	 * @return bool
	 * @since 30.0.0
	 */
	public function isStrict(): bool;

	/**
	 * define the list of entries of your application config lexicon, related to AppConfig.
	 *
	 * @return IConfigLexiconEntry[]
	 * @since 30.0.0
	 */
	public function getAppConfigs(): array;

	/**
	 * define the list of entries of your application config lexicon, related to UserPreference.
	 *
	 * @return IConfigLexiconEntry[]
	 * @since 30.0.0
	 */
	public function getUserPreferences(): array;
}
