<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\User\Backend;

/**
 * @since 14.0.0
 */
interface IGetHomeBackend {
	/**
	 * @since 14.0.0
	 *
	 * @param string $uid the username
	 * @return string|bool Datadir on success false on failure
	 */
	public function getHome(string $uid);
}
