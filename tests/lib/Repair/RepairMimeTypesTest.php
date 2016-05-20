<?php
/**
 * Copyright (c) 2014 Vincent Petry <pvince81@owncloud.com>
 * Copyright (c) 2014-2015 Olivier Paroz owncloud@oparoz.com
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */
namespace Test\Repair;

use OC\Files\Storage\Temporary;
use OCP\Files\IMimeTypeLoader;
use OCP\IConfig;
use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;

/**
 * Tests for the converting of legacy storages to home storages.
 *
 * @group DB
 *
 * @see \OC\Repair\RepairMimeTypes
 */
class RepairMimeTypesTest extends \Test\TestCase {

	/** @var IRepairStep */
	private $repair;

	/** @var Temporary */
	private $storage;

	/** @var IMimeTypeLoader */
	private $mimetypeLoader;

	protected function setUp() {
		parent::setUp();

		$this->savedMimetypeLoader = \OC::$server->getMimeTypeLoader();
		$this->mimetypeLoader = \OC::$server->getMimeTypeLoader();

		/** @var IConfig | \PHPUnit_Framework_MockObject_MockObject $config */
		$config = $this->getMockBuilder('OCP\IConfig')
			->disableOriginalConstructor()
			->getMock();
		$config->expects($this->any())
			->method('getSystemValue')
			->with('version')
			->will($this->returnValue('8.0.0.0'));

		$this->storage = new \OC\Files\Storage\Temporary([]);

		$this->repair = new \OC\Repair\RepairMimeTypes($config);
	}

	protected function tearDown() {
		$this->storage->getCache()->clear();
		$sql = 'DELETE FROM `*PREFIX*storages` WHERE `id` = ?';
		\OC_DB::executeAudited($sql, [$this->storage->getId()]);
		$this->clearMimeTypes();

		parent::tearDown();
	}

