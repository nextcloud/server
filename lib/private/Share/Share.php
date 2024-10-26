<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\Share;

use OCA\Files_Sharing\ShareBackend\File;
use Psr\Log\LoggerInterface;

/**
 * This class provides the ability for apps to share their content between users.
 * Apps must create a backend class that implements OCP\Share_Backend and register it with this class.
 *
 * It provides the following hooks:
 *  - post_shared
 */
class Share extends Constants {
	/** CRUDS permissions (Create, Read, Update, Delete, Share) using a bitmask
	 * Construct permissions for share() and setPermissions with Or (|) e.g.
	 * Give user read and update permissions: PERMISSION_READ | PERMISSION_UPDATE
	 *
	 * Check if permission is granted with And (&) e.g. Check if delete is
	 * granted: if ($permissions & PERMISSION_DELETE)
	 *
	 * Remove permissions with And (&) and Not (~) e.g. Remove the update
	 * permission: $permissions &= ~PERMISSION_UPDATE
	 *
	 * Apps are required to handle permissions on their own, this class only
	 * stores and manages the permissions of shares
	 *
	 * @see lib/public/Constants.php
	 */

	/**
	 * Register a sharing backend class that implements OCP\Share_Backend for an item type
	 *
	 * @param string $itemType Item type
	 * @param string $class Backend class
	 * @param string $collectionOf (optional) Depends on item type
	 * @param array $supportedFileExtensions (optional) List of supported file extensions if this item type depends on files
	 * @return boolean true if backend is registered or false if error
	 */
	public static function registerBackend($itemType, $class, $collectionOf = null, $supportedFileExtensions = null) {
		if (\OC::$server->getConfig()->getAppValue('core', 'shareapi_enabled', 'yes') == 'yes') {
			if (!isset(self::$backendTypes[$itemType])) {
				self::$backendTypes[$itemType] = [
					'class' => $class,
					'collectionOf' => $collectionOf,
					'supportedFileExtensions' => $supportedFileExtensions
				];
				return true;
			}
			\OC::$server->get(LoggerInterface::class)->warning(
				'Sharing backend ' . $class . ' not registered, ' . self::$backendTypes[$itemType]['class']
				. ' is already registered for ' . $itemType,
				['app' => 'files_sharing']);
		}
		return false;
	}

	/**
	 * Get the backend class for the specified item type
	 *
	 * @param string $itemType
	 * @return \OCP\Share_Backend
	 * @throws \Exception
	 */
	public static function getBackend($itemType) {
		$l = \OCP\Util::getL10N('lib');
		$logger = \OCP\Server::get(LoggerInterface::class);
		if (isset(self::$backends[$itemType])) {
			return self::$backends[$itemType];
		} elseif (isset(self::$backendTypes[$itemType]['class'])) {
			$class = self::$backendTypes[$itemType]['class'];
			if (class_exists($class)) {
				self::$backends[$itemType] = new $class;
				if (!(self::$backends[$itemType] instanceof \OCP\Share_Backend)) {
					$message = 'Sharing backend %s must implement the interface OCP\Share_Backend';
					$message_t = $l->t('Sharing backend %s must implement the interface OCP\Share_Backend', [$class]);
					$logger->error(sprintf($message, $class), ['app' => 'OCP\Share']);
					throw new \Exception($message_t);
				}
				return self::$backends[$itemType];
			} else {
				$message = 'Sharing backend %s not found';
				$message_t = $l->t('Sharing backend %s not found', [$class]);
				$logger->error(sprintf($message, $class), ['app' => 'OCP\Share']);
				throw new \Exception($message_t);
			}
		}
		$message = 'Sharing backend for %s not found';
		$message_t = $l->t('Sharing backend for %s not found', [$itemType]);
		$logger->error(sprintf($message, $itemType), ['app' => 'OCP\Share']);
		throw new \Exception($message_t);
	}

	/**
	 * Check if resharing is allowed
	 *
	 * @return boolean true if allowed or false
	 *
	 * Resharing is allowed by default if not configured
	 */
	public static function isResharingAllowed() {
		if (!isset(self::$isResharingAllowed)) {
			if (\OC::$server->getConfig()->getAppValue('core', 'shareapi_allow_resharing', 'yes') == 'yes') {
				self::$isResharingAllowed = true;
			} else {
				self::$isResharingAllowed = false;
			}
		}
		return self::$isResharingAllowed;
	}

	/**
	 * group items with link to the same source
	 *
	 * @param array $items
	 * @param string $itemType
	 * @return array of grouped items
	 */
	protected static function groupItems($items, $itemType) {
		$fileSharing = $itemType === 'file' || $itemType === 'folder';

		$result = [];

		foreach ($items as $item) {
			$grouped = false;
			foreach ($result as $key => $r) {
				// for file/folder shares we need to compare file_source, otherwise we compare item_source
				// only group shares if they already point to the same target, otherwise the file where shared
				// before grouping of shares was added. In this case we don't group them to avoid confusions
				if (($fileSharing && $item['file_source'] === $r['file_source'] && $item['file_target'] === $r['file_target']) ||
					(!$fileSharing && $item['item_source'] === $r['item_source'] && $item['item_target'] === $r['item_target'])) {
					// add the first item to the list of grouped shares
					if (!isset($result[$key]['grouped'])) {
						$result[$key]['grouped'][] = $result[$key];
					}
					$result[$key]['permissions'] = (int)$item['permissions'] | (int)$r['permissions'];
					$result[$key]['grouped'][] = $item;
					$grouped = true;
					break;
				}
			}

			if (!$grouped) {
				$result[] = $item;
			}
		}

		return $result;
	}

	/**
	 * remove protocol from URL
	 *
	 * @param string $url
	 * @return string
	 */
	public static function removeProtocolFromUrl($url) {
		if (str_starts_with($url, 'https://')) {
			return substr($url, strlen('https://'));
		} elseif (str_starts_with($url, 'http://')) {
			return substr($url, strlen('http://'));
		}

		return $url;
	}


	/**
	 * @return int
	 */
	public static function getExpireInterval() {
		return (int)\OC::$server->getConfig()->getAppValue('core', 'shareapi_expire_after_n_days', '7');
	}
}
