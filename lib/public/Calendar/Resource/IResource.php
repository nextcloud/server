<?php
/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\Calendar\Resource;

/**
 * Interface IResource
 *
 * @since 14.0.0
 */
interface IResource {
	/**
	 * get the resource id
	 *
	 * This id has to be unique within the backend
	 *
	 * @return string
	 * @since 14.0.0
	 */
	public function getId():string;

	/**
	 * get the display name for a resource
	 *
	 * @return string
	 * @since 14.0.0
	 */
	public function getDisplayName():string;

	/**
	 * Get a list of groupIds that are allowed to access this resource
	 *
	 * If an empty array is returned, no group restrictions are
	 * applied.
	 *
	 * @return string[]
	 * @since 14.0.0
	 */
	public function getGroupRestrictions():array;

	/**
	 * get email-address for resource
	 *
	 * The email address has to be globally unique
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
