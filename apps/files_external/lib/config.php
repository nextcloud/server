<?php
/**
 * @author Bart Visscher <bartv@thisnet.nl>
 * @author Björn Schießle <schiessle@owncloud.com>
 * @author Frank Karlitschek <frank@owncloud.org>
 * @author Joas Schilling <nickvergessen@owncloud.com>
 * @author Jörn Friedrich Dreyer <jfd@butonic.de>
 * @author Lukas Reschke <lukas@owncloud.com>
 * @author Michael Gapczynski <GapczynskiM@gmail.com>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Philipp Kapfer <philipp.kapfer@gmx.at>
 * @author Robin Appelman <icewind@owncloud.com>
 * @author Robin McCorkell <rmccorkell@karoshi.org.uk>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 * @author Vincent Petry <pvince81@owncloud.com>
 *
 * @copyright Copyright (c) 2015, ownCloud, Inc.
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

use phpseclib\Crypt\AES;
use \OCP\AppFramework\IAppContainer;
use \OCA\Files_External\Lib\BackendConfig;
use \OCA\Files_External\Service\BackendService;

/**
 * Class to configure mount.json globally and for users
 */
class OC_Mount_Config {
	// TODO: make this class non-static and give it a proper namespace

	const MOUNT_TYPE_GLOBAL = 'global';
	const MOUNT_TYPE_GROUP = 'group';
	const MOUNT_TYPE_USER = 'user';
	const MOUNT_TYPE_PERSONAL = 'personal';

	// getBackendStatus return types
	const STATUS_SUCCESS = 0;
	const STATUS_ERROR = 1;

	// whether to skip backend test (for unit tests, as this static class is not mockable)
	public static $skipTest = false;

	/** @var IAppContainer */
	private static $appContainer;

	/**
	 * Teach OC_Mount_Config about the AppFramework
	 *
	 * @param IAppContainer $appContainer
	 */
	public static function initApp(IAppContainer $appContainer) {
		self::$appContainer = $appContainer;
	}

	/*
	 * Hook that mounts the given user's visible mount points
	 *
	 * @param array $data
	 */
	public static function initMountPointsHook($data) {
		self::addStorageIdToConfig(null);
		if ($data['user']) {
			self::addStorageIdToConfig($data['user']);
			$user = \OC::$server->getUserManager()->get($data['user']);
			if (!$user) {
				\OCP\Util::writeLog(
					'files_external',
					'Cannot init external mount points for non-existant user "' . $data['user'] . '".',
					\OCP\Util::WARN
				);
				return;
			}
			$userView = new \OC\Files\View('/' . $user->getUID() . '/files');
			$changePropagator = new \OC\Files\Cache\ChangePropagator($userView);
			$etagPropagator = new \OCA\Files_External\EtagPropagator($user, $changePropagator, \OC::$server->getConfig());
			$etagPropagator->propagateDirtyMountPoints();
			\OCP\Util::connectHook(
				\OC\Files\Filesystem::CLASSNAME,
				\OC\Files\Filesystem::signal_create_mount,
				$etagPropagator, 'updateHook');
			\OCP\Util::connectHook(
				\OC\Files\Filesystem::CLASSNAME,
				\OC\Files\Filesystem::signal_delete_mount,
				$etagPropagator, 'updateHook');
		}
	}

