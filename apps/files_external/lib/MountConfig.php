<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCA\Files_External;

use OCA\Files_External\Lib\Backend\Backend;
use OCA\Files_External\Service\BackendService;
use OCA\Files_External\Service\EncryptionService;
use OCP\Server;
use Psr\Container\ContainerExceptionInterface;

/**
 * Class to configure mount.json globally and for users
 */
class MountConfig {
	public const MOUNT_TYPE_GLOBAL = 'global';
	public const MOUNT_TYPE_GROUP = 'group';
	public const MOUNT_TYPE_USER = 'user';
	public const MOUNT_TYPE_PERSONAL = 'personal';

	/**
	 * @param mixed $input
	 * @param string|null $userId
	 * @return mixed
	 * @throws ContainerExceptionInterface
	 * @since 16.0.0
	 * @deprecated 34.0.0 use BackendService instead
	 */
	public static function substitutePlaceholdersInConfig($input, ?string $userId = null) {
		return Server::get(BackendService::class)->applyConfigHandlers($input, $userId);
	}

	/**
	 * Test connecting using the given backend configuration
	 *
	 * @param string $class backend class name
	 * @param array $options backend configuration options
	 * @param boolean $isPersonal
	 * @return int see self::STATUS_*
	 * @throws \Exception
	 * @deprecated 34.0.0 use BackendService instead
	 */
	public static function getBackendStatus($class, $options) {
		return Server::get(BackendService::class)->getBackendStatus($class, $options);
	}

	/**
	 * Encrypt passwords in the given config options
	 *
	 * @param array $options mount options
	 * @return array updated options
	 * @deprecated 34.0.0 use EncryptionService instead
	 */
	public static function encryptPasswords($options) {
		return Server::get(EncryptionService::class)->encryptPasswords($options);
	}

	/**
	 * Decrypt passwords in the given config options
	 *
	 * @param array $options mount options
	 * @return array updated options
	 * @deprecated 34.0.0 use EncryptionService instead
	 */
	public static function decryptPasswords($options) {
		return Server::get(EncryptionService::class)->decryptPasswords($options);
	}

	/**
	 * Computes a hash based on the given configuration.
	 * This is mostly used to find out whether configurations
	 * are the same.
	 * @throws \JsonException
	 */
	public static function makeConfigHash(array $config): string {
		$data = json_encode(
			[
				'c' => $config['backend'],
				'a' => $config['authMechanism'],
				'm' => $config['mountpoint'],
				'o' => $config['options'],
				'p' => $config['priority'] ?? -1,
				'mo' => $config['mountOptions'] ?? [],
			],
			JSON_THROW_ON_ERROR
		);
		return hash('md5', $data);
	}
}
