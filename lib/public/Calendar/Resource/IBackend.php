<?php
/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\Calendar\Resource;

use OCP\Calendar\BackendTemporarilyUnavailableException;

/**
 * Interface IBackend
 *
 * @since 14.0.0
 */
interface IBackend {
	/**
	 * get a list of all resources in this backend
	 *
	 * @throws BackendTemporarilyUnavailableException
	 * @return IResource[]
	 * @since 14.0.0
	 */
	public function getAllResources():array;

	/**
	 * get a list of all resource identifiers in this backend
	 *
	 * @throws BackendTemporarilyUnavailableException
	 * @return string[]
	 * @since 14.0.0
	 */
	public function listAllResources():array;

	/**
	 * get a resource by it's id
	 *
	 * @param string $id
	 * @throws BackendTemporarilyUnavailableException
	 * @return IResource|null
	 * @since 14.0.0
	 */
	public function getResource($id);

	/**
	 * Get unique identifier of the backend
	 *
	 * @return string
	 * @since 14.0.0
	 */
	public function getBackendIdentifier():string;
}
