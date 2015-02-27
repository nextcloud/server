<?php
/**
 * Copyright (c) 2013 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OC\Files\Utils;

use OC\Files\View;
use OC\Files\Cache\ChangePropagator;
use OC\Files\Filesystem;
use OC\ForbiddenException;
use OC\Hooks\PublicEmitter;

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
	 * @param string $user
	 * @param \OCP\IDBConnection $db
	 */
	public function __construct($user, $db) {
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
		$absolutePath = Filesystem::getView()->getAbsolutePath($dir);

		$mountManager = Filesystem::getMountManager();
		$mounts = $mountManager->findIn($absolutePath);
		$mounts[] = $mountManager->find($absolutePath);
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
			$this->db->beginTransaction();
			$scanner->scan($relativePath, \OC\Files\Cache\Scanner::SCAN_RECURSIVE, \OC\Files\Cache\Scanner::REUSE_ETAG | \OC\Files\Cache\Scanner::REUSE_SIZE);
			$this->db->commit();
		}
		$this->propagator->propagateChanges(time());
	}
}