	/**
	 * Returns the mount points for the given user.
	 * The mount point is relative to the data directory.
	 * TODO: Move me into StoragesService
	 *
	 * @param string $user user
	 * @return array of mount point string as key, mountpoint config as value
	 */
	public static function getAbsoluteMountPoints($user) {
		$mountPoints = array();
		$backendService = self::$appContainer->query('OCA\Files_External\Service\BackendService');

		// Load system mount points
		$mountConfig = self::readData();

		// Global mount points (is this redundant?)
		if (isset($mountConfig[self::MOUNT_TYPE_GLOBAL])) {
			foreach ($mountConfig[self::MOUNT_TYPE_GLOBAL] as $mountPoint => $options) {
				$backend = $backendService->getBackend($options['class']);
				$options['personal'] = false;
				$options['options'] = self::decryptPasswords($options['options']);
				if (!isset($options['priority'])) {
					$options['priority'] = $backend->getPriority();
				}
				if (!isset($options['authMechanism'])) {
					$options['authMechanism'] = $backend->getLegacyAuthMechanism($options['options'])->getClass();
				}

				// Override if priority greater
				if ((!isset($mountPoints[$mountPoint]))
					|| ($options['priority'] >= $mountPoints[$mountPoint]['priority'])
				) {
					$options['priority_type'] = self::MOUNT_TYPE_GLOBAL;
					$options['backend'] = $backend->getText();
					$mountPoints[$mountPoint] = $options;
				}
			}
		}
		// All user mount points
		if (isset($mountConfig[self::MOUNT_TYPE_USER]) && isset($mountConfig[self::MOUNT_TYPE_USER]['all'])) {
			$mounts = $mountConfig[self::MOUNT_TYPE_USER]['all'];
			foreach ($mounts as $mountPoint => $options) {
				$mountPoint = self::setUserVars($user, $mountPoint);
				foreach ($options as &$option) {
					$option = self::setUserVars($user, $option);
				}
				$backend = $backendService->getBackend($options['class']);
				$options['personal'] = false;
				$options['options'] = self::decryptPasswords($options['options']);
				if (!isset($options['priority'])) {
					$options['priority'] = $backend->getPriority();
				}
				if (!isset($options['authMechanism'])) {
					$options['authMechanism'] = $backend->getLegacyAuthMechanism($options['options'])->getClass();
				}

				// Override if priority greater
				if ((!isset($mountPoints[$mountPoint]))
					|| ($options['priority'] >= $mountPoints[$mountPoint]['priority'])
				) {
					$options['priority_type'] = self::MOUNT_TYPE_GLOBAL;
					$options['backend'] = $backend->getText();
					$mountPoints[$mountPoint] = $options;
				}
			}
		}
		// Group mount points
		if (isset($mountConfig[self::MOUNT_TYPE_GROUP])) {
			foreach ($mountConfig[self::MOUNT_TYPE_GROUP] as $group => $mounts) {
				if (\OC_Group::inGroup($user, $group)) {
					foreach ($mounts as $mountPoint => $options) {
						$mountPoint = self::setUserVars($user, $mountPoint);
						foreach ($options as &$option) {
							$option = self::setUserVars($user, $option);
						}
						$backend = $backendService->getBackend($options['class']);
						$options['personal'] = false;
						$options['options'] = self::decryptPasswords($options['options']);
						if (!isset($options['priority'])) {
							$options['priority'] = $backend->getPriority();
						}
						if (!isset($options['authMechanism'])) {
							$options['authMechanism'] = $backend->getLegacyAuthMechanism($options['options'])->getClass();
						}

						// Override if priority greater or if priority type different
						if ((!isset($mountPoints[$mountPoint]))
							|| ($options['priority'] >= $mountPoints[$mountPoint]['priority'])
							|| ($mountPoints[$mountPoint]['priority_type'] !== self::MOUNT_TYPE_GROUP)
						) {
							$options['priority_type'] = self::MOUNT_TYPE_GROUP;
							$options['backend'] = $backend->getText();
							$mountPoints[$mountPoint] = $options;
						}
					}
				}
			}
		}
		// User mount points
		if (isset($mountConfig[self::MOUNT_TYPE_USER])) {
			foreach ($mountConfig[self::MOUNT_TYPE_USER] as $mountUser => $mounts) {
				if (strtolower($mountUser) === strtolower($user)) {
					foreach ($mounts as $mountPoint => $options) {
						$mountPoint = self::setUserVars($user, $mountPoint);
						foreach ($options as &$option) {
							$option = self::setUserVars($user, $option);
						}
						$backend = $backendService->getBackend($options['class']);
						$options['personal'] = false;
						$options['options'] = self::decryptPasswords($options['options']);
						if (!isset($options['priority'])) {
							$options['priority'] = $backend->getPriority();
						}
						if (!isset($options['authMechanism'])) {
							$options['authMechanism'] = $backend->getLegacyAuthMechanism($options['options'])->getClass();
						}

						// Override if priority greater or if priority type different
						if ((!isset($mountPoints[$mountPoint]))
							|| ($options['priority'] >= $mountPoints[$mountPoint]['priority'])
							|| ($mountPoints[$mountPoint]['priority_type'] !== self::MOUNT_TYPE_USER)
						) {
							$options['priority_type'] = self::MOUNT_TYPE_USER;
							$options['backend'] = $backend->getText();
							$mountPoints[$mountPoint] = $options;
						}
					}
				}
			}
		}

		// Load personal mount points
		$mountConfig = self::readData($user);
		if (isset($mountConfig[self::MOUNT_TYPE_USER][$user])) {
			foreach ($mountConfig[self::MOUNT_TYPE_USER][$user] as $mountPoint => $options) {
				$backend = $backendService->getBackend($options['class']);
				if ($backend->isVisibleFor(BackendService::VISIBILITY_PERSONAL)) {
					$options['personal'] = true;
					$options['options'] = self::decryptPasswords($options['options']);
					if (!isset($options['authMechanism'])) {
						$options['authMechanism'] = $backend->getLegacyAuthMechanism($options['options'])->getClass();
					}

					// Always override previous config
					$options['priority_type'] = self::MOUNT_TYPE_PERSONAL;
					$options['backend'] = $backend->getText();
					$mountPoints[$mountPoint] = $options;
				}
			}
		}

		return $mountPoints;
	}

