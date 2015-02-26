<?php

/**
 * ownCloud
 *
 * @author Jörn Friedrich Dreyer
 * @copyright 2012 Jörn Friedrich Dreyer jfd@owncloud.com
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
 * License as published by the Free Software Foundation; either
 * version 3 of the License, or any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU AFFERO GENERAL PUBLIC LICENSE for more details.
 *
 * You should have received a copy of the GNU Affero General Public
 * License along with this library.  If not, see <http://www.gnu.org/licenses/>.
 */


namespace Test\Files\Storage;

class AmazonS3Migration extends \Test\TestCase {

	/**
	 * @var \OC\Files\Storage\Storage instance
	 */
	protected $instance;

	/** @var array */
	protected $params;

	/** @var string */
	protected $oldId;

	/** @var string */
	protected $newId;

	protected function setUp() {
		parent::setUp();

		$uuid = $this->getUniqueID();

		$this->params['key'] = 'key'.$uuid;
		$this->params['secret'] = 'secret'.$uuid;
		$this->params['bucket'] = 'bucket'.$uuid;

		$this->oldId = 'amazon::' . $this->params['key'] . md5($this->params['secret']);
		$this->newId = 'amazon::' . $this->params['bucket'];
	}

	protected function tearDown() {
		$this->deleteStorage($this->oldId);
		$this->deleteStorage($this->newId);

		parent::tearDown();
	}

	public function testUpdateLegacyOnlyId () {
		// add storage ids
		$oldCache = new \OC\Files\Cache\Cache($this->oldId);

		// add file to old cache
		$fileId = $oldCache->put('/', array('size' => 0, 'mtime' => time(), 'mimetype' => 'httpd/directory'));

		try {
			$this->instance = new \OC\Files\Storage\AmazonS3($this->params);
		} catch (\Exception $e) {
			//ignore
		}
		$storages = $this->getStorages();

		$this->assertTrue(isset($storages[$this->newId]));
		$this->assertFalse(isset($storages[$this->oldId]));
		$this->assertSame((int)$oldCache->getNumericStorageId(), (int)$storages[$this->newId]);

		list($storageId, $path) = \OC\Files\Cache\Cache::getById($fileId);

		$this->assertSame($this->newId, $storageId);
		$this->assertSame('/', $path);
	}

	public function testUpdateLegacyAndNewId () {
		// add storage ids

		$oldCache = new \OC\Files\Cache\Cache($this->oldId);
		new \OC\Files\Cache\Cache($this->newId);

		// add file to old cache
		$fileId = $oldCache->put('/', array('size' => 0, 'mtime' => time(), 'mimetype' => 'httpd/directory'));

		try {
			$this->instance = new \OC\Files\Storage\AmazonS3($this->params);
		} catch (\Exception $e) {
			//ignore
		}
		$storages = $this->getStorages();

		$this->assertTrue(isset($storages[$this->newId]));
		$this->assertFalse(isset($storages[$this->oldId]));

		$this->assertNull(\OC\Files\Cache\Cache::getById($fileId), 'old filecache has not been cleared');
	}

	/**
	 * @param $storages
	 * @return array
	 */
	public function getStorages() {
		$storages = array();
		$stmt = \OC::$server->getDatabaseConnection()->prepare(
			'SELECT `numeric_id`, `id` FROM `*PREFIX*storages` WHERE `id` IN (?, ?)'
		);
		$stmt->execute(array($this->oldId, $this->newId));
		while ($row = $stmt->fetch()) {
			$storages[$row['id']] = $row['numeric_id'];
		}
		return $storages;
	}

	public function deleteStorage($id) {
		$stmt = \OC::$server->getDatabaseConnection()->prepare(
			'DELETE FROM `*PREFIX*storages` WHERE `id` = ?'
		);
		$stmt->execute(array($id));
	}
}