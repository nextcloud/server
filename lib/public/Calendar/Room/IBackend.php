<?php
/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\Calendar\Room;

use OCP\Calendar\BackendTemporarilyUnavailableException;

/**
 * Interface IBackend
 *
 * @since 14.0.0
 */
interface IBackend {
	/**
	 * get a list of all rooms in this backend
	 *
	 * @throws BackendTemporarilyUnavailableException
	 * @return IRoom[]
	 * @since 14.0.0
	 */
	public function getAllRooms():array;

	/**
	 * get a list of all room identifiers in this backend
	 *
	 * @throws BackendTemporarilyUnavailableException
	 * @return string[]
	 * @since 14.0.0
	 */
	public function listAllRooms():array;

	/**
	 * get a room by it's id
	 *
	 * @param string $id
	 * @throws BackendTemporarilyUnavailableException
	 * @return IRoom|null
	 * @since 14.0.0
	 */
	public function getRoom($id);

	/**
	 * Get unique identifier of the backend
	 *
	 * @return string
	 * @since 14.0.0
	 */
	public function getBackendIdentifier():string;
}
