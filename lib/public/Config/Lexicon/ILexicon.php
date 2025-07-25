<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCP\Config\Lexicon;

use OCP\AppFramework\Attribute\Implementable;

/**
 * This interface needs to be implemented if you want to define a config lexicon for your application
 * The config lexicon is used to avoid conflicts and problems when storing/retrieving config values
 */
#[Implementable(since: '32.0.0')]
interface ILexicon {

	/**
	 * Define the expected behavior when using config
	 * keys not set within your application config lexicon.
	 *
	 * @return Strictness
	 * @since 32.0.0
	 *@see Strictness
	 */
	public function getStrictness(): Strictness;

	/**
	 * define the list of entries of your application config lexicon, related to AppConfig.
	 *
	 * @return Entry[]
	 * @since 32.0.0
	 */
	public function getAppConfigs(): array;

	/**
	 * define the list of entries of your application config lexicon, related to UserPreferences.
	 *
	 * @return Entry[]
	 * @since 32.0.0
	 */
	public function getUserConfigs(): array;
}