	/**
	 * fill in the correct values for $user
	 *
	 * @param string $user user value
	 * @param string|array $input
	 * @return string
	 */
	private static function setUserVars($user, $input) {
		if (is_array($input)) {
			foreach ($input as &$value) {
				if (is_string($value)) {
					$value = str_replace('$user', $user, $value);
				}
			}
		} else {
			$input = str_replace('$user', $user, $input);
		}
		return $input;
	}

	/**
	 * Get the system mount points
	 * The returned array is not in the same format as getUserMountPoints()
	 *
	 * @return array
	 */
	public static function getSystemMountPoints() {
		$mountPoints = self::readData();
		$backendService = self::$appContainer->query('\OCA\Files_External\Service\BackendService');
		$system = array();
		if (isset($mountPoints[self::MOUNT_TYPE_GROUP])) {
			foreach ($mountPoints[self::MOUNT_TYPE_GROUP] as $group => $mounts) {
				foreach ($mounts as $mountPoint => $mount) {
					// Update old classes to new namespace
					if (strpos($mount['class'], 'OC_Filestorage_') !== false) {
						$mount['class'] = '\OC\Files\Storage\\' . substr($mount['class'], 15);
					}
					$backend = $backendService->getBackend($mount['class']);
					$mount['options'] = self::decryptPasswords($mount['options']);
					if (!isset($mount['priority'])) {
						$mount['priority'] = $backend->getPriority();
					}
					// Remove '/$user/files/' from mount point
					$mountPoint = substr($mountPoint, 13);

					$config = array(
						'class' => $mount['class'],
						'mountpoint' => $mountPoint,
						'backend' => $backend->getText(),
						'priority' => $mount['priority'],
						'options' => $mount['options'],
						'applicable' => array('groups' => array($group), 'users' => array())
					);
					if (isset($mount['id'])) {
						$config['id'] = (int)$mount['id'];
					}
					if (isset($mount['storage_id'])) {
						$config['storage_id'] = (int)$mount['storage_id'];
					}
					if (isset($mount['mountOptions'])) {
						$config['mountOptions'] = $mount['mountOptions'];
					}
					$hash = self::makeConfigHash($config);
					// If an existing config exists (with same class, mountpoint and options)
					if (isset($system[$hash])) {
						// add the groups into that config
						$system[$hash]['applicable']['groups']
							= array_merge($system[$hash]['applicable']['groups'], array($group));
					} else {
						$system[$hash] = $config;
					}
				}
			}
		}
		if (isset($mountPoints[self::MOUNT_TYPE_USER])) {
			foreach ($mountPoints[self::MOUNT_TYPE_USER] as $user => $mounts) {
				foreach ($mounts as $mountPoint => $mount) {
					// Update old classes to new namespace
					if (strpos($mount['class'], 'OC_Filestorage_') !== false) {
						$mount['class'] = '\OC\Files\Storage\\' . substr($mount['class'], 15);
					}
					$backend = $backendService->getBackend($mount['class']);
					$mount['options'] = self::decryptPasswords($mount['options']);
					if (!isset($mount['priority'])) {
						$mount['priority'] = $backend->getPriority();
					}
					// Remove '/$user/files/' from mount point
					$mountPoint = substr($mountPoint, 13);
					$config = array(
						'class' => $mount['class'],
						'mountpoint' => $mountPoint,
						'backend' => $backend->getText(),
						'priority' => $mount['priority'],
						'options' => $mount['options'],
						'applicable' => array('groups' => array(), 'users' => array($user))
					);
					if (isset($mount['id'])) {
						$config['id'] = (int)$mount['id'];
					}
					if (isset($mount['storage_id'])) {
						$config['storage_id'] = (int)$mount['storage_id'];
					}
					if (isset($mount['mountOptions'])) {
						$config['mountOptions'] = $mount['mountOptions'];
					}
					$hash = self::makeConfigHash($config);
					// If an existing config exists (with same class, mountpoint and options)
					if (isset($system[$hash])) {
						// add the users into that config
						$system[$hash]['applicable']['users']
							= array_merge($system[$hash]['applicable']['users'], array($user));
					} else {
						$system[$hash] = $config;
					}
				}
			}
		}
		return array_values($system);
	}