	private function clearMimeTypes() {
		$sql = 'DELETE FROM `*PREFIX*mimetypes`';
		\OC_DB::executeAudited($sql);
		$this->mimetypeLoader->reset();
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

		/** @var IOutput | \PHPUnit_Framework_MockObject_MockObject $outputMock */
		$outputMock = $this->getMockBuilder('\OCP\Migration\IOutput')
			->disableOriginalConstructor()
			->getMock();

		$this->repair->run($outputMock);

		// force mimetype reload
		$this->mimetypeLoader->reset();

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

	/**
	 * Test renaming the 3D image media type
	 */
	public function testRename3dImagesMimeType() {
		$currentMimeTypes = [
			['test.jps', 'application/octet-stream'],
			['test.mpo', 'application/octet-stream'],
		];

		$fixedMimeTypes = [
			['test.jps', 'image/jpeg'],
			['test.mpo', 'image/jpeg'],
		];

		$this->renameMimeTypes($currentMimeTypes, $fixedMimeTypes);
	}

	/**
	 * Test renaming the conf/cnf media type
	 */
	public function testRenameConfMimeType() {
		$currentMimeTypes = [
			['test.conf', 'application/octet-stream'],
			['test.cnf', 'application/octet-stream'],
		];

		$fixedMimeTypes = [
			['test.conf', 'text/plain'],
			['test.cnf', 'text/plain'],
		];

		$this->renameMimeTypes($currentMimeTypes, $fixedMimeTypes);
	}

	/**
	 * Test renaming the yaml media type
	 */
	public function testRenameYamlMimeType() {
		$currentMimeTypes = [
			['test.yaml', 'application/octet-stream'],
			['test.yml', 'application/octet-stream'],
		];

		$fixedMimeTypes = [
			['test.yaml', 'application/yaml'],
			['test.yml', 'application/yaml'],
		];

		$this->renameMimeTypes($currentMimeTypes, $fixedMimeTypes);
	}

	/**
	 * Test renaming the java mime types
	 */
	public function testRenameJavaMimeType() {
		$currentMimeTypes = [
			['test.java', 'application/octet-stream'],
			['test.class', 'application/octet-stream'],
		];

		$fixedMimeTypes = [
			['test.java', 'text/x-java-source'],
			['test.class', 'application/java'],
		];

		$this->renameMimeTypes($currentMimeTypes, $fixedMimeTypes);
	}

	/**
	 * Test renaming the hpp mime type
	 */
	public function testRenameHppMimeType() {
		$currentMimeTypes = [
			['test.hpp', 'application/octet-stream'],
		];

		$fixedMimeTypes = [
			['test.hpp', 'text/x-h'],
		];

		$this->renameMimeTypes($currentMimeTypes, $fixedMimeTypes);
	}

	/**
	 * Test renaming the rss mime type
	 */
	public function testRenameRssMimeType() {
		$currentMimeTypes = [
			['test.rss', 'application/octet-stream'],
		];

		$fixedMimeTypes = [
			['test.rss', 'application/rss+xml'],
		];

		$this->renameMimeTypes($currentMimeTypes, $fixedMimeTypes);
	}

	/**
	 * Test renaming the hpp mime type
	 */
	public function testRenameRtfMimeType() {
		$currentMimeTypes = [
			['test.rtf', 'application/octet-stream'],
		];

		$fixedMimeTypes = [
			['test.rtf', 'text/rtf'],
		];

		$this->renameMimeTypes($currentMimeTypes, $fixedMimeTypes);
	}

	/**
	 * Test renaming the richdocuments additional office mime types
	 */
	public function testRenameRichDocumentsMimeTypes() {
		$currentMimeTypes = [
			['test.lwp', 'application/octet-stream'],
			['test.one', 'application/octet-stream'],
			['test.vsd', 'application/octet-stream'],
			['test.wpd', 'application/octet-stream'],
		];

		$fixedMimeTypes = [
			['test.lwp', 'application/vnd.lotus-wordpro'],
			['test.one', 'application/msonenote'],
			['test.vsd', 'application/vnd.visio'],
			['test.wpd', 'application/vnd.wordperfect'],
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
			['test.conf', 'text/plain'],
			['test.cnf', 'text/plain'],
			['test.yaml', 'application/yaml'],
			['test.yml', 'application/yaml'],
			['test.java', 'text/x-java-source'],
			['test.class', 'application/java'],
			['test.hpp', 'text/x-h'],
			['test.rss', 'application/rss+xml'],
			['test.rtf', 'text/rtf'],
			['test.lwp', 'application/vnd.lotus-wordpro'],
			['test.one', 'application/msonenote'],
			['test.vsd', 'application/vnd.visio'],
			['test.wpd', 'application/vnd.wordperfect'],
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
			['test.conf', 'text/plain'],
			['test.cnf', 'text/plain'],
			['test.yaml', 'application/yaml'],
			['test.yml', 'application/yaml'],
			['test.java', 'text/x-java-source'],
			['test.class', 'application/java'],
			['test.hpp', 'text/x-h'],
			['test.rss', 'application/rss+xml'],
			['test.rtf', 'text/rtf'],
			['test.lwp', 'application/vnd.lotus-wordpro'],
			['test.one', 'application/msonenote'],
			['test.vsd', 'application/vnd.visio'],
			['test.wpd', 'application/vnd.wordperfect'],
		];

		$this->renameMimeTypes($currentMimeTypes, $fixedMimeTypes);
	}

	/**
	 * Test that mime type renaming does not affect folders
	 */
	public function testDoNotChangeFolderMimeType() {
		$currentMimeTypes = [
			['test.conf', 'httpd/unix-directory'],
			['test.cnf', 'httpd/unix-directory'],
		];

		$fixedMimeTypes = [
			['test.conf', 'httpd/unix-directory'],
			['test.cnf', 'httpd/unix-directory'],
		];

		$this->renameMimeTypes($currentMimeTypes, $fixedMimeTypes);
	}
}

