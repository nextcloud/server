<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */


namespace OCP\Translation;

/**
 * @since 26.0.0
 * @deprecated 30.0.0
 */
interface IDetectLanguageProvider {
	/**
	 * Try to detect the language of a given string
	 *
	 * @since 26.0.0
	 */
	public function detectLanguage(string $text): ?string;
}
