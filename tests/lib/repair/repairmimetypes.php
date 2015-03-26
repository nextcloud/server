<?php
/**
 * Copyright (c) 2014 Vincent Petry <pvince81@owncloud.com>
 * Copyright (c) 2014-2015 Olivier Paroz owncloud@oparoz.com
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */
namespace Test\Repair;

/**
 * Tests for the converting of legacy storages to home storages.
 *
 * @see \OC\Repair\RepairMimeTypes
 */
class RepairMimeTypes extends \Test\TestCase {

	/** @var \OC\RepairStep */
	private $repair;

	private $storage;

	protected function setUp() {
		parent::setUp();
		$this->storage = new \OC\Files\Storage\Temporary([]);

		$this->repair = new \OC\Repair\RepairMimeTypes();
	}

	protected function tearDown() {
		$this->storage->getCache()->clear();
		$sql = 'DELETE FROM `*PREFIX*storages` WHERE `id` = ?';
		\OC_DB::executeAudited($sql, [$this->storage->getId()]);
		$this->clearMimeTypes();

		DummyFileCache::clearCachedMimeTypes();

		parent::tearDown();
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
				[
					'size' => 0,
					'mtime' => 0,
					'mimetype' => $entry[1]
				]
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
		$sql = 'SELECT `id` FROM `*PREFIX*mimetypes` WHERE `mimetype` = ?';
		$results = \OC_DB::executeAudited($sql, [$mimeType]);
		$result = $results->fetchOne();
		if ($result) {
			return $result['id'];
		}
		return null;
	}

	private function renameMimeTypes($currentMimeTypes, $fixedMimeTypes) {
		$this->addEntries($currentMimeTypes);

		$this->repair->run();

		// force mimetype reload
		DummyFileCache::clearCachedMimeTypes();
		$this->storage->getCache()->loadMimeTypes();

		$this->checkEntries($fixedMimeTypes);
	}

