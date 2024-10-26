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
interface ICollection {
	/**
	 * @return int
	 * @since 16.0.0
	 */
	public function getId(): int;

	/**
	 * @return string
	 * @since 16.0.0
	 */
	public function getName(): string;

	/**
	 * @param string $name
	 * @since 16.0.0
	 */
	public function setName(string $name): void;

	/**
	 * @return IResource[]
	 * @since 16.0.0
	 */
	public function getResources(): array;

	/**
	 * Adds a resource to a collection
	 *
	 * @param IResource $resource
	 * @throws ResourceException when the resource is already part of the collection
	 * @since 16.0.0
	 */
	public function addResource(IResource $resource): void;

	/**
	 * Removes a resource from a collection
	 *
	 * @param IResource $resource
	 * @since 16.0.0
	 */
	public function removeResource(IResource $resource): void;

	/**
	 * Can a user/guest access the collection
	 *
	 * @param IUser|null $user
	 * @return bool
	 * @since 16.0.0
	 */
	public function canAccess(?IUser $user): bool;
}
