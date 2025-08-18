<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Files_External;

use OC\Files\Storage\Common;
use OCA\Files_External\Config\IConfigHandler;
use OCA\Files_External\Config\UserContext;
use OCA\Files_External\Lib\Backend\Backend;
use OCA\Files_External\Service\BackendService;
use OCA\Files_External\Service\GlobalStoragesService;
use OCA\Files_External\Service\UserGlobalStoragesService;
use OCA\Files_External\Service\UserStoragesService;
use OCP\AppFramework\QueryException;
use OCP\Files\StorageNotAvailableException;
use OCP\IConfig;
use OCP\IL10N;
use OCP\Security\ISecureRandom;
use OCP\Server;
use OCP\Util;
use phpseclib\Crypt\AES;
use Psr\Log\LoggerInterface;

/**
 * Class to configure mount.json globally and for users
 */
class MountConfig {
	// TODO: make this class non-static and give it a proper namespace

	public const MOUNT_TYPE_GLOBAL = 'global';
	public const MOUNT_TYPE_GROUP = 'group';
	public const MOUNT_TYPE_USER = 'user';
	public const MOUNT_TYPE_PERSONAL = 'personal';

	// whether to skip backend test (for unit tests, as this static class is not mockable)
	public static $skipTest = false;

	public function __construct(
		private UserGlobalStoragesService $userGlobalStorageService,
		private UserStoragesService $userStorageService,
		private GlobalStoragesService $globalStorageService,
	) {
	}

	/**
	 * @param mixed $input
	 * @param string|null $userId
	 * @return mixed
	 * @throws QueryException
	 * @since 16.0.0
	 */
	public static function substitutePlaceholdersInConfig($input, ?string $userId = null) {
		/** @var BackendService $backendService */
		$backendService = Server::get(BackendService::class);
		/** @var IConfigHandler[] $handlers */
		$handlers = $backendService->getConfigHandlers();
		foreach ($handlers as $handler) {
			if ($handler instanceof UserContext && $userId !== null) {
				$handler->setUserId($userId);
			}
			$input = $handler->handle($input);
		}
		return $input;
	}

	/**
	 * Test connecting using the given backend configuration
	 *
	 * @param string $class backend class name
	 * @param array $options backend configuration options
	 * @param boolean $isPersonal
	 * @return int see self::STATUS_*
	 * @throws \Exception
	 */
	public static function getBackendStatus($class, $options) {
		if (self::$skipTest) {
			return StorageNotAvailableException::STATUS_SUCCESS;
		}
		foreach ($options as $key => &$option) {
			if ($key === 'password') {
				// no replacements in passwords
				continue;
			}
			$option = self::substitutePlaceholdersInConfig($option);
		}
		if (class_exists($class)) {
			try {
				/** @var Common $storage */
				$storage = new $class($options);

				try {
					$result = $storage->test();
					$storage->setAvailability($result);
					if ($result) {
						return StorageNotAvailableException::STATUS_SUCCESS;
					}
				} catch (\Exception $e) {
					$storage->setAvailability(false);
					throw $e;
				}
			} catch (\Exception $exception) {
				Server::get(LoggerInterface::class)->error($exception->getMessage(), ['exception' => $exception, 'app' => 'files_external']);
				throw $exception;
			}
		}
		return StorageNotAvailableException::STATUS_ERROR;
	}

	/**
	 * Get backend dependency message
	 * TODO: move into AppFramework along with templates
	 *
	 * @param Backend[] $backends
	 */
	public static function dependencyMessage(array $backends): string {
		$l = Util::getL10N('files_external');
		$message = '';
		$dependencyGroups = [];

		foreach ($backends as $backend) {
			foreach ($backend->checkDependencies() as $dependency) {
				$dependencyMessage = $dependency->getMessage();
				if ($dependencyMessage !== null) {
					$message .= '<p>' . $dependencyMessage . '</p>';
				} else {
					$dependencyGroups[$dependency->getDependency()][] = $backend;
				}
			}
		}

		foreach ($dependencyGroups as $module => $dependants) {
			$backends = implode(', ', array_map(function (Backend $backend): string {
				return '"' . $backend->getText() . '"';
			}, $dependants));
			$message .= '<p>' . MountConfig::getSingleDependencyMessage($l, $module, $backends) . '</p>';
		}

		return $message;
	}

	/**
	 * Returns a dependency missing message
	 */
	private static function getSingleDependencyMessage(IL10N $l, string $module, string $backend): string {
		switch (strtolower($module)) {
			case 'curl':
				return $l->t('The cURL support in PHP is not enabled or installed. Mounting of %s is not possible. Please ask your system administrator to install it.', [$backend]);
			case 'ftp':
				return $l->t('The FTP support in PHP is not enabled or installed. Mounting of %s is not possible. Please ask your system administrator to install it.', [$backend]);
			default:
				return $l->t('"%1$s" is not installed. Mounting of %2$s is not possible. Please ask your system administrator to install it.', [$module, $backend]);
		}
	}

	/**
	 * Encrypt passwords in the given config options
	 *
	 * @param array $options mount options
	 * @return array updated options
	 */
	public static function encryptPasswords($options) {
		if (isset($options['password'])) {
			$options['password_encrypted'] = self::encryptPassword($options['password']);
			// do not unset the password, we want to keep the keys order
			// on load... because that's how the UI currently works
			$options['password'] = '';
		}
		return $options;
	}

	/**
	 * Decrypt passwords in the given config options
	 *
	 * @param array $options mount options
	 * @return array updated options
	 */
	public static function decryptPasswords($options) {
		// note: legacy options might still have the unencrypted password in the "password" field
		if (isset($options['password_encrypted'])) {
			$options['password'] = self::decryptPassword($options['password_encrypted']);
			unset($options['password_encrypted']);
		}
		return $options;
	}

	/**
	 * Encrypt a single password
	 *
	 * @param string $password plain text password
	 * @return string encrypted password
	 */
	private static function encryptPassword($password) {
		$cipher = self::getCipher();
		$iv = Server::get(ISecureRandom::class)->generate(16);
		$cipher->setIV($iv);
		return base64_encode($iv . $cipher->encrypt($password));
	}

	/**
	 * Decrypts a single password
	 *
	 * @param string $encryptedPassword encrypted password
	 * @return string plain text password
	 */
	private static function decryptPassword($encryptedPassword) {
		$cipher = self::getCipher();
		$binaryPassword = base64_decode($encryptedPassword);
		$iv = substr($binaryPassword, 0, 16);
		$cipher->setIV($iv);
		$binaryPassword = substr($binaryPassword, 16);
		return $cipher->decrypt($binaryPassword);
	}

	/**
	 * Returns the encryption cipher
	 *
	 * @return AES
	 */
	private static function getCipher() {
		$cipher = new AES(AES::MODE_CBC);
		$cipher->setKey(Server::get(IConfig::class)->getSystemValue('passwordsalt', null));
		return $cipher;
	}

	/**
	 * Computes a hash based on the given configuration.
	 * This is mostly used to find out whether configurations
	 * are the same.
	 *
	 * @param array $config
	 * @return string
	 */
	public static function makeConfigHash($config) {
		$data = json_encode(
			[
				'c' => $config['backend'],
				'a' => $config['authMechanism'],
				'm' => $config['mountpoint'],
				'o' => $config['options'],
				'p' => $config['priority'] ?? -1,
				'mo' => $config['mountOptions'] ?? [],
			]
		);
		return hash('md5', $data);
	}
}
