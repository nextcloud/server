<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\Collaboration\Resources;

use OCP\IUser;

/**
 * @since 16.0.0
 */
interface IResource {
	/**
	 * @return string
	 * @since 16.0.0
	 */
	public function getType(): string;

	/**
	 * @return string
	 * @since 16.0.0
	 */
	public function getId(): string;

	/**
	 * @return array
	 * @since 16.0.0
	 */
	public function getRichObject(): array;

	/**
	 * Can a user/guest access the resource
	 *
	 * @param IUser|null $user
	 * @return bool
	 * @since 16.0.0
	 */
	public function canAccess(?IUser $user): bool;

	/**
	 * @return ICollection[]
	 * @since 16.0.0
	 */
	public function getCollections(): array;
}