	/**
	 * Get the personal mount points of the current user
	 * The returned array is not in the same format as getUserMountPoints()
	 *
	 * @return array
	 */
	public static function getPersonalMountPoints() {
		$mountPoints = self::readData(OCP\User::getUser());
		$backendService = self::$appContainer->query('\OCA\Files_External\Service\BackendService');
		$uid = OCP\User::getUser();
		$personal = array();
		if (isset($mountPoints[self::MOUNT_TYPE_USER][$uid])) {
			foreach ($mountPoints[self::MOUNT_TYPE_USER][$uid] as $mountPoint => $mount) {
				// Update old classes to new namespace
				if (strpos($mount['class'], 'OC_Filestorage_') !== false) {
					$mount['class'] = '\OC\Files\Storage\\' . substr($mount['class'], 15);
				}
				$backend = $backendService->getBackend($mount['class']);
				$mount['options'] = self::decryptPasswords($mount['options']);
				$config = array(
					'class' => $mount['class'],
					// Remove '/uid/files/' from mount point
					'mountpoint' => substr($mountPoint, strlen($uid) + 8),
					'backend' => $backend->getText(),
					'options' => $mount['options']
				);
				if (isset($mount['id'])) {
					$config['id'] = (int)$mount['id'];
				}
				if (isset($mount['storage_id'])) {
					$config['storage_id'] = (int)$mount['storage_id'];
				}
				if (isset($mount['mountOptions'])) {
					$config['mountOptions'] = $mount['mountOptions'];
				}
				$personal[] = $config;
			}
		}
		return $personal;
	}

	/**
	 * Test connecting using the given backend configuration
	 *
	 * @param string $class backend class name
	 * @param array $options backend configuration options
	 * @return int see self::STATUS_*
	 */
	public static function getBackendStatus($class, $options, $isPersonal) {
		if (self::$skipTest) {
			return self::STATUS_SUCCESS;
		}
		foreach ($options as &$option) {
			$option = self::setUserVars(OCP\User::getUser(), $option);
		}
		if (class_exists($class)) {
			try {
				$storage = new $class($options);

				try {
					$result = $storage->test($isPersonal);
					$storage->setAvailability($result);
					if ($result) {
						return self::STATUS_SUCCESS;
					}
				} catch (\Exception $e) {
					$storage->setAvailability(false);
					throw $e;
				}
			} catch (Exception $exception) {
				\OCP\Util::logException('files_external', $exception);
			}
		}
		return self::STATUS_ERROR;
	}

