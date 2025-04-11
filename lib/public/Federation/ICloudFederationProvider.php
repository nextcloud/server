<?php
/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\Federation;

use OCP\Federation\Exceptions\ActionNotSupportedException;
use OCP\Federation\Exceptions\AuthenticationFailedException;
use OCP\Federation\Exceptions\BadRequestException;
use OCP\Federation\Exceptions\ProviderCouldNotAddShareException;
use OCP\Share\Exceptions\ShareNotFound;

/**
 * Interface ICloudFederationProvider
 *
 * Enable apps to create their own cloud federation provider
 *
 * @since 14.0.0
 *
 */

interface ICloudFederationProvider {
	/**
	 * get the name of the share type, handled by this provider
	 *
	 * @return string
	 *
	 * @since 14.0.0
	 */
	public function getShareType();

	/**
	 * share received from another server
	 *
	 * @param ICloudFederationShare $share
	 * @return string provider specific unique ID of the share
	 *
	 * @throws ProviderCouldNotAddShareException
	 *
	 * @since 14.0.0
	 */
	public function shareReceived(ICloudFederationShare $share);

	/**
	 * notification received from another server
	 *
	 * @param string $notificationType (e.g SHARE_ACCEPTED)
	 * @param string $providerId share ID
	 * @param array $notification provider specific notification
	 * @return array<string> $data send back to sender
	 *
	 * @throws ShareNotFound
	 * @throws ActionNotSupportedException
	 * @throws BadRequestException
	 * @throws AuthenticationFailedException
	 *
	 * @since 14.0.0
	 */
	public function notificationReceived($notificationType, $providerId, array $notification);

	/**
	 * get the supported share types, e.g. "user", "group", etc.
	 *
	 * @return array
	 *
	 * @since 14.0.0
	 */
	public function getSupportedShareTypes();
}
