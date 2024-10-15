<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCP\ConfigLexicon;

/**
 * This interface needs to be implemented if you want to define a config lexicon for your application
 * The config lexicon is used to avoid conflicts and problems when storing/retrieving config values
 *
 * @since 31.0.0
 */
interface IConfigLexicon {

	/**
	 * set your application config lexicon as strict or not.
	 * When set as strict, using a config key not set in the lexicon will throw an exception.
	 *
	 * @return ConfigLexiconStrictness
	 * @since 31.0.0
	 *
	 */
	public function getStrictness(): ConfigLexiconStrictness;

	/**
	 * define the list of entries of your application config lexicon, related to AppConfig.
	 *
	 * @return IConfigLexiconEntry[]
	 * @since 31.0.0
	 */
	public function getAppConfigs(): array;

	/**
	 * define the list of entries of your application config lexicon, related to UserPreferences.
	 *
	 * @return IConfigLexiconEntry[]
	 * @since 31.0.0
	 */
	public function getUserPreferences(): array;
}
