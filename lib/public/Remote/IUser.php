<?php
/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\Remote;

/**
 * User info for a remote user
 *
 * @since 13.0.0
 * @deprecated 23.0.0
 */
interface IUser {
	/**
	 * @return string
	 *
	 * @since 13.0.0
	 * @deprecated 23.0.0
	 */
	public function getUserId();

	/**
	 * @return string
	 *
	 * @since 13.0.0
	 * @deprecated 23.0.0
	 */
	public function getEmail();

	/**
	 * @return string
	 *
	 * @since 13.0.0
	 * @deprecated 23.0.0
	 */
	public function getDisplayName();

	/**
	 * @return string
	 *
	 * @since 13.0.0
	 * @deprecated 23.0.0
	 */
	public function getPhone();

	/**
	 * @return string
	 *
	 * @since 13.0.0
	 * @deprecated 23.0.0
	 */
	public function getAddress();

	/**
	 * @return string
	 *
	 * @since 13.0.0
	 * @deprecated 23.0.0
	 */
	public function getWebsite();

	/**
	 * @return string
	 *
	 * @since 13.0.0
	 * @deprecated 23.0.0
	 */
	public function getTwitter();

	/**
	 * @return string[]
	 *
	 * @since 13.0.0
	 * @deprecated 23.0.0
	 */
	public function getGroups();

	/**
	 * @return string
	 *
	 * @since 13.0.0
	 * @deprecated 23.0.0
	 */
	public function getLanguage();

	/**
	 * @return int
	 *
	 * @since 13.0.0
	 * @deprecated 23.0.0
	 */
	public function getUsedSpace();

	/**
	 * @return int
	 *
	 * @since 13.0.0
	 * @deprecated 23.0.0
	 */
	public function getFreeSpace();

	/**
	 * @return int
	 *
	 * @since 13.0.0
	 * @deprecated 23.0.0
	 */
	public function getTotalSpace();

	/**
	 * @return int
	 *
	 * @since 13.0.0
	 * @deprecated 23.0.0
	 */
	public function getQuota();
}
