<?php

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\Share;

/**
 * Interface IPartialShareProvider
 *
 * @since 33.0.0
 */
interface IPartialShareProvider extends IShareProvider {
	/**
	 * Get shares received by the given user and filtered by path.
	 *
	 * If $forChildren is true, results should only include children of $path
	 *
	 * @return iterable<IShare>
	 * @since 33.0.0
	 */
	public function getSharedWithByPath(
		string $userId,
		int $shareType,
		string $path,
		bool $forChildren,
		int $limit,
		int $offset,
	): iterable;
}
