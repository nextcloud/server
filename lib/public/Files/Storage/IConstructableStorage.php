<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */
// use OCP namespace for all classes that are considered public.
// This means that they should be used by apps instead of the internal Nextcloud classes

namespace OCP\Files\Storage;

/**
 * Marks a storage as constructable. Allows to pass the storage as a string to a mounpoint and let it build the instance.
 *
 * @since 31.0.0
 */
interface IConstructableStorage {
	/**
	 * @param array $parameters is a free form array with the configuration options needed to construct the storage
	 *
	 * @since 31.0.0
	 */
	public function __construct(array $parameters);
}
