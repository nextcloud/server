<?php

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\Share;

use OCP\IUser;

/**
 * Interface IShareProviderSupportsAccept
 *
 * This interface allows to define IShareProvider that can list users for share with the getUsersForShare method,
 * which is available since Nextcloud 17.
 *
 * @since 33.0.0
 */
interface IShareProviderGetUsers extends IShareProvider {
	/**
	 * Get all users with access to a share
	 *
	 * @param IShare $share
	 * @return iterable<IUser>
	 * @since 33.0.0
	 */
	public function getUsersForShare(IShare $share): iterable;
}