	/**
	 * Add a mount point to the filesystem
	 *
	 * @param string $mountPoint Mount point
	 * @param string $class Backend class
	 * @param array $classOptions Backend parameters for the class
	 * @param string $mountType MOUNT_TYPE_GROUP | MOUNT_TYPE_USER
	 * @param string $applicable User or group to apply mount to
	 * @param bool $isPersonal Personal or system mount point i.e. is this being called from the personal or admin page
	 * @param int|null $priority Mount point priority, null for default
	 * @return boolean
	 *
	 * @deprecated use StoragesService#addStorage() instead
	 */
	public static function addMountPoint($mountPoint,
										 $class,
										 $classOptions,
										 $mountType,
										 $applicable,
										 $isPersonal = false,
										 $priority = null) {
		$backendService = self::$appContainer->query('\OCA\Files_External\Service\BackendService');
		$mountPoint = OC\Files\Filesystem::normalizePath($mountPoint);
		$relMountPoint = $mountPoint;
		if ($mountPoint === '' || $mountPoint === '/') {
			// can't mount at root folder
			return false;
		}

		$backend = $backendService->getBackend($class);
		if (!isset($backend)) {
			// invalid backend
			return false;
		}
		if ($isPersonal) {
			// Verify that the mount point applies for the current user
			// Prevent non-admin users from mounting local storage and other disabled backends
			if ($applicable != OCP\User::getUser() || !$backend->isVisibleFor(BackendConfig::VISIBILITY_PERSONAL)) {
				return false;
			}
			$mountPoint = '/' . $applicable . '/files/' . ltrim($mountPoint, '/');
		} else {
			$mountPoint = '/$user/files/' . ltrim($mountPoint, '/');
		}

		$mount = array($applicable => array(
			$mountPoint => array(
				'class' => $class,
				'options' => self::encryptPasswords($classOptions))
		)
		);
		if (!$isPersonal && !is_null($priority)) {
			$mount[$applicable][$mountPoint]['priority'] = $priority;
		}

		$mountPoints = self::readData($isPersonal ? OCP\User::getUser() : null);
		// who else loves multi-dimensional array ?
		$isNew = !isset($mountPoints[$mountType]) ||
			!isset($mountPoints[$mountType][$applicable]) ||
			!isset($mountPoints[$mountType][$applicable][$mountPoint]);
		$mountPoints = self::mergeMountPoints($mountPoints, $mount, $mountType);

		// Set default priority if none set
		if (!isset($mountPoints[$mountType][$applicable][$mountPoint]['priority'])) {
			$mountPoints[$mountType][$applicable][$mountPoint]['priority']
				= $backend->getPriority();
		}

		self::writeData($isPersonal ? OCP\User::getUser() : null, $mountPoints);

		$result = self::getBackendStatus($class, $classOptions, $isPersonal);
		if ($result === self::STATUS_SUCCESS && $isNew) {
			\OC_Hook::emit(
				\OC\Files\Filesystem::CLASSNAME,
				\OC\Files\Filesystem::signal_create_mount,
				array(
					\OC\Files\Filesystem::signal_param_path => $relMountPoint,
					\OC\Files\Filesystem::signal_param_mount_type => $mountType,
					\OC\Files\Filesystem::signal_param_users => $applicable,
				)
			);
		}
		return $result;
	}

