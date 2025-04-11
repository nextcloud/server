<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\Collaboration\Reference;

/**
 * @since 26.0.0
 */
interface ISearchableReferenceProvider extends IDiscoverableReferenceProvider {
	/**
	 * @return string[] list of search provider IDs that can be used by the vue-richtext picker
	 * @since 26.0.0
	 */
	public function getSupportedSearchProviderIds(): array;
}
