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
interface IManager extends IProvider {
	/**
	 * @param int $id
	 * @return ICollection
	 * @throws CollectionException when the collection could not be found
	 * @since 16.0.0
	 */
	public function getCollection(int $id): ICollection;

	/**
	 * @param int $id
	 * @param IUser|null $user
	 * @return ICollection
	 * @throws CollectionException when the collection could not be found
	 * @since 16.0.0
	 */
	public function getCollectionForUser(int $id, ?IUser $user): ICollection;

	/**
	 * @param string $name
	 * @return ICollection
	 * @since 16.0.0
	 */
	public function newCollection(string $name): ICollection;

	/**
	 * Can a user/guest access the collection
	 *
	 * @param ICollection $collection
	 * @param IUser|null $user
	 * @return bool
	 * @since 16.0.0
	 */
	public function canAccessCollection(ICollection $collection, ?IUser $user): bool;

	/**
	 * @param IUser|null $user
	 * @since 16.0.0
	 */
	public function invalidateAccessCacheForUser(?IUser $user): void;

	/**
	 * @param IResource $resource
	 * @since 16.0.0
	 */
	public function invalidateAccessCacheForResource(IResource $resource): void;

	/**
	 * @param IResource $resource
	 * @param IUser|null $user
	 * @since 16.0.0
	 */
	public function invalidateAccessCacheForResourceByUser(IResource $resource, ?IUser $user): void;

	/**
	 * @param IProvider $provider
	 * @since 16.0.0
	 */
	public function invalidateAccessCacheForProvider(IProvider $provider): void;

	/**
	 * @param IProvider $provider
	 * @param IUser|null $user
	 * @since 16.0.0
	 */
	public function invalidateAccessCacheForProviderByUser(IProvider $provider, ?IUser $user): void;

	/**
	 * @param string $type
	 * @param string $id
	 * @return IResource
	 * @since 16.0.0
	 */
	public function createResource(string $type, string $id): IResource;

	/**
	 * @param string $type
	 * @param string $id
	 * @param IUser|null $user
	 * @return IResource
	 * @throws ResourceException
	 * @since 16.0.0
	 */
	public function getResourceForUser(string $type, string $id, ?IUser $user): IResource;

	/**
	 * @param string $provider
	 * @since 16.0.0
	 * @deprecated 18.0.0 Use IProviderManager::registerResourceProvider instead
	 */
	public function registerResourceProvider(string $provider): void;
}
