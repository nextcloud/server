<?php

/**
 * SPDX-FileCopyrightText: 2019-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Files_External\Lib;

use OC\Files\Filesystem;
use OCA\Files_External\Lib\Auth\AuthMechanism;
use OCA\Files_External\Lib\Auth\IUserProvided;
use OCA\Files_External\Lib\Backend\Backend;
use OCA\Files_External\ResponseDefinitions;

/**
 * External storage configuration
 *
 * @psalm-import-type Files_ExternalStorageConfig from ResponseDefinitions
 */
class StorageConfig implements \JsonSerializable {
	public const MOUNT_TYPE_ADMIN = 1;
	public const MOUNT_TYPE_PERSONAL = 2;
	/** @deprecated use MOUNT_TYPE_PERSONAL (full uppercase) instead */
	public const MOUNT_TYPE_PERSONAl = 2;

	/**
	 * Storage config id
	 *
	 * @var int
	 */
	private $id;

	/**
	 * Backend
	 *
	 * @var Backend
	 */
	private $backend;

	/**
	 * Authentication mechanism
	 *
	 * @var AuthMechanism
	 */
	private $authMechanism;

	/**
	 * Backend options
	 *
	 * @var array<string, mixed>
	 */
	private $backendOptions = [];

	/**
	 * Mount point path, relative to the user's "files" folder
	 *
	 * @var string
	 */
	private $mountPoint;

	/**
	 * Storage status
	 *
	 * @var int
	 */
	private $status;

	/**
	 * Status message
	 *
	 * @var string
	 */
	private $statusMessage;

	/**
	 * Priority
	 *
	 * @var int
	 */
	private $priority;

	/**
	 * List of users who have access to this storage
	 *
	 * @var list<string>
	 */
	private $applicableUsers = [];

	/**
	 * List of groups that have access to this storage
	 *
	 * @var list<string>
	 */
	private $applicableGroups = [];

	/**
	 * Mount-specific options
	 *
	 * @var array<string, mixed>
	 */
	private $mountOptions = [];

	/**
	 * Whether it's a personal or admin mount
	 *
	 * @var int
	 */
	private $type;

	/**
	 * Creates a storage config
	 *
	 * @param int|string $id config id or null for a new config
	 */
	public function __construct($id = null) {
		$this->id = $id ?? -1;
		$this->mountOptions['enable_sharing'] = false;
	}

	/**
	 * Returns the configuration id
	 *
	 * @return int
	 */
	public function getId() {
		return $this->id;
	}

	/**
	 * Sets the configuration id
	 *
	 * @param int $id configuration id
	 */
	public function setId(int $id): void {
		$this->id = $id;
	}

	/**
	 * Returns mount point path relative to the user's
	 * "files" folder.
	 *
	 * @return string path
	 */
	public function getMountPoint() {
		return $this->mountPoint;
	}

	/**
	 * Sets mount point path relative to the user's
	 * "files" folder.
	 * The path will be normalized.
	 *
	 * @param string $mountPoint path
	 */
	public function setMountPoint($mountPoint) {
		$this->mountPoint = Filesystem::normalizePath($mountPoint);
	}

	/**
	 * @return Backend
	 */
	public function getBackend() {
		return $this->backend;
	}

	/**
	 * @param Backend $backend
	 */
	public function setBackend(Backend $backend) {
		$this->backend = $backend;
	}

	/**
	 * @return AuthMechanism
	 */
	public function getAuthMechanism() {
		return $this->authMechanism;
	}

	/**
	 * @param AuthMechanism $authMechanism
	 */
	public function setAuthMechanism(AuthMechanism $authMechanism) {
		$this->authMechanism = $authMechanism;
	}

	/**
	 * Returns the external storage backend-specific options
	 *
	 * @return array backend options
	 */
	public function getBackendOptions() {
		return $this->backendOptions;
	}

	/**
	 * Sets the external storage backend-specific options
	 *
	 * @param array $backendOptions backend options
	 */
	public function setBackendOptions($backendOptions) {
		if ($this->getBackend() instanceof  Backend) {
			$parameters = $this->getBackend()->getParameters();
			foreach ($backendOptions as $key => $value) {
				if (isset($parameters[$key])) {
					switch ($parameters[$key]->getType()) {
						case DefinitionParameter::VALUE_BOOLEAN:
							$value = (bool)$value;
							break;
					}
					$backendOptions[$key] = $value;
				}
			}
		}

		$this->backendOptions = $backendOptions;
	}

	/**
	 * @param string $key
	 * @return mixed
	 */
	public function getBackendOption($key) {
		if (isset($this->backendOptions[$key])) {
			return $this->backendOptions[$key];
		}
		return null;
	}

	/**
	 * @param string $key
	 * @param mixed $value
	 */
	public function setBackendOption($key, $value) {
		$this->backendOptions[$key] = $value;
	}

	/**
	 * Returns the mount priority
	 *
	 * @return int priority
	 */
	public function getPriority() {
		return $this->priority;
	}

