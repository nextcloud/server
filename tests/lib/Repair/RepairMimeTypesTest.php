<?php
/**
 * SPDX-FileCopyrightText: 2017-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Repair;

use OC\Files\Storage\Temporary;
use OC\Repair\RepairMimeTypes;
use OCP\Files\IMimeTypeLoader;
use OCP\IAppConfig;
use OCP\IConfig;
use OCP\IDBConnection;
use OCP\Migration\IOutput;
use OCP\Server;

/**
 * Tests for the converting of legacy storages to home storages.
 *
 * @group DB
 *
 * @see \OC\Repair\RepairMimeTypes
 */
class RepairMimeTypesTest extends \Test\TestCase {

	private RepairMimeTypes $repair;
	private Temporary $storage;
	private IMimeTypeLoader $mimetypeLoader;
	private IDBConnection $db;

	protected function setUp(): void {
		parent::setUp();

		$this->mimetypeLoader = Server::get(IMimeTypeLoader::class);
		$this->db = Server::get(IDBConnection::class);

		$config = $this->getMockBuilder(IConfig::class)
			->disableOriginalConstructor()
			->getMock();
		$config->method('getSystemValueString')
			->with('version')
			->willReturn('11.0.0.0');

		$appConfig = $this->getMockBuilder(IAppConfig::class)
			->disableOriginalConstructor()
			->getMock();
		$appConfig->method('getValueString')
			->with('files', 'mimetype_version')
			->willReturn('11.0.0.0');

		$this->storage = new Temporary([]);
		$this->storage->getScanner()->scan('');

		$this->repair = new RepairMimeTypes(
			$config,
			$appConfig,
			Server::get(IDBConnection::class),
		);
	}

	protected function tearDown(): void {
		$this->storage->getCache()->clear();

		$qb = $this->db->getQueryBuilder();
		$qb->delete('storages')
			->where($qb->expr()->eq('id', $qb->createNamedParameter($this->storage->getId())));
		$qb->executeStatement();

		$this->clearMimeTypes();

		parent::tearDown();
	}

	private function clearMimeTypes() {
		$qb = $this->db->getQueryBuilder();
		$qb->delete('mimetypes');
		$qb->executeStatement();

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

	private function renameMimeTypes($currentMimeTypes, $fixedMimeTypes) {
		$this->addEntries($currentMimeTypes);

		/** @var IOutput | \PHPUnit\Framework\MockObject\MockObject $outputMock */
		$outputMock = $this->getMockBuilder('\OCP\Migration\IOutput')
			->disableOriginalConstructor()
			->getMock();

		$this->repair->run($outputMock);

		// force mimetype reload
		$this->mimetypeLoader->reset();

		$this->checkEntries($fixedMimeTypes);
	}

	/**
	 * Test renaming the additional image mime types
	 */
	public function testRenameImageTypes(): void {
		$currentMimeTypes = [
			['test.jp2', 'application/octet-stream'],
			['test.webp', 'application/octet-stream'],
		];

		$fixedMimeTypes = [
			['test.jp2', 'image/jp2'],
			['test.webp', 'image/webp'],
		];

		$this->renameMimeTypes($currentMimeTypes, $fixedMimeTypes);
	}

	/**
	 * Test renaming the richdocuments additional office mime types
	 */
	public function testRenameWindowsProgramTypes(): void {
		$currentMimeTypes = [
			['test.htaccess', 'application/octet-stream'],
			['.htaccess', 'application/octet-stream'],
			['test.bat', 'application/octet-stream'],
			['test.cmd', 'application/octet-stream'],
		];

		$fixedMimeTypes = [
			['test.htaccess', 'text/plain'],
			['.htaccess', 'text/plain'],
			['test.bat', 'application/x-msdos-program'],
			['test.cmd', 'application/cmd'],
		];

		$this->renameMimeTypes($currentMimeTypes, $fixedMimeTypes);
	}

	/**
	 * Test that nothing happens and no error happens when all mimetypes are
	 * already correct and no old ones exist..
	 */
	public function testDoNothingWhenOnlyNewFiles(): void {
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
			['test.jp2', 'image/jp2'],
			['test.jps', 'image/jpeg'],
			['test.MPO', 'image/jpeg'],
			['test.webp', 'image/webp'],
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
			['test.htaccess', 'text/plain'],
			['.htaccess', 'text/plain'],
			['test.bat', 'application/x-msdos-program'],
			['test.cmd', 'application/cmd'],
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
			['test.jp2', 'image/jp2'],
			['test.jps', 'image/jpeg'],
			['test.MPO', 'image/jpeg'],
			['test.webp', 'image/webp'],
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
			['test.htaccess', 'text/plain'],
			['.htaccess', 'text/plain'],
			['test.bat', 'application/x-msdos-program'],
			['test.cmd', 'application/cmd'],
		];

		$this->renameMimeTypes($currentMimeTypes, $fixedMimeTypes);
	}

	/**
	 * Test that mime type renaming does not affect folders
	 */
	public function testDoNotChangeFolderMimeType(): void {
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
