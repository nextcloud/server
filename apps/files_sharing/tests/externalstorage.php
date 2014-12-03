<?php
/**
 * ownCloud
 *
 * @author Vincent Petry
 * @copyright 2014 Vincent Petry <pvince81@owncloud.com>
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
 *
 */

/**
 * Tests for the external Storage class for remote shares.
 */
class Test_Files_Sharing_External_Storage extends \Test\TestCase {

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

	/**
	 * @dataProvider optionsProvider
	 */
	public function testStorageMountOptions($inputUri, $baseUri) {
		$certificateManager = \OC::$server->getCertificateManager();
		$storage = new TestSharingExternalStorage(
			array(
				'remote' => $inputUri,
				'owner' => 'testOwner',
				'mountpoint' => 'remoteshare',
				'token' => 'abcdef',
				'password' => '',
				'manager' => null,
				'certificateManager' => $certificateManager
			)
		);
		$this->assertEquals($baseUri, $storage->getBaseUri());
	}
}

/**
 * Dummy subclass to make it possible to access private members
 */
class TestSharingExternalStorage extends \OCA\Files_Sharing\External\Storage {

	public function getBaseUri() {
		return $this->createBaseUri();
	}
}
