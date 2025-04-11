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
		'sender' => '',
		'senderDisplayName' => '',
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
		$sharedSecret = '',
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

	/**
	 * set uid of the recipient
	 *
	 * @param string $user
	 *
	 * @since 14.0.0
	 */
	public function setShareWith($user) {
		$this->share['shareWith'] = $user;
	}

	/**
	 * set resource name (e.g. document.odt)
	 *
	 * @param string $name
	 *
	 * @since 14.0.0
	 */
	public function setResourceName($name) {
		$this->share['name'] = $name;
	}

	/**
	 * set resource type (e.g. file, calendar, contact,...)
	 *
	 * @param string $resourceType
	 *
	 * @since 14.0.0
	 */
	public function setResourceType($resourceType) {
		$this->share['resourceType'] = $resourceType;
	}

	/**
	 * set resource description (optional)
	 *
	 * @param string $description
	 *
	 * @since 14.0.0
	 */
	public function setDescription($description) {
		$this->share['description'] = $description;
	}

	/**
	 * set provider ID (e.g. file ID)
	 *
	 * @param string $providerId
	 *
	 * @since 14.0.0
	 */
	public function setProviderId($providerId) {
		$this->share['providerId'] = $providerId;
	}

	/**
	 * set owner UID
	 *
	 * @param string $owner
	 *
	 * @since 14.0.0
	 */
	public function setOwner($owner) {
		$this->share['owner'] = $owner;
	}

	/**
	 * set owner display name
	 *
	 * @param string $ownerDisplayName
	 *
	 * @since 14.0.0
	 */
	public function setOwnerDisplayName($ownerDisplayName) {
		$this->share['ownerDisplayName'] = $ownerDisplayName;
	}

	/**
	 * set UID of the user who sends the share
	 *
	 * @param string $sharedBy
	 *
	 * @since 14.0.0
	 */
	public function setSharedBy($sharedBy) {
		$this->share['sharedBy'] = $sharedBy;
		$this->share['sender'] = $sharedBy;
	}

	/**
	 * set display name of the user who sends the share
	 *
	 * @param $sharedByDisplayName
	 *
	 * @since 14.0.0
	 */
	public function setSharedByDisplayName($sharedByDisplayName) {
		$this->share['sharedByDisplayName'] = $sharedByDisplayName;
		$this->share['senderDisplayName'] = $sharedByDisplayName;
	}

	/**
	 * set protocol specification
	 *
	 * @param array $protocol
	 *
	 * @since 14.0.0
	 */
	public function setProtocol(array $protocol) {
		$this->share['protocol'] = $protocol;
	}

	/**
	 * share type (group or user)
	 *
	 * @param string $shareType
	 *
	 * @since 14.0.0
	 */
	public function setShareType($shareType) {
		if ($shareType === 'group' || $shareType === IShare::TYPE_REMOTE_GROUP) {
			$this->share['shareType'] = 'group';
		} else {
			$this->share['shareType'] = 'user';
		}
	}

	/**
	 * get the whole share, ready to send out
	 *
	 * @return array
	 *
	 * @since 14.0.0
	 */
	public function getShare() {
		return $this->share;
	}

	/**
	 * get uid of the recipient
	 *
	 * @return string
	 *
	 * @since 14.0.0
	 */
	public function getShareWith() {
		return $this->share['shareWith'];
	}

	/**
	 * get resource name (e.g. file, calendar, contact,...)
	 *
	 * @return string
	 *
	 * @since 14.0.0
	 */
	public function getResourceName() {
		return $this->share['name'];
	}

	/**
	 * get resource type (e.g. file, calendar, contact,...)
	 *
	 * @return string
	 *
	 * @since 14.0.0
	 */
	public function getResourceType() {
		return $this->share['resourceType'];
	}

	/**
	 * get resource description (optional)
	 *
	 * @return string
	 *
	 * @since 14.0.0
	 */
	public function getDescription() {
		return $this->share['description'];
	}

	/**
	 * get provider ID (e.g. file ID)
	 *
	 * @return string
	 *
	 * @since 14.0.0
	 */
	public function getProviderId() {
		return $this->share['providerId'];
	}

	/**
	 * get owner UID
	 *
	 * @return string
	 *
	 * @since 14.0.0
	 */
	public function getOwner() {
		return $this->share['owner'];
	}

	/**
	 * get owner display name
	 *
	 * @return string
	 *
	 * @since 14.0.0
	 */
	public function getOwnerDisplayName() {
		return $this->share['ownerDisplayName'];
	}

	/**
	 * get UID of the user who sends the share
	 *
	 * @return string
	 *
	 * @since 14.0.0
	 */
	public function getSharedBy() {
		return $this->share['sharedBy'];
	}

	/**
	 * get display name of the user who sends the share
	 *
	 * @return string
	 *
	 * @since 14.0.0
	 */
	public function getSharedByDisplayName() {
		return $this->share['sharedByDisplayName'];
	}

	/**
	 * get share type (group or user)
	 *
	 * @return string
	 *
	 * @since 14.0.0
	 */
	public function getShareType() {
		return $this->share['shareType'];
	}

	/**
	 * get share Secret
	 *
	 * @return string
	 *
	 * @since 14.0.0
	 */
	public function getShareSecret() {
		return $this->share['protocol']['options']['sharedSecret'];
	}

	/**
	 * get protocol specification
	 *
	 * @return array
	 *
	 * @since 14.0.0
	 */
	public function getProtocol() {
		return $this->share['protocol'];
	}
}
