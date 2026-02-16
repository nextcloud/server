<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH
 * SPDX-FileContributor: Carl Schwan
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\Share;

/**
 * Interface IShareProviderSupportsAccept
 *
 * This interface allows to define IShareProvider that can list users for share with the getUsersForShare method,
 * which is available since Nextcloud 17.
 *
 * @since 33.0.0
 */
interface ICreateShareProvider extends IShareProvider {
	/**
	 * Fill a share with additional information from the raw row of the database.
	 *
	 * @param array<string, mixed> $data
	 * @return IShare $share
	 * @since 34.0.0
	 */
	public function createShare(array $data): IShare;

	/**
	 * @return list<IShare::TYPE_*>
	 * @since 34.0.0
	 */
	public function getShareTypes(): array;

	/**
	 * @return list<IShare::TYPE_*>
	 * @since 34.0.0
	 */
	public function getTokenShareTypes(): array;
}
