<?php

/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\Calendar\Room;

/**
 * Interface IRoom
 *
 * @since 14.0.0
 */
interface IRoom {
	/**
	 * Get a unique ID for the room
	 *
	 * This id has to be unique within the backend
	 *
	 * @return string
	 * @since 14.0.0
	 */
	public function getId():string;

	/**
	 * Get the display name for the room
	 *
	 * @return string
	 * @since 14.0.0
	 */
	public function getDisplayName():string;

	/**
	 * Get a list of groupIds that are allowed to access this room
	 *
	 * If an empty array is returned, no group restrictions are
	 * applied.
	 *
	 * @return string[]
	 * @since 14.0.0
	 */
	public function getGroupRestrictions():array;

	/**
	 * Get the email-address for the room
	 *
	 * The email-address has to be globally unique
	 *
	 * @return string
	 * @since 14.0.0
	 */
	public function getEMail():string;

	/**
	 * Get corresponding backend object
	 *
	 * @return IBackend
	 * @since 14.0.0
	 */
	public function getBackend():IBackend;
}
