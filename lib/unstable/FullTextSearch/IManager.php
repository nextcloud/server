<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace NCU\FullTextSearch;

use NCU\FullTextSearch\Exceptions\ServiceNotFoundException;
use OC\AppFramework\Bootstrap\ServiceRegistration;

interface IManager {
	/**
	 * returns FullTextSearch API
	 *
	 * @throws ServiceNotFoundException if no full text search service found
	 * @since 33.0.0
	 */
	public function getService(): IService;

	/**
	 * returns list of registered FullTextSearch config provider
	 *
	 * @return ServiceRegistration<IContentProvider>[]
	 * @since 33.0.0
	 */
	public function getContentProviders(): array;
}
