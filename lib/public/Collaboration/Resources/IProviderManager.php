<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\Collaboration\Resources;

/**
 * @since 18.0.0
 */
interface IProviderManager {
	/**
	 * @return IProvider[] list of resource providers
	 * @since 18.0.0
	 */
	public function getResourceProviders(): array;

	/**
	 * @param string $provider provider's class name
	 * @since 18.0.0
	 */
	public function registerResourceProvider(string $provider): void;
}
