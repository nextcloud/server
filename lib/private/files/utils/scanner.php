<?php
/**
 * @author Jörn Friedrich Dreyer <jfd@butonic.de>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Olivier Paroz <github@oparoz.com>
 * @author Robin Appelman <icewind@owncloud.com>
 * @author Scrutinizer Auto-Fixer <auto-fixer@scrutinizer-ci.com>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
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

namespace OC\Files\Utils;

use OC\Files\View;
use OC\Files\Cache\ChangePropagator;
use OC\Files\Cache\Cache;
use OC\Files\Filesystem;
use OC\ForbiddenException;
use OC\Hooks\PublicEmitter;
use OC\Lock\DBLockingProvider;
use OCP\Files\StorageNotAvailableException;
use OCP\ILogger;

/**
 * Class Scanner
 *
 * Hooks available in scope \OC\Utils\Scanner
 *  - scanFile(string $absolutePath)
 *  - scanFolder(string $absolutePath)
 *
 * @package OC\Files\Utils
 */
class Scanner extends PublicEmitter {
	/**
	 * @var string $user
	 */
	private $user;

	/**
	 * @var \OC\Files\Cache\ChangePropagator
	 */
	protected $propagator;

	/**
	 * @var \OCP\IDBConnection
	 */
	protected $db;

	/**
	 * @var ILogger
	 */
	protected $logger;

	/**
	 * @param string $user
	 * @param \OCP\IDBConnection $db
	 * @param ILogger $logger
	 */
	public function __construct($user, $db, ILogger $logger) {
		$this->logger = $logger;
		$this->user = $user;
		$this->propagator = new ChangePropagator(new View(''));
		$this->db = $db;
	}

	/**
	 * get all storages for $dir
	 *
	 * @param string $dir
	 * @return \OC\Files\Mount\MountPoint[]
	 */
	protected function getMounts($dir) {
		//TODO: move to the node based fileapi once that's done
		\OC_Util::tearDownFS();
		\OC_Util::setupFS($this->user);

		$mountManager = Filesystem::getMountManager();
		$mounts = $mountManager->findIn($dir);
		$mounts[] = $mountManager->find($dir);
		$mounts = array_reverse($mounts); //start with the mount of $dir

		return $mounts;
	}

	/**
	 * attach listeners to the scanner
	 *
	 * @param \OC\Files\Mount\MountPoint $mount
	 */
	protected function attachListener($mount) {
		$scanner = $mount->getStorage()->getScanner();
		$emitter = $this;
		$scanner->listen('\OC\Files\Cache\Scanner', 'scanFile', function ($path) use ($mount, $emitter) {
			$emitter->emit('\OC\Files\Utils\Scanner', 'scanFile', array($mount->getMountPoint() . $path));
		});
		$scanner->listen('\OC\Files\Cache\Scanner', 'scanFolder', function ($path) use ($mount, $emitter) {
			$emitter->emit('\OC\Files\Utils\Scanner', 'scanFolder', array($mount->getMountPoint() . $path));
		});
		$scanner->listen('\OC\Files\Cache\Scanner', 'postScanFile', function ($path) use ($mount, $emitter) {
			$emitter->emit('\OC\Files\Utils\Scanner', 'postScanFile', array($mount->getMountPoint() . $path));
		});
		$scanner->listen('\OC\Files\Cache\Scanner', 'postScanFolder', function ($path) use ($mount, $emitter) {
			$emitter->emit('\OC\Files\Utils\Scanner', 'postScanFolder', array($mount->getMountPoint() . $path));
		});
		// propagate etag and mtimes when files are changed or removed
		$propagator = $this->propagator;
		$propagatorListener = function ($path) use ($mount, $propagator) {
			$fullPath = Filesystem::normalizePath($mount->getMountPoint() . $path);
			$propagator->addChange($fullPath);
		};
		$scanner->listen('\OC\Files\Cache\Scanner', 'addToCache', $propagatorListener);
		$scanner->listen('\OC\Files\Cache\Scanner', 'removeFromCache', $propagatorListener);
	}

	/**
	 * @param string $dir
	 */
	public function backgroundScan($dir) {
		$mounts = $this->getMounts($dir);
		foreach ($mounts as $mount) {
			if (is_null($mount->getStorage())) {
				continue;
			}
			// don't scan the root storage
			if ($mount->getStorage()->instanceOfStorage('\OC\Files\Storage\Local') && $mount->getMountPoint() === '/') {
				continue;
			}
			$scanner = $mount->getStorage()->getScanner();
			$this->attachListener($mount);
			$scanner->backgroundScan();
		}
		$this->propagator->propagateChanges(time());
	}

	/**
	 * @param string $dir
	 * @throws \OC\ForbiddenException
	 */
	public function scan($dir = '') {
		if (!Filesystem::isValidPath($dir)) {
			throw new \InvalidArgumentException('Invalid path to scan');
		}
		$mounts = $this->getMounts($dir);
		foreach ($mounts as $mount) {
			if (is_null($mount->getStorage())) {
				continue;
			}
			$storage = $mount->getStorage();
			// if the home storage isn't writable then the scanner is run as the wrong user
			if ($storage->instanceOfStorage('\OC\Files\Storage\Home') and
				(!$storage->isCreatable('') or !$storage->isCreatable('files'))
			) {
				throw new ForbiddenException();
			}
			$relativePath = $mount->getInternalPath($dir);
			$scanner = $storage->getScanner();
			$scanner->setUseTransactions(false);
			$this->attachListener($mount);
			$isDbLocking = \OC::$server->getLockingProvider() instanceof DBLockingProvider;
			if (!$isDbLocking) {
				$this->db->beginTransaction();
			}
			try {
				$scanner->scan($relativePath, \OC\Files\Cache\Scanner::SCAN_RECURSIVE, \OC\Files\Cache\Scanner::REUSE_ETAG | \OC\Files\Cache\Scanner::REUSE_SIZE);
				$cache = $storage->getCache();
				if ($cache instanceof Cache) {
					// only re-calculate for the root folder we scanned, anything below that is taken care of by the scanner
					$cache->correctFolderSize($relativePath);
				}
			} catch (StorageNotAvailableException $e) {
				$this->logger->error('Storage ' . $storage->getId() . ' not available');
				$this->logger->logException($e);
				$this->emit('\OC\Files\Utils\Scanner', 'StorageNotAvailable', [$e]);
			}
			if (!$isDbLocking) {
				$this->db->commit();
			}
		}
		$this->propagator->propagateChanges(time());
	}
}

