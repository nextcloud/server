<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
// use OCP namespace for all classes that are considered public.
// This means that they should be used by apps instead of the internal Nextcloud classes

namespace OCP;

/**
 * Interface for collections of items implemented by another share backend.
 * Extends the Share_Backend interface.
 * @since 5.0.0
 */
interface Share_Backend_Collection extends Share_Backend {
	/**
	 * Get the sources of the children of the item
	 * @param string $itemSource
	 * @return array Returns an array of children each inside an array with the keys: source, target, and file_path if applicable
	 * @since 5.0.0
	 */
	public function getChildren($itemSource);
}
