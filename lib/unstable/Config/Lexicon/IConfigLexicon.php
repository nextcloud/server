<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace NCU\Config\Lexicon;

/**
 * This interface needs to be implemented if you want to define a config lexicon for your application
 * The config lexicon is used to avoid conflicts and problems when storing/retrieving config values
 *
 * @since 31.0.0
 * @experimental 31.0.0
 */
interface IConfigLexicon {

	/**
	 * Define the expected behavior when using config
	 * keys not set within your application config lexicon.
	 *
	 * @see ConfigLexiconStrictness
	 * @return ConfigLexiconStrictness
	 * @since 31.0.0
	 */
	public function getStrictness(): ConfigLexiconStrictness;

	/**
	 * define the list of entries of your application config lexicon, related to AppConfig.
	 *
	 * @return ConfigLexiconEntry[]
	 * @since 31.0.0
	 */
	public function getAppConfigs(): array;

	/**
	 * define the list of entries of your application config lexicon, related to UserPreferences.
	 *
	 * @return ConfigLexiconEntry[]
	 * @since 31.0.0
	 */
	public function getUserPreferences(): array;
}
