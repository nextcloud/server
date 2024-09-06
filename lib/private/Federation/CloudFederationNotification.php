<?php
/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Federation;

use OCP\Federation\ICloudFederationNotification;

/**
 * Class CloudFederationNotification
 *
 * @package OC\Federation
 *
 * @since 14.0.0
 */
class CloudFederationNotification implements ICloudFederationNotification {
	private $message = [];

	public function setMessage($notificationType, $resourceType, $providerId, array $notification) {
		$this->message = [
			'notificationType' => $notificationType,
			'resourceType' => $resourceType,
			'providerId' => $providerId,
			'notification' => $notification,
		];
	}

	public function getMessage() {
		return $this->message;
	}
}
