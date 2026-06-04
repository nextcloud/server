<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\FullTextSearch\Service;

/**
 * Interface IProviderService
 *
 * @since 15.0.0
 *
 */
interface IProviderService {
	/**
	 * Check if the provider $providerId is already indexed.
	 *
	 * @since 15.0.0
	 *
	 * @param string $providerId
	 *
	 * @return bool
	 */
	public function isProviderIndexed(string $providerId);


	/**
	 * Add the Javascript API in the navigation page of an app.
	 *
	 * @since 15.0.0
	 */
	public function addJavascriptAPI();
}
