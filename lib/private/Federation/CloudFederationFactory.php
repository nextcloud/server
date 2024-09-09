<?php
/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Federation;

use OCP\Federation\ICloudFederationFactory;

class CloudFederationFactory implements ICloudFederationFactory {
	public function getCloudFederationShare($shareWith, $name, $description, $providerId, $owner, $ownerDisplayName, $sharedBy, $sharedByDisplayName, $sharedSecret, $shareType, $resourceType) {
		return new CloudFederationShare($shareWith, $name, $description, $providerId, $owner, $ownerDisplayName, $sharedBy, $sharedByDisplayName, $shareType, $resourceType, $sharedSecret);
	}

	public function getCloudFederationNotification() {
		return new CloudFederationNotification();
	}
}