	/**
	 *
	 * @param string $mountPoint Mount point
	 * @param string $mountType MOUNT_TYPE_GROUP | MOUNT_TYPE_USER
	 * @param string $applicable User or group to remove mount from
	 * @param bool $isPersonal Personal or system mount point
	 * @return bool
	 *
	 * @deprecated use StoragesService#removeStorage() instead
	 */
	public static function removeMountPoint($mountPoint, $mountType, $applicable, $isPersonal = false) {
		// Verify that the mount point applies for the current user
		$relMountPoints = $mountPoint;
		if ($isPersonal) {
			if ($applicable != OCP\User::getUser()) {
				return false;
			}
			$mountPoint = '/' . $applicable . '/files/' . ltrim($mountPoint, '/');
		} else {
			$mountPoint = '/$user/files/' . ltrim($mountPoint, '/');
		}
		$mountPoint = \OC\Files\Filesystem::normalizePath($mountPoint);
		$mountPoints = self::readData($isPersonal ? OCP\User::getUser() : null);
		// Remove mount point
		unset($mountPoints[$mountType][$applicable][$mountPoint]);
		// Unset parent arrays if empty
		if (empty($mountPoints[$mountType][$applicable])) {
			unset($mountPoints[$mountType][$applicable]);
			if (empty($mountPoints[$mountType])) {
				unset($mountPoints[$mountType]);
			}
		}
		self::writeData($isPersonal ? OCP\User::getUser() : null, $mountPoints);
		\OC_Hook::emit(
			\OC\Files\Filesystem::CLASSNAME,
			\OC\Files\Filesystem::signal_delete_mount,
			array(
				\OC\Files\Filesystem::signal_param_path => $relMountPoints,
				\OC\Files\Filesystem::signal_param_mount_type => $mountType,
				\OC\Files\Filesystem::signal_param_users => $applicable,
			)
		);
		return true;
	}

	/**
	 *
	 * @param string $mountPoint Mount point
	 * @param string $target The new mount point
	 * @param string $mountType MOUNT_TYPE_GROUP | MOUNT_TYPE_USER
	 * @return bool
	 */
	public static function movePersonalMountPoint($mountPoint, $target, $mountType) {
		$mountPoint = rtrim($mountPoint, '/');
		$user = OCP\User::getUser();
		$mountPoints = self::readData($user);
		if (!isset($mountPoints[$mountType][$user][$mountPoint])) {
			return false;
		}
		$mountPoints[$mountType][$user][$target] = $mountPoints[$mountType][$user][$mountPoint];
		// Remove old mount point
		unset($mountPoints[$mountType][$user][$mountPoint]);

		self::writeData($user, $mountPoints);
		return true;
	}

	/**
	 * Read the mount points in the config file into an array
	 *
	 * @param string|null $user If not null, personal for $user, otherwise system
	 * @return array
	 */
	public static function readData($user = null) {
		if (isset($user)) {
			$jsonFile = OC_User::getHome($user) . '/mount.json';
		} else {
			$datadir = \OC_Config::getValue('datadirectory', \OC::$SERVERROOT . '/data/');
			$jsonFile = \OC_Config::getValue('mount_file', $datadir . '/mount.json');
		}
		if (is_file($jsonFile)) {
			$mountPoints = json_decode(file_get_contents($jsonFile), true);
			if (is_array($mountPoints)) {
				return $mountPoints;
			}
		}
		return array();
	}

	/**
	 * Write the mount points to the config file
	 *
	 * @param string|null $user If not null, personal for $user, otherwise system
	 * @param array $data Mount points
	 */
	public static function writeData($user, $data) {
		if (isset($user)) {
			$file = OC_User::getHome($user) . '/mount.json';
		} else {
			$datadir = \OC_Config::getValue('datadirectory', \OC::$SERVERROOT . '/data/');
			$file = \OC_Config::getValue('mount_file', $datadir . '/mount.json');
		}

		foreach ($data as &$applicables) {
			foreach ($applicables as &$mountPoints) {
				foreach ($mountPoints as &$options) {
					self::addStorageId($options);
				}
			}
		}

		$content = json_encode($data, JSON_PRETTY_PRINT);
		@file_put_contents($file, $content);
		@chmod($file, 0640);
	}

	/**
	 * Get backend dependency message
	 * TODO: move into AppFramework along with templates
	 *
	 * @param BackendConfig[] $backends
	 * @return string
	 */
	public static function dependencyMessage($backends) {
		$l = new \OC_L10N('files_external');
		$message = '';
		$dependencyGroups = [];

		foreach ($backends as $backend) {
			foreach ($backend->checkDependencies() as $dependency) {
				if ($message = $dependency->getMessage()) {
					$message .= '<br />' . $l->t('<b>Note:</b> ') . $message;
				} else {
					$dependencyGroups[$dependency->getDependency()][] = $backend;
				}
			}
		}

		foreach ($dependencyGroups as $module => $dependants) {
			$backends = implode(', ', array_map(function($backend) {
				return '<i>' . $backend->getText() . '</i>';
			}, $dependants));
			$message .= '<br />' . OC_Mount_Config::getSingleDependencyMessage($l, $module, $backends);
		}

		return $message;
	}

