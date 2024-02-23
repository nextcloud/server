<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
 * @author Bart Visscher <bartv@thisnet.nl>
 * @author Bernhard Reiter <ockham@raz.or.at>
 * @author Björn Schießle <bjoern@schiessle.org>
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin Appelman <robin@icewind.nl>
 * @author Robin McCorkell <robin@mccorkell.me.uk>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Sebastian Döll <sebastian.doell@libasys.de>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 * @author Vincent Petry <vincent@nextcloud.com>
 * @author Volkan Gezer <volkangezer@gmail.com>
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

namespace OC\Share;

use OCA\Files_Sharing\ShareBackend\File;
use OCP\DB\Exception;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;
use OCP\Share\IShare;
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
				'Sharing backend '.$class.' not registered, '.self::$backendTypes[$itemType]['class']
				.' is already registered for '.$itemType,
				['app' => 'files_sharing']);
		}
		return false;
	}

	/**
	 * Get the item of item type shared with a given user by source
	 *
	 * @param string $itemType
	 * @param string $itemSource
	 * @param ?string $user User to whom the item was shared
	 * @param ?string $owner Owner of the share
	 * @param ?int $shareType only look for a specific share type
	 * @return array Return list of items with file_target, permissions and expiration
	 * @throws Exception
	 */
	public static function getItemSharedWithUser(string $itemType, string $itemSource, ?string $user = null, ?string $owner = null, ?int $shareType = null) {
		$shares = [];
		$fileDependent = $itemType === 'file' || $itemType === 'folder';
		$qb = self::getSelectStatement(self::FORMAT_NONE, $fileDependent);
		$qb->from('share', 's');
		if ($fileDependent) {
			$qb->innerJoin('s', 'filecache', 'f', $qb->expr()->eq('file_source', 'f.fileid'));
			$qb->innerJoin('s', 'storages', 'st', $qb->expr()->eq('numeric_id', 'f.storage'));
			$column = 'file_source';
		} else {
			$column = 'item_source';
		}

		$qb->where($qb->expr()->eq($column, $qb->createNamedParameter($itemSource)))
			->andWhere($qb->expr()->eq('item_type', $qb->createNamedParameter($itemType)));

		// for link shares $user === null
		if ($user !== null) {
			$qb->andWhere($qb->expr()->eq('share_with', $qb->createNamedParameter($user)));
		}

		if ($shareType !== null) {
			$qb->andWhere($qb->expr()->eq('share_type', $qb->createNamedParameter($shareType, IQueryBuilder::PARAM_INT)));
		}

		if ($owner !== null) {
			$qb->andWhere($qb->expr()->eq('uid_owner', $qb->createNamedParameter($owner)));
		}

		$result = $qb->executeQuery();
		while ($row = $result->fetch()) {
			if ($fileDependent && !self::isFileReachable($row['path'], $row['storage_id'])) {
				continue;
			}
			if ($fileDependent && (int)$row['file_parent'] === -1) {
				// if it is a mount point we need to get the path from the mount manager
				$mountManager = \OC\Files\Filesystem::getMountManager();
				$mountPoint = $mountManager->findByStorageId($row['storage_id']);
				if (!empty($mountPoint)) {
					$path = $mountPoint[0]->getMountPoint();
					$path = trim($path, '/');
					$path = substr($path, strlen($owner) + 1); //normalize path to 'files/foo.txt`
					$row['path'] = $path;
				} else {
					\OC::$server->get(LoggerInterface::class)->warning(
						'Could not resolve mount point for ' . $row['storage_id'],
						['app' => 'OCP\Share']
					);
				}
			}
			$shares[] = $row;
		}
		$result->closeCursor();

		// if we didn't found a result then let's look for a group share.
		if (empty($shares) && $user !== null) {
			$userObject = \OC::$server->getUserManager()->get($user);
			$groups = [];
			if ($userObject) {
				$groups = \OC::$server->getGroupManager()->getUserGroupIds($userObject);
			}

			if (!empty($groups)) {
				$qb = self::getSelectStatement(self::FORMAT_NONE, $fileDependent);
				$qb->from('share', 's');

				if ($fileDependent) {
					$qb->innerJoin('s', 'filecache', 'f', $qb->expr()->eq('file_source', 'f.fileid'))
						->innerJoin('s', 'storages', 'st', $qb->expr()->eq('numeric_id', 'f.storage'));
				}

				$qb->where($qb->expr()->eq($column, $qb->createNamedParameter($itemSource)))
					->andWhere($qb->expr()->eq('item_type', $qb->createNamedParameter($itemType, IQueryBuilder::PARAM_STR)))
					->andWhere($qb->expr()->in('share_with', $qb->createNamedParameter($groups, IQueryBuilder::PARAM_STR_ARRAY)));

				if ($owner !== null) {
					$qb->andWhere($qb->expr()->eq('uid_owner', $qb->createNamedParameter($owner)));
				}
				$result = $qb->executeQuery();

				while ($row = $result->fetch()) {
					$shares[] = $row;
				}
				$result->closeCursor();
			}
		}

		return $shares;
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
	 * Construct select statement
	 *
	 * @param bool $fileDependent ist it a file/folder share or a general share
	 */
	private static function getSelectStatement(int $format, bool $fileDependent, ?string $uidOwner = null): IQueryBuilder {
		/** @var IDBConnection $connection */
		$connection = \OC::$server->get(IDBConnection::class);
		$qb = $connection->getQueryBuilder();
		if ($format == self::FORMAT_STATUSES) {
			if ($fileDependent) {
				return $qb->select(
					's.id',
					's.parent',
					'share_type',
					'path',
					'storage',
					'share_with',
					'uid_owner',
					'file_source',
					'stime',
					's.permissions',
					'uid_initiator'
				)->selectAlias('st.id', 'storage_id')
					->selectAlias('f.parent', 'file_parent');
			}
			return $qb->select('id', 'parent', 'share_type', 'share_with', 'uid_owner', 'item_source', 'stime', 's.permissions');
		}

		if (isset($uidOwner)) {
			if ($fileDependent) {
				return $qb->select(
					's.id',
					'item_type',
					'item_source',
					's.parent',
					'share_type',
					'share_with',
					'file_source',
					'file_target',
					'path',
					's.permissions',
					'stime',
					'expiration',
					'token',
					'storage',
					'mail_send',
					'uid_owner',
					'uid_initiator'
				)->selectAlias('st.id', 'storage_id')
					->selectAlias('f.parent', 'file_parent');
			}
			return $qb->select('id', 'item_type', 'item_source', 'parent', 'share_type',
				'share_with', 'uid_owner', 'file_source', 'stime', 's.permissions',
				'expiration', 'token', 'mail_send');
		}

		if ($fileDependent) {
			if ($format == File::FORMAT_GET_FOLDER_CONTENTS || $format == File::FORMAT_FILE_APP_ROOT) {
				return $qb->select(
					's.id',
					'item_type',
					'item_source',
					's.parent',
					'uid_owner',
					'share_type',
					'share_with',
					'file_source',
					'path',
					'file_target',
					's.permissions',
					'stime',
					'expiration',
					'storage',
					'name',
					'mtime',
					'mimepart',
					'size',
					'encrypted',
					'etag',
					'mail_send'
				)->selectAlias('f.parent', 'file_parent');
			}
			return $qb->select(
				's.id',
				'item_type',
				'item_source',
				'item_target',
				's.parent',
				'share_type',
				'share_with',
				'uid_owner',
				'file_source',
				'path',
				'file_target',
				's.permissions',
				'stime',
				'expiration',
				'token',
				'storage',
				'mail_send',
			)->selectAlias('f.parent', 'file_parent')
				->selectAlias('st.id', 'storage_id');
		}
		return $qb->select('*');
	}


	/**
	 * transform db results
	 *
	 * @param array $row result
	 */
	private static function transformDBResults(&$row) {
		if (isset($row['id'])) {
			$row['id'] = (int)$row['id'];
		}
		if (isset($row['share_type'])) {
			$row['share_type'] = (int)$row['share_type'];
		}
		if (isset($row['parent'])) {
			$row['parent'] = (int)$row['parent'];
		}
		if (isset($row['file_parent'])) {
			$row['file_parent'] = (int)$row['file_parent'];
		}
		if (isset($row['file_source'])) {
			$row['file_source'] = (int)$row['file_source'];
		}
		if (isset($row['permissions'])) {
			$row['permissions'] = (int)$row['permissions'];
		}
		if (isset($row['storage'])) {
			$row['storage'] = (int)$row['storage'];
		}
		if (isset($row['stime'])) {
			$row['stime'] = (int)$row['stime'];
		}
		if (isset($row['expiration']) && $row['share_type'] !== IShare::TYPE_LINK) {
			// discard expiration date for non-link shares, which might have been
			// set by ancient bugs
			$row['expiration'] = null;
		}
	}

	/**
	 * format result
	 *
	 * @param array $items result
	 * @param string $column is it a file share or a general share ('file_target' or 'item_target')
	 * @param \OCP\Share_Backend $backend sharing backend
	 * @param int $format
	 * @param array $parameters additional format parameters
	 * @return array format result
	 */
	private static function formatResult($items, $column, $backend, $format = self::FORMAT_NONE, $parameters = null) {
		if ($format === self::FORMAT_NONE) {
			return $items;
		} elseif ($format === self::FORMAT_STATUSES) {
			$statuses = [];
			foreach ($items as $item) {
				if ($item['share_type'] === IShare::TYPE_LINK) {
					if ($item['uid_initiator'] !== \OC::$server->getUserSession()->getUser()->getUID()) {
						continue;
					}
					$statuses[$item[$column]]['link'] = true;
				} elseif (!isset($statuses[$item[$column]])) {
					$statuses[$item[$column]]['link'] = false;
				}
				if (!empty($item['file_target'])) {
					$statuses[$item[$column]]['path'] = $item['path'];
				}
			}
			return $statuses;
		} else {
			return $backend->formatItems($items, $format, $parameters);
		}
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

	/**
	 * Checks whether the given path is reachable for the given owner
	 *
	 * @param string $path path relative to files
	 * @param string $ownerStorageId storage id of the owner
	 *
	 * @return boolean true if file is reachable, false otherwise
	 */
	private static function isFileReachable($path, $ownerStorageId) {
		// if outside the home storage, file is always considered reachable
		if (!(substr($ownerStorageId, 0, 6) === 'home::' ||
			substr($ownerStorageId, 0, 13) === 'object::user:'
		)) {
			return true;
		}

		// if inside the home storage, the file has to be under "/files/"
		$path = ltrim($path, '/');
		if (substr($path, 0, 6) === 'files/') {
			return true;
		}

		return false;
	}
}
