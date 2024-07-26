<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */


namespace OCP\Translation;

/**
 * @since 29.0.0
 * @deprecated 30.0.0
 */
interface ITranslationProviderWithId extends ITranslationProvider {
	/**
	 * @since 29.0.0
	 */
	public function getId(): string;
}
