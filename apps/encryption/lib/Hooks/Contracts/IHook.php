<?php

/**
 * SPDX-FileCopyrightText: 2019-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Encryption\Hooks\Contracts;

interface IHook {
	/**
	 * Connects Hooks
	 *
	 * @return null
	 */
	public function addHooks();
}
