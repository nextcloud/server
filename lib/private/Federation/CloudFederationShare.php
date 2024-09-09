<?php
/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Federation;

use OCP\Federation\ICloudFederationShare;
use OCP\Share\IShare;

class CloudFederationShare implements ICloudFederationShare {
	private $share = [
		'shareWith' => '',
		'shareType' => '',
		'name' => '',
		'resourceType' => '',
		'description' => '',
		'providerId' => '',
		'owner' => '',
		'ownerDisplayName' => '',
		'sharedBy' => '',
		'sharedByDisplayName' => '',
		'protocol' => []
	];

	/**
	 * get a CloudFederationShare Object to prepare a share you want to send
	 *
	 * @param string $shareWith
	 * @param string $name resource name (e.g. document.odt)
	 * @param string $description share description (optional)
	 * @param string $providerId resource UID on the provider side
	 * @param string $owner provider specific UID of the user who owns the resource
	 * @param string $ownerDisplayName display name of the user who shared the item
	 * @param string $sharedBy provider specific UID of the user who shared the resource
	 * @param string $sharedByDisplayName display name of the user who shared the resource
	 * @param string $shareType ('group' or 'user' share)
	 * @param string $resourceType ('file', 'calendar',...)
	 * @param string $sharedSecret
	 */
	public function __construct($shareWith = '',
		$name = '',
		$description = '',
		$providerId = '',
		$owner = '',
		$ownerDisplayName = '',
		$sharedBy = '',
		$sharedByDisplayName = '',
		$shareType = '',
		$resourceType = '',
		$sharedSecret = ''
	) {
		$this->setShareWith($shareWith);
		$this->setResourceName($name);
		$this->setDescription($description);
		$this->setProviderId($providerId);
		$this->setOwner($owner);
		$this->setOwnerDisplayName($ownerDisplayName);
		$this->setSharedBy($sharedBy);
		$this->setSharedByDisplayName($sharedByDisplayName);
		$this->setProtocol([
			'name' => 'webdav',
			'options' => [
				'sharedSecret' => $sharedSecret,
				'permissions' => '{http://open-cloud-mesh.org/ns}share-permissions'
			]
		]);
		$this->setShareType($shareType);
		$this->setResourceType($resourceType);
	}

	public function setShareWith($user) {
		$this->share['shareWith'] = $user;
	}

	public function setResourceName($name) {
		$this->share['name'] = $name;
	}

	public function setResourceType($resourceType) {
		$this->share['resourceType'] = $resourceType;
	}

	public function setDescription($description) {
		$this->share['description'] = $description;
	}

	public function setProviderId($providerId) {
		$this->share['providerId'] = $providerId;
	}

	public function setOwner($owner) {
		$this->share['owner'] = $owner;
	}

	public function setOwnerDisplayName($ownerDisplayName) {
		$this->share['ownerDisplayName'] = $ownerDisplayName;
	}

	public function setSharedBy($sharedBy) {
		$this->share['sharedBy'] = $sharedBy;
	}

	public function setSharedByDisplayName($sharedByDisplayName) {
		$this->share['sharedByDisplayName'] = $sharedByDisplayName;
	}

	public function setProtocol(array $protocol) {
		$this->share['protocol'] = $protocol;
	}

	public function setShareType($shareType) {
		if ($shareType === 'group' || $shareType === IShare::TYPE_REMOTE_GROUP) {
			$this->share['shareType'] = 'group';
		} else {
			$this->share['shareType'] = 'user';
		}
	}

	public function getShare() {
		return $this->share;
	}

	public function getShareWith() {
		return $this->share['shareWith'];
	}

	public function getResourceName() {
		return $this->share['name'];
	}

	public function getResourceType() {
		return $this->share['resourceType'];
	}

	public function getDescription() {
		return $this->share['description'];
	}

	public function getProviderId() {
		return $this->share['providerId'];
	}

	public function getOwner() {
		return $this->share['owner'];
	}

	public function getOwnerDisplayName() {
		return $this->share['ownerDisplayName'];
	}

	public function getSharedBy() {
		return $this->share['sharedBy'];
	}

	public function getSharedByDisplayName() {
		return $this->share['sharedByDisplayName'];
	}

	public function getShareType() {
		return $this->share['shareType'];
	}

	public function getShareSecret() {
		return $this->share['protocol']['options']['sharedSecret'];
	}

	public function getProtocol() {
		return $this->share['protocol'];
	}
}
