<?php
/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\Federation;

/**
 * Interface ICloudFederationNotification
 *
 *
 * @since 14.0.0
 */
interface ICloudFederationNotification {
	/**
	 * add a message to the notification
	 *
	 * @param string $notificationType (e.g. SHARE_ACCEPTED)
	 * @param string $resourceType (e.g. file, calendar, contact,...)
	 * @param string $providerId id of the share
	 * @param array $notification payload of the notification
	 *
	 * @since 14.0.0
	 */
	public function setMessage($notificationType, $resourceType, $providerId, array $notification);

	/**
	 * get message, ready to send out
	 *
	 * @return array
	 *
	 * @since 14.0.0
	 */
	public function getMessage();
}