	/**
	 * Test renaming and splitting old office mime types
	 */
	public function testRenameOfficeMimeTypes() {
		$currentMimeTypes = [
			['test.doc', 'application/msword'],
			['test.docx', 'application/msword'],
			['test.xls', 'application/msexcel'],
			['test.xlsx', 'application/msexcel'],
			['test.ppt', 'application/mspowerpoint'],
			['test.pptx', 'application/mspowerpoint'],
		];

		$fixedMimeTypes = [
			['test.doc', 'application/msword'],
			['test.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
			['test.xls', 'application/vnd.ms-excel'],
			['test.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
			['test.ppt', 'application/vnd.ms-powerpoint'],
			['test.pptx', 'application/vnd.openxmlformats-officedocument.presentationml.presentation'],
		];

		$this->renameMimeTypes($currentMimeTypes, $fixedMimeTypes);
	}

	/**
	 * Test renaming old fonts mime types
	 */
	public function testRenameFontsMimeTypes() {
		$currentMimeTypes = [
			['test.ttf', 'application/x-font-ttf'],
			['test.otf', 'font/opentype'],
			['test.pfb', 'application/octet-stream'],
		];

		$fixedMimeTypes = [
			['test.ttf', 'application/font-sfnt'],
			['test.otf', 'application/font-sfnt'],
			['test.pfb', 'application/x-font'],
		];

		$this->renameMimeTypes($currentMimeTypes, $fixedMimeTypes);
	}

	/**
	 * Test renaming the APK mime type
	 */
	public function testRenameAPKMimeType() {
		$currentMimeTypes = [
			['test.apk', 'application/octet-stream'],
			['bogus.apk', 'application/vnd.android.package-archive'],
			['bogus2.apk', 'application/wrong'],
		];

		$fixedMimeTypes = [
			['test.apk', 'application/vnd.android.package-archive'],
			['bogus.apk', 'application/vnd.android.package-archive'],
			['bogus2.apk', 'application/vnd.android.package-archive'],
		];

		$this->renameMimeTypes($currentMimeTypes, $fixedMimeTypes);
	}

	/**
	 * Test renaming the postscript mime types
	 */
	public function testRenamePostscriptMimeType() {
		$currentMimeTypes = [
			['test.eps', 'application/octet-stream'],
			['test.ps', 'application/octet-stream'],
		];

		$fixedMimeTypes = [
			['test.eps', 'application/postscript'],
			['test.ps', 'application/postscript'],
		];

		$this->renameMimeTypes($currentMimeTypes, $fixedMimeTypes);
	}

	/**
	 * Test renaming the Raw mime types
	 */
	public function testRenameRawMimeType() {
		$currentMimeTypes = [
			['test.arw', 'application/octet-stream'],
			['test.cr2', 'application/octet-stream'],
			['test.dcr', 'application/octet-stream'],
			['test.dng', 'application/octet-stream'],
			['test.erf', 'application/octet-stream'],
			['test.iiq', 'application/octet-stream'],
			['test.k25', 'application/octet-stream'],
			['test.kdc', 'application/octet-stream'],
			['test.mef', 'application/octet-stream'],
			['test.nef', 'application/octet-stream'],
			['test.orf', 'application/octet-stream'],
			['test.pef', 'application/octet-stream'],
			['test.raf', 'application/octet-stream'],
			['test.rw2', 'application/octet-stream'],
			['test.srf', 'application/octet-stream'],
			['test.sr2', 'application/octet-stream'],
			['test.xrf', 'application/octet-stream'],
			['CapitalExtension.DNG', 'application/octet-stream'],
		];

		$fixedMimeTypes = [
			['test.arw', 'image/x-dcraw'],
			['test.cr2', 'image/x-dcraw'],
			['test.dcr', 'image/x-dcraw'],
			['test.dng', 'image/x-dcraw'],
			['test.erf', 'image/x-dcraw'],
			['test.iiq', 'image/x-dcraw'],
			['test.k25', 'image/x-dcraw'],
			['test.kdc', 'image/x-dcraw'],
			['test.mef', 'image/x-dcraw'],
			['test.nef', 'image/x-dcraw'],
			['test.orf', 'image/x-dcraw'],
			['test.pef', 'image/x-dcraw'],
			['test.raf', 'image/x-dcraw'],
			['test.rw2', 'image/x-dcraw'],
			['test.srf', 'image/x-dcraw'],
			['test.sr2', 'image/x-dcraw'],
			['test.xrf', 'image/x-dcraw'],
			['CapitalExtension.DNG', 'image/x-dcraw'],
		];

		$this->renameMimeTypes($currentMimeTypes, $fixedMimeTypes);
	}

	public function testRename3dImagesMimeType() {
		$currentMimeTypes = [
			['test.eps', 'application/octet-stream'],
			['test.ps', 'application/octet-stream'],
		];

		$fixedMimeTypes = [
			['test.eps', 'application/postscript'],
			['test.ps', 'application/postscript'],
		];

		$this->renameMimeTypes($currentMimeTypes, $fixedMimeTypes);
	}

	/**
	 * Test renaming and splitting old office mime types when
	 * new ones already exist
	 */
	public function testRenameOfficeMimeTypesWhenExist() {
		$currentMimeTypes = [
			['test.doc', 'application/msword'],
			['test.docx', 'application/msword'],
			['test.xls', 'application/msexcel'],
			['test.xlsx', 'application/msexcel'],
			['test.ppt', 'application/mspowerpoint'],
			['test.pptx', 'application/mspowerpoint'],
			// make it so that the new mimetypes already exist
			['bogus.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
			['bogus.xlsx', 'application/vnd.ms-excel'],
			['bogus.pptx', 'application/vnd.ms-powerpoint'],
			['bogus2.docx', 'application/wrong'],
			['bogus2.xlsx', 'application/wrong'],
			['bogus2.pptx', 'application/wrong'],
		];

		$fixedMimeTypes = [
			['test.doc', 'application/msword'],
			['test.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
			['test.xls', 'application/vnd.ms-excel'],
			['test.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
			['test.ppt', 'application/vnd.ms-powerpoint'],
			['test.pptx', 'application/vnd.openxmlformats-officedocument.presentationml.presentation'],
			['bogus.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
			['bogus.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
			['bogus.pptx', 'application/vnd.openxmlformats-officedocument.presentationml.presentation'],
			['bogus2.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
			['bogus2.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
			['bogus2.pptx', 'application/vnd.openxmlformats-officedocument.presentationml.presentation'],
		];

		$this->renameMimeTypes($currentMimeTypes, $fixedMimeTypes);

		// wrong mimetypes are gone
		$this->assertNull($this->getMimeTypeIdFromDB('application/msexcel'));
		$this->assertNull($this->getMimeTypeIdFromDB('application/mspowerpoint'));
	}

	/**
	 * Test renaming old fonts mime types when
	 * new ones already exist
	 */
	public function testRenameFontsMimeTypesWhenExist() {
		$currentMimeTypes = [
			['test.ttf', 'application/x-font-ttf'],
			['test.otf', 'font/opentype'],
			// make it so that the new mimetypes already exist
			['bogus.ttf', 'application/font-sfnt'],
			['bogus.otf', 'application/font-sfnt'],
			['bogus2.ttf', 'application/wrong'],
			['bogus2.otf', 'application/wrong'],
		];

		$fixedMimeTypes = [
			['test.ttf', 'application/font-sfnt'],
			['test.otf', 'application/font-sfnt'],
			['bogus.ttf', 'application/font-sfnt'],
			['bogus.otf', 'application/font-sfnt'],
			['bogus2.ttf', 'application/font-sfnt'],
			['bogus2.otf', 'application/font-sfnt'],
		];

		$this->renameMimeTypes($currentMimeTypes, $fixedMimeTypes);

		// wrong mimetypes are gone
		$this->assertNull($this->getMimeTypeIdFromDB('application/x-font-ttf'));
		$this->assertNull($this->getMimeTypeIdFromDB('font'));
		$this->assertNull($this->getMimeTypeIdFromDB('font/opentype'));
	}

	/**
	 * Test that nothing happens and no error happens when all mimetypes are
	 * already correct and no old ones exist..
	 */
	public function testDoNothingWhenOnlyNewFiles() {
		$currentMimeTypes = [
			['test.doc', 'application/msword'],
			['test.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
			['test.xls', 'application/vnd.ms-excel'],
			['test.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
			['test.ppt', 'application/vnd.ms-powerpoint'],
			['test.pptx', 'application/vnd.openxmlformats-officedocument.presentationml.presentation'],
			['test.apk', 'application/vnd.android.package-archive'],
			['test.ttf', 'application/font-sfnt'],
			['test.otf', 'application/font-sfnt'],
			['test.pfb', 'application/x-font'],
			['test.eps', 'application/postscript'],
			['test.ps', 'application/postscript'],
			['test.arw', 'image/x-dcraw'],
			['test.cr2', 'image/x-dcraw'],
			['test.dcr', 'image/x-dcraw'],
			['test.dng', 'image/x-dcraw'],
			['test.erf', 'image/x-dcraw'],
			['test.iiq', 'image/x-dcraw'],
			['test.k25', 'image/x-dcraw'],
			['test.kdc', 'image/x-dcraw'],
			['test.mef', 'image/x-dcraw'],
			['test.nef', 'image/x-dcraw'],
			['test.orf', 'image/x-dcraw'],
			['test.pef', 'image/x-dcraw'],
			['test.raf', 'image/x-dcraw'],
			['test.rw2', 'image/x-dcraw'],
			['test.srf', 'image/x-dcraw'],
			['test.sr2', 'image/x-dcraw'],
			['test.xrf', 'image/x-dcraw'],
			['test.DNG', 'image/x-dcraw'],
			['test.jps', 'image/jpeg'],
			['test.MPO', 'image/jpeg'],
		];

		$fixedMimeTypes = [
			['test.doc', 'application/msword'],
			['test.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
			['test.xls', 'application/vnd.ms-excel'],
			['test.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
			['test.ppt', 'application/vnd.ms-powerpoint'],
			['test.pptx', 'application/vnd.openxmlformats-officedocument.presentationml.presentation'],
			['test.apk', 'application/vnd.android.package-archive'],
			['test.ttf', 'application/font-sfnt'],
			['test.otf', 'application/font-sfnt'],
			['test.pfb', 'application/x-font'],
			['test.eps', 'application/postscript'],
			['test.ps', 'application/postscript'],
			['test.arw', 'image/x-dcraw'],
			['test.cr2', 'image/x-dcraw'],
			['test.dcr', 'image/x-dcraw'],
			['test.dng', 'image/x-dcraw'],
			['test.erf', 'image/x-dcraw'],
			['test.iiq', 'image/x-dcraw'],
			['test.k25', 'image/x-dcraw'],
			['test.kdc', 'image/x-dcraw'],
			['test.mef', 'image/x-dcraw'],
			['test.nef', 'image/x-dcraw'],
			['test.orf', 'image/x-dcraw'],
			['test.pef', 'image/x-dcraw'],
			['test.raf', 'image/x-dcraw'],
			['test.rw2', 'image/x-dcraw'],
			['test.srf', 'image/x-dcraw'],
			['test.sr2', 'image/x-dcraw'],
			['test.xrf', 'image/x-dcraw'],
			['test.DNG', 'image/x-dcraw'],
			['test.jps', 'image/jpeg'],
			['test.MPO', 'image/jpeg'],
		];

		$this->renameMimeTypes($currentMimeTypes, $fixedMimeTypes);
	}
}

/**
 * Dummy class to access protected members
 */
class DummyFileCache extends \OC\Files\Cache\Cache {

	public static function clearCachedMimeTypes() {
		self::$mimetypeIds = [];
		self::$mimetypes = [];
	}
}

