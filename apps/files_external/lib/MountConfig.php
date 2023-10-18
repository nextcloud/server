<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Andreas Fischer <bantu@owncloud.com>
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
 * @author Bart Visscher <bartv@thisnet.nl>
 * @author Björn Schießle <bjoern@schiessle.org>
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Frank Karlitschek <frank@karlitschek.de>
 * @author Jesús Macias <jmacias@solidgear.es>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Juan Pablo Villafáñez <jvillafanez@solidgear.es>
 * @author Julius Härtl <jus@bitgrid.net>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Michael Gapczynski <GapczynskiM@gmail.com>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Philipp Kapfer <philipp.kapfer@gmx.at>
 * @author Robin Appelman <robin@icewind.nl>
 * @author Robin McCorkell <robin@mccorkell.me.uk>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 * @author Vincent Petry <vincent@nextcloud.com>
 *
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program. If not, see <http://www.gnu.org/licenses/>
 *
 */
namespace OCA\Files_External;

use OCA\Files_External\Config\IConfigHandler;
use OCA\Files_External\Config\UserContext;
use OCA\Files_External\Lib\Backend\Backend;
use OCA\Files_External\Service\BackendService;
use OCA\Files_External\Service\GlobalStoragesService;
use OCA\Files_External\Service\UserGlobalStoragesService;
use OCA\Files_External\Service\UserStoragesService;
use OCP\Files\StorageNotAvailableException;
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

	/** @var UserGlobalStoragesService */
	private $userGlobalStorageService;
	/** @var UserStoragesService */
	private $userStorageService;
	/** @var GlobalStoragesService */
	private $globalStorageService;

	public function __construct(
		UserGlobalStoragesService $userGlobalStorageService,
		UserStoragesService $userStorageService,
		GlobalStoragesService $globalStorageService
	) {
		$this->userGlobalStorageService = $userGlobalStorageService;
		$this->userStorageService = $userStorageService;
		$this->globalStorageService = $globalStorageService;
	}

	/**
	 * @param mixed $input
	 * @param string|null $userId
	 * @return mixed
	 * @throws \OCP\AppFramework\QueryException
	 * @since 16.0.0
	 */
	public static function substitutePlaceholdersInConfig($input, string $userId = null) {
		/** @var BackendService $backendService */
		$backendService = \OC::$server->get(BackendService::class);
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
	public static function getBackendStatus($class, $options, $isPersonal, $testOnly = true) {
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
				/** @var \OC\Files\Storage\Common $storage */
				$storage = new $class($options);

				try {
					$result = $storage->test($isPersonal, $testOnly);
					$storage->setAvailability($result);
					if ($result) {
						return StorageNotAvailableException::STATUS_SUCCESS;
					}
				} catch (\Exception $e) {
					$storage->setAvailability(false);
					throw $e;
				}
			} catch (\Exception $exception) {
				\OC::$server->get(LoggerInterface::class)->error($exception->getMessage(), ['exception' => $exception, 'app' => 'files_external']);
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
		$l = \OC::$server->getL10N('files_external');
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
	private static function getSingleDependencyMessage(\OCP\IL10N $l, string $module, string $backend): string {
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
		$iv = \OC::$server->getSecureRandom()->generate(16);
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
		$cipher->setKey(\OC::$server->getConfig()->getSystemValue('passwordsalt', null));
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
