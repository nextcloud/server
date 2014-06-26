<?php
/**
 * Copyright (c) 2014 Vincent Petry <pvince81@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

/**
 * Tests for the converting of legacy storages to home storages.
 *
 * @see \OC\Repair\RepairMimeTypes
 */
class TestRepairMimeTypes extends PHPUnit_Framework_TestCase {

	/** @var \OC\RepairStep */
	private $repair;

	private $storage;

	public function setUp() {
		$this->storage = new \OC\Files\Storage\Temporary(array());

		$this->repair = new \OC\Repair\RepairMimeTypes();
	}

	public function tearDown() {
		$this->storage->getCache()->clear();
		$sql = 'DELETE FROM `*PREFIX*storages` WHERE id = ?';
		\OC_DB::executeAudited($sql, array($this->storage->getId()));
		$this->clearMimeTypes();

		DummyFileCache::clearCachedMimeTypes();
	}

	private function clearMimeTypes() {
		$sql = 'DELETE FROM `*PREFIX*mimetypes`';
		\OC_DB::executeAudited($sql);
	}

	private function addEntries($entries) {
		// create files for the different extensions, this
		// will also automatically create the corresponding mime types
		foreach ($entries as $entry) {
			$this->storage->getCache()->put(
				$entry[0],
				array(
					'size' => 0,
					'mtime' => 0,
					'mimetype' => $entry[1]
				)
			);
		}

	}

	private function checkEntries($entries) {
		foreach ($entries as $entry) {
			$data = $this->storage->getCache()->get($entry[0]);
			$this->assertEquals($entry[1], $data['mimetype']);
		}
	}

	/**
	 * Returns the id of a given mime type or null
	 * if it does not exist.
	 */
	private function getMimeTypeIdFromDB($mimeType) {
		$sql = 'SELECT `id` FROM `*PREFIX*mimetypes` WHERE mimetype = ?';
		$results = \OC_DB::executeAudited($sql, array($mimeType));
		$result = $results->fetchOne();
		if ($result) {
			return $result['id'];
		}
		return null;
	}

	/**
	 * Test renaming and splitting old office mime types
	 */
	public function testRenameOfficeMimeTypes() {
		$this->addEntries(
			array(
				array('test.doc', 'application/msword'),
				array('test.docx', 'application/msword'),
				array('test.xls', 'application/msexcel'),
				array('test.xlsx', 'application/msexcel'),
				array('test.ppt', 'application/mspowerpoint'),
				array('test.pptx', 'application/mspowerpoint'),
			)
		);

		$this->repair->run();

		// force mimetype reload
		DummyFileCache::clearCachedMimeTypes();
		$this->storage->getCache()->loadMimeTypes();

		$this->checkEntries(
			array(
				array('test.doc', 'application/msword'),
				array('test.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'),
				array('test.xls', 'application/vnd.ms-excel'),
				array('test.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'),
				array('test.ppt', 'application/vnd.ms-powerpoint'),
				array('test.pptx', 'application/vnd.openxmlformats-officedocument.presentationml.presentation'),
			)
		);
	}

	/**
	 * Test renaming and splitting old office mime types when
	 * new ones already exist
	 */
	public function testRenameOfficeMimeTypesWhenExist() {
		$this->addEntries(
			array(
				array('test.doc', 'application/msword'),
				array('test.docx', 'application/msword'),
				array('test.xls', 'application/msexcel'),
				array('test.xlsx', 'application/msexcel'),
				array('test.ppt', 'application/mspowerpoint'),
				array('test.pptx', 'application/mspowerpoint'),
				// make it so that the new mimetypes already exist
				array('bogus.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'),
				array('bogus.xlsx', 'application/vnd.ms-excel'),
				array('bogus.pptx', 'application/vnd.ms-powerpoint'),
				array('bogus2.docx', 'application/wrong'),
				array('bogus2.xlsx', 'application/wrong'),
				array('bogus2.pptx', 'application/wrong'),
			)
		);

		$this->repair->run();

		// force mimetype reload
		DummyFileCache::clearCachedMimeTypes();
		$this->storage->getCache()->loadMimeTypes();

		$this->checkEntries(
			array(
				array('test.doc', 'application/msword'),
				array('test.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'),
				array('test.xls', 'application/vnd.ms-excel'),
				array('test.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'),
				array('test.ppt', 'application/vnd.ms-powerpoint'),
				array('test.pptx', 'application/vnd.openxmlformats-officedocument.presentationml.presentation'),
				array('bogus.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'),
				array('bogus.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'),
				array('bogus.pptx', 'application/vnd.openxmlformats-officedocument.presentationml.presentation'),
				array('bogus2.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'),
				array('bogus2.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'),
				array('bogus2.pptx', 'application/vnd.openxmlformats-officedocument.presentationml.presentation'),
			)
		);

		// wrong mimetypes are gone
		$this->assertNull($this->getMimeTypeIdFromDB('application/msexcel'));
		$this->assertNull($this->getMimeTypeIdFromDB('application/mspowerpoint'));
	}

	/**
	 * Test that nothing happens and no error happens when all mimetypes are
	 * already correct and no old ones exist..
	 */
	public function testDoNothingWhenOnlyNewFiles() {
		$this->addEntries(
			array(
				array('test.doc', 'application/msword'),
				array('test.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'),
				array('test.xls', 'application/vnd.ms-excel'),
				array('test.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'),
				array('test.ppt', 'application/vnd.ms-powerpoint'),
				array('test.pptx', 'application/vnd.openxmlformats-officedocument.presentationml.presentation'),
			)
		);

		$this->repair->run();

		// force mimetype reload
		DummyFileCache::clearCachedMimeTypes();
		$this->storage->getCache()->loadMimeTypes();

		$this->checkEntries(
			array(
				array('test.doc', 'application/msword'),
				array('test.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'),
				array('test.xls', 'application/vnd.ms-excel'),
				array('test.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'),
				array('test.ppt', 'application/vnd.ms-powerpoint'),
				array('test.pptx', 'application/vnd.openxmlformats-officedocument.presentationml.presentation'),
			)
		);
	}
}

/**
 * Dummy class to access protected members
 */
class DummyFileCache extends \OC\Files\Cache\Cache {

	public static function clearCachedMimeTypes() {
		self::$mimetypeIds = array();
		self::$mimetypes = array();
	}
}

