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
interface IProvider {
	/**
	 * Get the resource type of the provider
	 *
	 * @return string
	 * @since 16.0.0
	 */
	public function getType(): string;

	/**
	 * Get the rich object data of a resource
	 *
	 * @param IResource $resource
	 * @return array
	 * @since 16.0.0
	 */
	public function getResourceRichObject(IResource $resource): array;

	/**
	 * Can a user/guest access the collection
	 *
	 * @param IResource $resource
	 * @param IUser|null $user
	 * @return bool
	 * @since 16.0.0
	 */
	public function canAccessResource(IResource $resource, ?IUser $user): bool;
}
