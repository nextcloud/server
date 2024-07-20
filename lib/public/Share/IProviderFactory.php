<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCP\Share;

use OC\Share20\Exception\ProviderException;

/**
 * Interface IProviderFactory
 *
 * @since 9.0.0
 */
interface IProviderFactory {
	/**
	 * @param string $id
	 * @return IShareProvider
	 * @throws ProviderException
	 * @since 9.0.0
	 */
	public function getProvider($id);

	/**
	 * @param int $shareType
	 * @return IShareProvider
	 * @throws ProviderException
	 * @since 9.0.0
	 */
	public function getProviderForType($shareType);

	/**
	 * @return IShareProvider[]
	 * @since 11.0.0
	 */
	public function getAllProviders();

	/**
	 * @since 21.0.0
	 * @param string $shareProvier
	 */
	public function registerProvider(string $shareProvier): void;
}