	/**
	 * Sets the mount priority
	 *
	 * @param int $priority priority
	 */
	public function setPriority($priority) {
		$this->priority = $priority;
	}

	/**
	 * Returns the users for which to mount this storage
	 *
	 * @return list<string> applicable users
	 */
	public function getApplicableUsers() {
		return $this->applicableUsers;
	}

	/**
	 * Sets the users for which to mount this storage
	 *
	 * @param list<string>|null $applicableUsers applicable users
	 */
	public function setApplicableUsers($applicableUsers) {
		if (is_null($applicableUsers)) {
			$applicableUsers = [];
		}
		$this->applicableUsers = $applicableUsers;
	}

	/**
	 * Returns the groups for which to mount this storage
	 *
	 * @return list<string> applicable groups
	 */
	public function getApplicableGroups() {
		return $this->applicableGroups;
	}

	/**
	 * Sets the groups for which to mount this storage
	 *
	 * @param list<string>|null $applicableGroups applicable groups
	 */
	public function setApplicableGroups($applicableGroups) {
		if (is_null($applicableGroups)) {
			$applicableGroups = [];
		}
		$this->applicableGroups = $applicableGroups;
	}

	/**
	 * Returns the mount-specific options
	 *
	 * @return array mount specific options
	 */
	public function getMountOptions() {
		return $this->mountOptions;
	}

	/**
	 * Sets the mount-specific options
	 *
	 * @param array $mountOptions applicable groups
	 */
	public function setMountOptions($mountOptions) {
		if (is_null($mountOptions)) {
			$mountOptions = [];
		}
		$this->mountOptions = $mountOptions;
	}

	/**
	 * @param string $key
	 * @return mixed
	 */
	public function getMountOption($key) {
		if (isset($this->mountOptions[$key])) {
			return $this->mountOptions[$key];
		}
		return null;
	}

	/**
	 * @param string $key
	 * @param mixed $value
	 */
	public function setMountOption($key, $value) {
		$this->mountOptions[$key] = $value;
	}

	/**
	 * Gets the storage status, whether the config worked last time
	 *
	 * @return int $status status
	 */
	public function getStatus() {
		return $this->status;
	}

	/**
	 * Gets the message describing the storage status
	 *
	 * @return string|null
	 */
	public function getStatusMessage() {
		return $this->statusMessage;
	}

	/**
	 * Sets the storage status, whether the config worked last time
	 *
	 * @param int $status status
	 * @param string|null $message optional message
	 */
	public function setStatus($status, $message = null) {
		$this->status = $status;
		$this->statusMessage = $message;
	}

	/**
	 * @return int self::MOUNT_TYPE_ADMIN or self::MOUNT_TYPE_PERSONAL
	 */
	public function getType() {
		return $this->type;
	}

	/**
	 * @param int $type self::MOUNT_TYPE_ADMIN or self::MOUNT_TYPE_PERSONAL
	 */
	public function setType($type) {
		$this->type = $type;
	}

	/**
	 * Serialize config to JSON
	 * @return Files_ExternalStorageConfig
	 */
	public function jsonSerialize(bool $obfuscate = false): array {
		$result = [];
		if (!is_null($this->id)) {
			$result['id'] = $this->id;
		}

		// obfuscate sensitive data if requested
		if ($obfuscate) {
			$this->formatStorageForUI();
		}

		$result['mountPoint'] = $this->mountPoint;
		$result['backend'] = $this->backend->getIdentifier();
		$result['authMechanism'] = $this->authMechanism->getIdentifier();
		$result['backendOptions'] = $this->backendOptions;
		if (!is_null($this->priority)) {
			$result['priority'] = $this->priority;
		}
		if (!empty($this->applicableUsers)) {
			$result['applicableUsers'] = $this->applicableUsers;
		}
		if (!empty($this->applicableGroups)) {
			$result['applicableGroups'] = $this->applicableGroups;
		}
		if (!empty($this->mountOptions)) {
			$result['mountOptions'] = $this->mountOptions;
		}
		if (!is_null($this->status)) {
			$result['status'] = $this->status;
		}
		if (!is_null($this->statusMessage)) {
			$result['statusMessage'] = $this->statusMessage;
		}
		$result['userProvided'] = $this->authMechanism instanceof IUserProvided;
		$result['type'] = ($this->getType() === self::MOUNT_TYPE_PERSONAL) ? 'personal': 'system';
		return $result;
	}

	protected function formatStorageForUI(): void {
		/** @var DefinitionParameter[] $parameters */
		$parameters = array_merge($this->getBackend()->getParameters(), $this->getAuthMechanism()->getParameters());

		$options = $this->getBackendOptions();
		foreach ($options as $key => $value) {
			foreach ($parameters as $parameter) {
				if ($parameter->getName() === $key && $parameter->getType() === DefinitionParameter::VALUE_PASSWORD) {
					$this->setBackendOption($key, DefinitionParameter::UNMODIFIED_PLACEHOLDER);
					break;
				}
			}
		}
	}
}
