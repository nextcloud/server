<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Joas Schilling <coding@schilljs.com>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin Appelman <robin@icewind.nl>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 * @author Vincent Petry <pvince81@owncloud.com>
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OCA\Files_Sharing\Tests;

/**
 * Tests for the external Storage class for remote shares.
 *
 * @group DB
 */
class ExternalStorageTest extends \Test\TestCase {

	function optionsProvider() {
		return array(
			array(
				'http://remoteserver:8080/owncloud',
				'http://remoteserver:8080/owncloud/public.php/webdav/',
			),
			// extra slash
			array(
				'http://remoteserver:8080/owncloud/',
				'http://remoteserver:8080/owncloud/public.php/webdav/',
			),
			// extra path
			array(
				'http://remoteserver:8080/myservices/owncloud/',
				'http://remoteserver:8080/myservices/owncloud/public.php/webdav/',
			),
			// root path
			array(
				'http://remoteserver:8080/',
				'http://remoteserver:8080/public.php/webdav/',
			),
			// without port
			array(
				'http://remoteserver/oc.test',
				'http://remoteserver/oc.test/public.php/webdav/',
			),
			// https
			array(
				'https://remoteserver/',
				'https://remoteserver/public.php/webdav/',
			),
		);
	}

	private function getTestStorage($uri) {
		$certificateManager = \OC::$server->getCertificateManager();
		return new TestSharingExternalStorage(
			array(
				'remote' => $uri,
				'owner' => 'testOwner',
				'mountpoint' => 'remoteshare',
				'token' => 'abcdef',
				'password' => '',
				'manager' => null,
				'certificateManager' => $certificateManager
			)
		);
	}

	/**
	 * @dataProvider optionsProvider
	 */
	public function testStorageMountOptions($inputUri, $baseUri) {
		$storage = $this->getTestStorage($inputUri);
		$this->assertEquals($baseUri, $storage->getBaseUri());
	}

	public function testIfTestReturnsTheValue() {
		$result = $this->getTestStorage('https://remoteserver')->test();
		$this->assertSame(true, $result);
	}
}

/**
 * Dummy subclass to make it possible to access private members
 */
class TestSharingExternalStorage extends \OCA\Files_Sharing\External\Storage {

	public function getBaseUri() {
		return $this->createBaseUri();
	}

	public function stat($path) {
		if ($path === '') {
			return true;
		}
		return parent::stat($path);
	}
}