	/**
	 * Returns a dependency missing message
	 *
	 * @param OC_L10N $l
	 * @param string $module
	 * @param string $backend
	 * @return string
	 */
	private static function getSingleDependencyMessage(OC_L10N $l, $module, $backend) {
		switch (strtolower($module)) {
			case 'curl':
				return $l->t('<b>Note:</b> The cURL support in PHP is not enabled or installed. Mounting of %s is not possible. Please ask your system administrator to install it.', $backend);
			case 'ftp':
				return $l->t('<b>Note:</b> The FTP support in PHP is not enabled or installed. Mounting of %s is not possible. Please ask your system administrator to install it.', $backend);
			default:
				return $l->t('<b>Note:</b> "%s" is not installed. Mounting of %s is not possible. Please ask your system administrator to install it.', array($module, $backend));
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
		$iv = \OCP\Util::generateRandomBytes(16);
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
	 * Merges mount points
	 *
	 * @param array $data Existing mount points
	 * @param array $mountPoint New mount point
	 * @param string $mountType
	 * @return array
	 */
	private static function mergeMountPoints($data, $mountPoint, $mountType) {
		$applicable = key($mountPoint);
		$mountPath = key($mountPoint[$applicable]);
		if (isset($data[$mountType])) {
			if (isset($data[$mountType][$applicable])) {
				// Merge priorities
				if (isset($data[$mountType][$applicable][$mountPath])
					&& isset($data[$mountType][$applicable][$mountPath]['priority'])
					&& !isset($mountPoint[$applicable][$mountPath]['priority'])
				) {
					$mountPoint[$applicable][$mountPath]['priority']
						= $data[$mountType][$applicable][$mountPath]['priority'];
				}
				$data[$mountType][$applicable]
					= array_merge($data[$mountType][$applicable], $mountPoint[$applicable]);
			} else {
				$data[$mountType] = array_merge($data[$mountType], $mountPoint);
			}
		} else {
			$data[$mountType] = $mountPoint;
		}
		return $data;
	}

	/**
	 * Returns the encryption cipher
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
	 */
	public static function makeConfigHash($config) {
		$data = json_encode(
			array(
				'c' => $config['class'],
				'm' => $config['mountpoint'],
				'o' => $config['options'],
				'p' => isset($config['priority']) ? $config['priority'] : -1,
				'mo' => isset($config['mountOptions']) ? $config['mountOptions'] : [],
			)
		);
		return hash('md5', $data);
	}

	/**
	 * Add storage id to the storage configurations that did not have any.
	 *
	 * @param string $user user for which to process storage configs
	 */
	private static function addStorageIdToConfig($user) {
		$config = self::readData($user);

		$needUpdate = false;
		foreach ($config as &$applicables) {
			foreach ($applicables as &$mountPoints) {
				foreach ($mountPoints as &$options) {
					$needUpdate |= !isset($options['storage_id']);
				}
			}
		}

		if ($needUpdate) {
			self::writeData($user, $config);
		}
	}

	/**
	 * Get storage id from the numeric storage id and set
	 * it into the given options argument. Only do this
	 * if there was no storage id set yet.
	 *
	 * This might also fail if a storage wasn't fully configured yet
	 * and couldn't be mounted, in which case this will simply return false.
	 *
	 * @param array $options storage options
	 *
	 * @return bool true if the storage id was added, false otherwise
	 */
	private static function addStorageId(&$options) {
		if (isset($options['storage_id'])) {
			return false;
		}

		$class = $options['class'];
		try {
			/** @var \OC\Files\Storage\Storage $storage */
			$storage = new $class($options['options']);
			// TODO: introduce StorageConfigException
		} catch (\Exception $e) {
			// storage might not be fully configured yet (ex: Dropbox)
			// note that storage instances aren't supposed to open any connections
			// in the constructor, so this exception is likely to be a config exception
			return false;
		}

		$options['storage_id'] = $storage->getCache()->getNumericStorageId();
		return true;
	}
}
