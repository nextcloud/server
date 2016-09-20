<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Björn Schießle <bjoern@schiessle.org>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin Appelman <robin@icewind.nl>
 * @author Robin McCorkell <robin@mccorkell.me.uk>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
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

use OC\Files\Cache\Scanner;
use OCP\AppFramework\OCS\OCSBadRequestException;
use OCP\AppFramework\OCS\OCSException;
use OCP\AppFramework\OCS\OCSForbiddenException;
use OCP\AppFramework\OCS\OCSNotFoundException;

/**
 * Class ApiTest
 *
 * @group DB
 * TODO: convert to real intergration tests
 */
class ApiTest extends TestCase {

	const TEST_FOLDER_NAME = '/folder_share_api_test';
	const APP_NAME = 'files_sharing';

	private static $tempStorage;

	/** @var \OCP\Files\Folder */
	private $userFolder;

	/** @var string */
	private $subsubfolder;

	protected function setUp() {
		parent::setUp();

		\OC::$server->getAppConfig()->setValue('core', 'shareapi_exclude_groups', 'no');
		\OC::$server->getAppConfig()->setValue('core', 'shareapi_expire_after_n_days', '7');

		$this->folder = self::TEST_FOLDER_NAME;
		$this->subfolder  = '/subfolder_share_api_test';
		$this->subsubfolder = '/subsubfolder_share_api_test';

		$this->filename = '/share-api-test.txt';

		// save file with content
		$this->view->file_put_contents($this->filename, $this->data);
		$this->view->mkdir($this->folder);
		$this->view->mkdir($this->folder . $this->subfolder);
		$this->view->mkdir($this->folder . $this->subfolder . $this->subsubfolder);
		$this->view->file_put_contents($this->folder.$this->filename, $this->data);
		$this->view->file_put_contents($this->folder . $this->subfolder . $this->filename, $this->data);
		$mount = $this->view->getMount($this->filename);
		$mount->getStorage()->getScanner()->scan('', Scanner::SCAN_RECURSIVE);

		$this->userFolder = \OC::$server->getUserFolder(self::TEST_FILES_SHARING_API_USER1);
	}

	protected function tearDown() {
		if($this->view instanceof \OC\Files\View) {
			$this->view->unlink($this->filename);
			$this->view->deleteAll($this->folder);
		}

		self::$tempStorage = null;

		parent::tearDown();
	}

	/**
	 * @param string $userId The userId of the caller
	 * @return \OCA\Files_Sharing\API\Share20OCS
	 */
	private function createOCS($userId) {
		$currentUser = \OC::$server->getUserManager()->get($userId);

		$l = $this->getMockBuilder('\OCP\IL10N')->getMock();
		$l->method('t')
			->will($this->returnCallback(function($text, $parameters = []) {
				return vsprintf($text, $parameters);
			}));

		return new \OCA\Files_Sharing\API\Share20OCS(
			self::APP_NAME,
			$this->getMockBuilder('OCP\IRequest')->getMock(),
			$this->shareManager,
			\OC::$server->getGroupManager(),
			\OC::$server->getUserManager(),
			\OC::$server->getRootFolder(),
			\OC::$server->getURLGenerator(),
			$currentUser,
			$l
		);
	}

	function testCreateShareUserFile() {
		$this->setUp(); // for some reasons phpunit refuses to do this for us only for this test
		$ocs = $this->createOCS(self::TEST_FILES_SHARING_API_USER1);
		$result = $ocs->createShare($this->filename, \OCP\Constants::PERMISSION_ALL, \OCP\Share::SHARE_TYPE_USER, self::TEST_FILES_SHARING_API_USER2);
		$ocs->cleanup();

		$data = $result->getData();
		$this->assertEquals(19, $data['permissions']);
		$this->assertEmpty($data['expiration']);

		$this->shareManager->getShareById('ocinternal:'.$data['id']);

		$ocs = $this->createOCS(self::TEST_FILES_SHARING_API_USER1);
		$ocs->deleteShare($data['id']);

		$ocs->cleanup();
	}

	function testCreateShareUserFolder() {
		$ocs = $this->createOCS(self::TEST_FILES_SHARING_API_USER1);
		$result = $ocs->createShare($this->folder, \OCP\Constants::PERMISSION_ALL, \OCP\Share::SHARE_TYPE_USER, self::TEST_FILES_SHARING_API_USER2);
		$ocs->cleanup();

		$data = $result->getData();
		$this->assertEquals(31, $data['permissions']);
		$this->assertEmpty($data['expiration']);

		$this->shareManager->getShareById('ocinternal:'.$data['id']);

		$ocs = $this->createOCS(self::TEST_FILES_SHARING_API_USER1);
		$ocs->deleteShare($data['id']);
		$ocs->cleanup();

	}


	function testCreateShareGroupFile() {
		$ocs = $this->createOCS(self::TEST_FILES_SHARING_API_USER1);
		$result = $ocs->createShare($this->filename, \OCP\Constants::PERMISSION_ALL, \OCP\Share::SHARE_TYPE_GROUP, self::TEST_FILES_SHARING_API_GROUP1);
		$ocs->cleanup();

		$data = $result->getData();
		$this->assertEquals(19, $data['permissions']);
		$this->assertEmpty($data['expiration']);

		$this->shareManager->getShareById('ocinternal:'.$data['id']);

		$ocs = $this->createOCS(self::TEST_FILES_SHARING_API_USER1);
		$ocs->deleteShare($data['id']);
		$ocs->cleanup();
	}

	function testCreateShareGroupFolder() {
		$ocs = $this->createOCS(self::TEST_FILES_SHARING_API_USER1);
		$result = $ocs->createShare($this->folder, \OCP\Constants::PERMISSION_ALL, \OCP\Share::SHARE_TYPE_GROUP, self::TEST_FILES_SHARING_API_GROUP1);
		$ocs->cleanup();

		$data = $result->getData();
		$this->assertEquals(31, $data['permissions']);
		$this->assertEmpty($data['expiration']);

		$this->shareManager->getShareById('ocinternal:'.$data['id']);

		$ocs = $this->createOCS(self::TEST_FILES_SHARING_API_USER1);
		$ocs->deleteShare($data['id']);
		$ocs->cleanup();

	}

	public function testCreateShareLink() {
		$ocs = $this->createOCS(self::TEST_FILES_SHARING_API_USER1);
		$result = $ocs->createShare($this->folder, \OCP\Constants::PERMISSION_ALL, \OCP\Share::SHARE_TYPE_LINK);
		$ocs->cleanup();

		$data = $result->getData();
		$this->assertEquals(1, $data['permissions']);
		$this->assertEmpty($data['expiration']);
		$this->assertTrue(is_string($data['token']));

		// check for correct link
		$url = \OC::$server->getURLGenerator()->getAbsoluteURL('/index.php/s/' . $data['token']);
		$this->assertEquals($url, $data['url']);

		$this->shareManager->getShareById('ocinternal:'.$data['id']);

		$ocs = $this->createOCS(self::TEST_FILES_SHARING_API_USER1);
		$ocs->deleteShare($data['id']);
		$ocs->cleanup();
	}

	public function testCreateShareLinkPublicUpload() {
		$ocs = $this->createOCS(self::TEST_FILES_SHARING_API_USER1);
		$result = $ocs->createShare($this->folder, \OCP\Constants::PERMISSION_ALL, \OCP\Share::SHARE_TYPE_LINK, null, 'true');
		$ocs->cleanup();

		$data = $result->getData();
		$this->assertEquals(
			\OCP\Constants::PERMISSION_READ |
			\OCP\Constants::PERMISSION_CREATE |
			\OCP\Constants::PERMISSION_UPDATE |
			\OCP\Constants::PERMISSION_DELETE,
			$data['permissions']
		);
		$this->assertEmpty($data['expiration']);
		$this->assertTrue(is_string($data['token']));

		// check for correct link
		$url = \OC::$server->getURLGenerator()->getAbsoluteURL('/index.php/s/' . $data['token']);
		$this->assertEquals($url, $data['url']);

		$this->shareManager->getShareById('ocinternal:'.$data['id']);

		$ocs = $this->createOCS(self::TEST_FILES_SHARING_API_USER1);
		$ocs->deleteShare($data['id']);
		$ocs->cleanup();
	}

	function testEnfoceLinkPassword() {

		$appConfig = \OC::$server->getAppConfig();
		$appConfig->setValue('core', 'shareapi_enforce_links_password', 'yes');

		$ocs = $this->createOCS(self::TEST_FILES_SHARING_API_USER1);
		try {
			$ocs->createShare($this->folder, \OCP\Constants::PERMISSION_ALL, \OCP\Share::SHARE_TYPE_LINK);
			$this->fail();
		} catch (OCSForbiddenException $e) {

		}
		$ocs->cleanup();

		$ocs = $this->createOCS(self::TEST_FILES_SHARING_API_USER1);
		try {
			$ocs->createShare($this->folder, \OCP\Constants::PERMISSION_ALL, \OCP\Share::SHARE_TYPE_LINK, null, 'false', '');
			$this->fail();
		} catch (OCSForbiddenException $e) {

		}
		$ocs->cleanup();

		// share with password should succeed
		$ocs = $this->createOCS(self::TEST_FILES_SHARING_API_USER1);
		$result = $ocs->createShare($this->folder, \OCP\Constants::PERMISSION_ALL, \OCP\Share::SHARE_TYPE_LINK, null, 'false', 'bar');
		$ocs->cleanup();

		$data = $result->getData();

		// setting new password should succeed
		$ocs = $this->createOCS(self::TEST_FILES_SHARING_API_USER1);
		$ocs->updateShare($data['id'], null, 'bar');
		$ocs->cleanup();

		// removing password should fail
		$ocs = $this->createOCS(self::TEST_FILES_SHARING_API_USER1);
		try {
			$ocs->updateShare($data['id']);
			$this->fail();
		} catch (OCSBadRequestException $e) {

		}
		$ocs->cleanup();

		// cleanup
		$ocs = $this->createOCS(self::TEST_FILES_SHARING_API_USER1);
		$ocs->deleteShare($data['id']);
		$ocs->cleanup();

		$appConfig->setValue('core', 'shareapi_enforce_links_password', 'no');
	}

	/**
	 * @medium
	*/
	function testSharePermissions() {
		// sharing file to a user should work if shareapi_exclude_groups is set
		// to no
		\OC::$server->getAppConfig()->setValue('core', 'shareapi_exclude_groups', 'no');

		$ocs = $this->createOCS(self::TEST_FILES_SHARING_API_USER1);
		$result = $ocs->createShare($this->filename, \OCP\Constants::PERMISSION_ALL, \OCP\Share::SHARE_TYPE_USER, self::TEST_FILES_SHARING_API_USER2);
		$ocs->cleanup();

		$data = $result->getData();

		$this->shareManager->getShareById('ocinternal:'.$data['id']);

		$ocs = $this->createOCS(self::TEST_FILES_SHARING_API_USER1);
		$ocs->deleteShare($data['id']);
		$ocs->cleanup();

		// exclude groups, but not the group the user belongs to. Sharing should still work
		\OC::$server->getAppConfig()->setValue('core', 'shareapi_exclude_groups', 'yes');
		\OC::$server->getAppConfig()->setValue('core', 'shareapi_exclude_groups_list', 'admin,group1,group2');

		$ocs = $this->createOCS(self::TEST_FILES_SHARING_API_USER1);
		$result = $ocs->createShare($this->filename, \OCP\Constants::PERMISSION_ALL, \OCP\Share::SHARE_TYPE_USER, self::TEST_FILES_SHARING_API_USER2);
		$ocs->cleanup();

		$data = $result->getData();

		$this->shareManager->getShareById('ocinternal:' . $data['id']);

		$ocs = $this->createOCS(self::TEST_FILES_SHARING_API_USER1);
		$ocs->deleteShare($data['id']);
		$ocs->cleanup();

		// now we exclude the group the user belongs to ('group'), sharing should fail now
		\OC::$server->getAppConfig()->setValue('core', 'shareapi_exclude_groups_list', 'admin,group');

		$ocs = $this->createOCS(self::TEST_FILES_SHARING_API_USER1);
		$ocs->createShare($this->filename, \OCP\Constants::PERMISSION_ALL, \OCP\Share::SHARE_TYPE_USER, self::TEST_FILES_SHARING_API_USER2);
		$ocs->cleanup();

		// cleanup
		\OC::$server->getAppConfig()->setValue('core', 'shareapi_exclude_groups', 'no');
		\OC::$server->getAppConfig()->setValue('core', 'shareapi_exclude_groups_list', '');
	}


	/**
	 * @medium
	 */
	function testGetAllShares() {
		$node = $this->userFolder->get($this->filename);

		$share = $this->shareManager->newShare();
		$share->setNode($node)
			->setSharedBy(self::TEST_FILES_SHARING_API_USER1)
			->setSharedWith(self::TEST_FILES_SHARING_API_USER2)
			->setShareType(\OCP\Share::SHARE_TYPE_USER)
			->setPermissions(19);

		$share = $this->shareManager->createShare($share);

		$ocs = $this->createOCS(self::TEST_FILES_SHARING_API_USER1);
		$result = $ocs->getShares();
		$ocs->cleanup();

		$this->assertTrue(count($result->getData()) === 1);

		$this->shareManager->deleteShare($share);
	}

	function testGetAllSharesWithMe() {
		$node1 = $this->userFolder->get($this->filename);
		$share1 = $this->shareManager->newShare();
		$share1->setNode($node1)
			->setSharedBy(self::TEST_FILES_SHARING_API_USER1)
			->setSharedWith(self::TEST_FILES_SHARING_API_USER2)
			->setShareType(\OCP\Share::SHARE_TYPE_USER)
			->setPermissions(19);
		$share1 = $this->shareManager->createShare($share1);

		$node2 = $this->userFolder->get($this->folder);
		$share2 = $this->shareManager->newShare();
		$share2->setNode($node2)
			->setSharedBy(self::TEST_FILES_SHARING_API_USER1)
			->setSharedWith(self::TEST_FILES_SHARING_API_USER2)
			->setShareType(\OCP\Share::SHARE_TYPE_USER)
			->setPermissions(31);
		$share2 = $this->shareManager->createShare($share2);

		$ocs = $this->createOCS(self::TEST_FILES_SHARING_API_USER2);
		$result = $ocs->getShares('true');
		$ocs->cleanup();

		$this->assertCount(2, $result->getData());

		$this->shareManager->deleteShare($share1);
		$this->shareManager->deleteShare($share2);
	}

	/**
	 * @medium
	 */
	function testPublicLinkUrl() {
		$ocs = $this->createOCS(self::TEST_FILES_SHARING_API_USER1);
		$result = $ocs->createShare($this->folder, \OCP\Constants::PERMISSION_ALL, \OCP\Share::SHARE_TYPE_LINK);
		$ocs->cleanup();

		$data = $result->getData();

		// check if we have a token
		$this->assertTrue(is_string($data['token']));
		$id = $data['id'];

		// check for correct link
		$url = \OC::$server->getURLGenerator()->getAbsoluteURL('/index.php/s/' . $data['token']);
		$this->assertEquals($url, $data['url']);

		// check for link in getall shares
		$ocs = $this->createOCS(self::TEST_FILES_SHARING_API_USER1);
		$result = $ocs->getShares();
		$ocs->cleanup();

		$data = $result->getData();
		$this->assertEquals($url, current($data)['url']);

		// check for path
		$ocs = $this->createOCS(self::TEST_FILES_SHARING_API_USER1);
		$result = $ocs->getShares();
		$ocs->cleanup();

		$data = $result->getData();
		$this->assertEquals($url, current($data)['url']);

		// check in share id
		$ocs = $this->createOCS(self::TEST_FILES_SHARING_API_USER1);
		$result = $ocs->getShare($id);
		$ocs->cleanup();

		$data = $result->getData();
		$this->assertEquals($url, current($data)['url']);

		$ocs = $this->createOCS(self::TEST_FILES_SHARING_API_USER1);
		$ocs->deleteShare($id);
		$ocs->cleanup();
	}

	/**
	 * @medium
	 * @depends testCreateShareUserFile
	 * @depends testCreateShareLink
	 */
	function testGetShareFromSource() {
		$node = $this->userFolder->get($this->filename);
		$share = $this->shareManager->newShare();
		$share->setNode($node)
			->setSharedBy(self::TEST_FILES_SHARING_API_USER1)
			->setSharedWith(self::TEST_FILES_SHARING_API_USER2)
			->setShareType(\OCP\Share::SHARE_TYPE_USER)
			->setPermissions(19);
		$share1 = $this->shareManager->createShare($share);

		$share = $this->shareManager->newShare();
		$share->setNode($node)
			->setSharedBy(self::TEST_FILES_SHARING_API_USER1)
			->setShareType(\OCP\Share::SHARE_TYPE_LINK)
			->setPermissions(1);
		$share2 = $this->shareManager->createShare($share);

		$ocs = $this->createOCS(self::TEST_FILES_SHARING_API_USER1);
		$result = $ocs->getShares();
		$ocs->cleanup();

		// test should return one share created from testCreateShare()
		$this->assertTrue(count($result->getData()) === 2);

		$this->shareManager->deleteShare($share1);
		$this->shareManager->deleteShare($share2);
	}

	/**
	 * @medium
	 * @depends testCreateShareUserFile
	 * @depends testCreateShareLink
	 */
	function testGetShareFromSourceWithReshares() {
		$node = $this->userFolder->get($this->filename);
		$share1 = $this->shareManager->newShare();
		$share1->setNode($node)
			->setSharedBy(self::TEST_FILES_SHARING_API_USER1)
			->setSharedWith(self::TEST_FILES_SHARING_API_USER2)
			->setShareType(\OCP\Share::SHARE_TYPE_USER)
			->setPermissions(19);
		$share1 = $this->shareManager->createShare($share1);

		$share2 = $this->shareManager->newShare();
		$share2->setNode($node)
			->setSharedBy(self::TEST_FILES_SHARING_API_USER2)
			->setSharedWith(self::TEST_FILES_SHARING_API_USER3)
			->setShareType(\OCP\Share::SHARE_TYPE_USER)
			->setPermissions(19);
		$share2 = $this->shareManager->createShare($share2);

		$ocs = $this->createOCS(self::TEST_FILES_SHARING_API_USER1);
		$result = $ocs->getShares();
		$ocs->cleanup();

		// test should return one share
		$this->assertTrue(count($result->getData()) === 1);

		// now also ask for the reshares
		$ocs = $this->createOCS(self::TEST_FILES_SHARING_API_USER1);
		$result = $ocs->getShares('false', 'true', 'false', $this->filename);
		$ocs->cleanup();

		// now we should get two shares, the initial share and the reshare
		$this->assertCount(2, $result->getData());

		$this->shareManager->deleteShare($share1);
		$this->shareManager->deleteShare($share2);
	}

	/**
	 * @medium
	 * @depends testCreateShareUserFile
	 */
	function testGetShareFromId() {
		$node = $this->userFolder->get($this->filename);
		$share1 = $this->shareManager->newShare();
		$share1->setNode($node)
			->setSharedBy(self::TEST_FILES_SHARING_API_USER1)
			->setSharedWith(self::TEST_FILES_SHARING_API_USER2)
			->setShareType(\OCP\Share::SHARE_TYPE_USER)
			->setPermissions(19);
		$share1 = $this->shareManager->createShare($share1);

		// call getShare() with share ID
		$ocs = $this->createOCS(self::TEST_FILES_SHARING_API_USER1);
		$result = $ocs->getShare($share1->getId());
		$ocs->cleanup();

		// test should return one share created from testCreateShare()
		$this->assertEquals(1, count($result->getData()));

		$this->shareManager->deleteShare($share1);
	}

	/**
	 * @medium
	 */
	function testGetShareFromFolder() {
		$node1 = $this->userFolder->get($this->filename);
		$share1 = $this->shareManager->newShare();
		$share1->setNode($node1)
			->setSharedBy(self::TEST_FILES_SHARING_API_USER1)
			->setSharedWith(self::TEST_FILES_SHARING_API_USER2)
			->setShareType(\OCP\Share::SHARE_TYPE_USER)
			->setPermissions(19);
		$share1 = $this->shareManager->createShare($share1);

		$node2 = $this->userFolder->get($this->folder.'/'.$this->filename);
		$share2 = $this->shareManager->newShare();
		$share2->setNode($node2)
			->setSharedBy(self::TEST_FILES_SHARING_API_USER1)
			->setShareType(\OCP\Share::SHARE_TYPE_LINK)
			->setPermissions(1);
		$share2 = $this->shareManager->createShare($share2);


		$ocs = $this->createOCS(self::TEST_FILES_SHARING_API_USER1);
		$result = $ocs->getShares('false', 'false', 'true', $this->folder);
		$ocs->cleanup();

		// test should return one share within $this->folder
		$this->assertTrue(count($result->getData()) === 1);

		$this->shareManager->deleteShare($share1);
		$this->shareManager->deleteShare($share2);
	}

	function testGetShareFromFolderWithFile() {
		$node1 = $this->userFolder->get($this->filename);
		$share1 = $this->shareManager->newShare();
		$share1->setNode($node1)
			->setSharedBy(self::TEST_FILES_SHARING_API_USER1)
			->setSharedWith(self::TEST_FILES_SHARING_API_USER2)
			->setShareType(\OCP\Share::SHARE_TYPE_USER)
			->setPermissions(19);
		$share1 = $this->shareManager->createShare($share1);

		$ocs = $this->createOCS(self::TEST_FILES_SHARING_API_USER1);
		try {
			$ocs->getShares('false', 'false', 'true', $this->filename);
			$this->fail();
		} catch (OCSBadRequestException $e) {
			$this->assertEquals('Not a directory', $e->getMessage());
		}
		$ocs->cleanup();

		$this->shareManager->deleteShare($share1);
	}

	/**
	 * share a folder, than reshare a file within the shared folder and check if we construct the correct path
	 * @medium
	 */
	function testGetShareFromFolderReshares() {
		$node1 = $this->userFolder->get($this->folder);
		$share1 = $this->shareManager->newShare();
		$share1->setNode($node1)
			->setSharedBy(self::TEST_FILES_SHARING_API_USER1)
			->setSharedWith(self::TEST_FILES_SHARING_API_USER2)
			->setShareType(\OCP\Share::SHARE_TYPE_USER)
			->setPermissions(31);
		$share1 = $this->shareManager->createShare($share1);

		$node2 = $this->userFolder->get($this->folder.'/'.$this->filename);
		$share2 = $this->shareManager->newShare();
		$share2->setNode($node2)
			->setSharedBy(self::TEST_FILES_SHARING_API_USER2)
			->setShareType(\OCP\Share::SHARE_TYPE_LINK)
			->setPermissions(1);
		$share2 = $this->shareManager->createShare($share2);

		$node3 = $this->userFolder->get($this->folder.'/'.$this->subfolder.'/'.$this->filename);
		$share3 = $this->shareManager->newShare();
		$share3->setNode($node3)
			->setSharedBy(self::TEST_FILES_SHARING_API_USER2)
			->setShareType(\OCP\Share::SHARE_TYPE_LINK)
			->setPermissions(1);
		$share3 = $this->shareManager->createShare($share3);

		$testValues=array(
			array('query' => $this->folder,
				'expectedResult' => $this->folder . $this->filename),
			array('query' => $this->folder . $this->subfolder,
				'expectedResult' => $this->folder . $this->subfolder . $this->filename),
		);
		foreach ($testValues as $value) {

				$ocs = $this->createOCS(self::TEST_FILES_SHARING_API_USER2);
			$result = $ocs->getShares('false', 'false', 'true', $value['query']);
			$ocs->cleanup();

			// test should return one share within $this->folder
			$data = $result->getData();

			$this->assertEquals($value['expectedResult'], $data[0]['path']);
		}

		// cleanup
		$this->shareManager->deleteShare($share1);
		$this->shareManager->deleteShare($share2);
		$this->shareManager->deleteShare($share3);
	}

	/**
	 * reshare a sub folder and check if we get the correct path
	 * @medium
	 */
	function testGetShareFromSubFolderReShares() {
		$node1 = $this->userFolder->get($this->folder . $this->subfolder);
		$share1 = $this->shareManager->newShare();
		$share1->setNode($node1)
			->setSharedBy(self::TEST_FILES_SHARING_API_USER1)
			->setSharedWith(self::TEST_FILES_SHARING_API_USER2)
			->setShareType(\OCP\Share::SHARE_TYPE_USER)
			->setPermissions(31);
		$share1 = $this->shareManager->createShare($share1);

		$node2 = \OC::$server->getRootFolder()->getUserFolder(self::TEST_FILES_SHARING_API_USER2)->get($this->subfolder);
		$share2 = $this->shareManager->newShare();
		$share2->setNode($node2)
			->setSharedBy(self::TEST_FILES_SHARING_API_USER2)
			->setShareType(\OCP\Share::SHARE_TYPE_LINK)
			->setPermissions(1);
		$share2 = $this->shareManager->createShare($share2);

		$ocs = $this->createOCS(self::TEST_FILES_SHARING_API_USER2);
		$result = $ocs->getShares();
		$ocs->cleanup();

		// test should return one share within $this->folder
		$data = $result->getData();

		// we should get exactly one result
		$this->assertCount(1, $data);

		$this->assertEquals($this->subfolder, $data[0]['path']);

		$this->shareManager->deleteShare($share2);
		$this->shareManager->deleteShare($share1);
	}

	/**
	 * test re-re-share of folder if the path gets constructed correctly
	 * @medium
	 */
	function XtestGetShareFromFolderReReShares() {
		$node1 = $this->userFolder->get($this->folder . $this->subfolder);
		$share1 = $this->shareManager->newShare();
		$share1->setNode($node1)
			->setSharedBy(self::TEST_FILES_SHARING_API_USER1)
			->setSharedWith(self::TEST_FILES_SHARING_API_USER2)
			->setShareType(\OCP\Share::SHARE_TYPE_USER)
			->setPermissions(31);
		$share1 = $this->shareManager->createShare($share1);

		$node2 = $this->userFolder->get($this->folder . $this->subfolder . $this->subsubfolder);
		$share2 = $this->shareManager->newShare();
		$share2->setNode($node2)
			->setSharedBy(self::TEST_FILES_SHARING_API_USER2)
			->setSharedWith(self::TEST_FILES_SHARING_API_USER3)
			->setShareType(\OCP\Share::SHARE_TYPE_USER)
			->setPermissions(31);
		$share2 = $this->shareManager->createShare($share2);

		$share3 = $this->shareManager->newShare();
		$share3->setNode($node2)
			->setSharedBy(self::TEST_FILES_SHARING_API_USER3)
			->setShareType(\OCP\Share::SHARE_TYPE_LINK)
			->setPermissions(1);
		$share3 = $this->shareManager->createShare($share3);

		/*
		 * Test as recipient
		 */
		$request = $this->createRequest(['path' => '/', 'subfiles' => 'true']);
		$ocs = $this->createOCS(self::TEST_FILES_SHARING_API_USER3);
		$result = $ocs->getShares();
		$ocs->cleanup();

		// test should return one share within $this->folder
		$data = $result->getData();

		// we should get exactly one result
		$this->assertCount(1, $data);
		$this->assertEquals($this->subsubfolder, $data[0]['path']);

		/*
		 * Test for first owner/initiator
		 */
		$ocs = $this->createOCS(self::TEST_FILES_SHARING_API_USER1);
		$result = $ocs->getShares();
		$ocs->cleanup();

		// test should return one share within $this->folder
		$data = $result->getData();

		// we should get exactly one result
		$this->assertCount(1, $data);
		$this->assertEquals($this->folder . $this->subfolder, $data[0]['path']);

		/*
		 * Test for second initiator
		 */
		$ocs = $this->createOCS(self::TEST_FILES_SHARING_API_USER2);
		$result = $ocs->getShares();
		$ocs->cleanup();

		// test should return one share within $this->folder
		$data = $result->getData();

		// we should get exactly one result
		$this->assertCount(1, $data);
		$this->assertEquals($this->subfolder . $this->subsubfolder, $data[0]['path']);

		$this->shareManager->deleteShare($share1);
		$this->shareManager->deleteShare($share2);
		$this->shareManager->deleteShare($share3);
	}

	/**
	 * test multiple shared folder if the path gets constructed correctly
	 * @medium
	 */
	function testGetShareMultipleSharedFolder() {
		$node1 = $this->userFolder->get($this->folder . $this->subfolder);
		$share1 = $this->shareManager->newShare();
		$share1->setNode($node1)
			->setSharedBy(self::TEST_FILES_SHARING_API_USER1)
			->setSharedWith(self::TEST_FILES_SHARING_API_USER2)
			->setShareType(\OCP\Share::SHARE_TYPE_USER)
			->setPermissions(31);
		$share1 = $this->shareManager->createShare($share1);

		$node2 = $this->userFolder->get($this->folder);
		$share2 = $this->shareManager->newShare();
		$share2->setNode($node2)
			->setSharedBy(self::TEST_FILES_SHARING_API_USER1)
			->setSharedWith(self::TEST_FILES_SHARING_API_USER2)
			->setShareType(\OCP\Share::SHARE_TYPE_USER)
			->setPermissions(31);
		$share2 = $this->shareManager->createShare($share2);

		$share3 = $this->shareManager->newShare();
		$share3->setNode($node1)
			->setSharedBy(self::TEST_FILES_SHARING_API_USER2)
			->setShareType(\OCP\Share::SHARE_TYPE_LINK)
			->setPermissions(1);
		$share3 = $this->shareManager->createShare($share3);

		$ocs = $this->createOCS(self::TEST_FILES_SHARING_API_USER2);
		$result1 = $ocs->getShares();
		$ocs->cleanup();

		// test should return one share within $this->folder
		$data1 = $result1->getData();
		$this->assertCount(1, $data1);
		$s1 = reset($data1);

		$ocs = $this->createOCS(self::TEST_FILES_SHARING_API_USER2);
		$result2 = $ocs->getShares();
		$ocs->cleanup();

		// test should return one share within $this->folder
		$data2 = $result2->getData();
		$this->assertCount(1, $data2);
		$s2 = reset($data2);

		$this->assertEquals($this->folder.$this->subfolder, $s1['path']);
		$this->assertEquals($this->folder.$this->subfolder, $s2['path']);

		$this->shareManager->deleteShare($share1);
		$this->shareManager->deleteShare($share2);
		$this->shareManager->deleteShare($share3);
	}

	/**
	 * test re-re-share of folder if the path gets constructed correctly
	 * @medium
	 */
	function testGetShareFromFileReReShares() {
		$node1 = $this->userFolder->get($this->folder . $this->subfolder);
		$share1 = $this->shareManager->newShare();
		$share1->setNode($node1)
			->setSharedBy(self::TEST_FILES_SHARING_API_USER1)
			->setSharedWith(self::TEST_FILES_SHARING_API_USER2)
			->setShareType(\OCP\Share::SHARE_TYPE_USER)
			->setPermissions(31);
		$share1 = $this->shareManager->createShare($share1);

		$user2Folder = \OC::$server->getUserFolder(self::TEST_FILES_SHARING_API_USER2);
		$node2 = $user2Folder->get($this->subfolder . $this->filename);
		$share2 = $this->shareManager->newShare();
		$share2->setNode($node2)
			->setSharedBy(self::TEST_FILES_SHARING_API_USER2)
			->setSharedWith(self::TEST_FILES_SHARING_API_USER3)
			->setShareType(\OCP\Share::SHARE_TYPE_USER)
			->setPermissions(19);
		$share2 = $this->shareManager->createShare($share2);

		$user3Folder = \OC::$server->getUserFolder(self::TEST_FILES_SHARING_API_USER3);
		$node3 = $user3Folder->get($this->filename);
		$share3 = $this->shareManager->newShare();
		$share3->setNode($node3)
			->setSharedBy(self::TEST_FILES_SHARING_API_USER3)
			->setShareType(\OCP\Share::SHARE_TYPE_LINK)
			->setPermissions(1);
		$share3 = $this->shareManager->createShare($share3);

		$ocs = $this->createOCS(self::TEST_FILES_SHARING_API_USER3);
		$result = $ocs->getShares();
		$ocs->cleanup();

		// test should return one share within $this->folder
		$data = $result->getData();

		// we should get exactly one result
		$this->assertCount(1, $data);

		$this->assertEquals($this->filename, $data[0]['path']);

		$this->shareManager->deleteShare($share1);
		$this->shareManager->deleteShare($share2);
		$this->shareManager->deleteShare($share3);
	}

	/**
	 * @medium
	 */
	function testGetShareFromUnknownId() {
		$ocs = $this->createOCS(self::TEST_FILES_SHARING_API_USER3);
		try {
			$ocs->getShare(0);
			$this->fail();
		} catch (OCSNotFoundException $e) {
			$this->assertEquals('Wrong share ID, share doesn\'t exist', $e->getMessage());
		}
		$ocs->cleanup();
	}

	/**
	 * @medium
	 * @depends testCreateShareUserFile
	 * @depends testCreateShareLink
	 */
	function testUpdateShare() {
		$node1 = $this->userFolder->get($this->filename);
		$share1 = $this->shareManager->newShare();
		$share1->setNode($node1)
			->setSharedBy(self::TEST_FILES_SHARING_API_USER1)
			->setSharedWith(self::TEST_FILES_SHARING_API_USER2)
			->setShareType(\OCP\Share::SHARE_TYPE_USER)
			->setPermissions(19);
		$share1 = $this->shareManager->createShare($share1);

		$share2 = $this->shareManager->newShare();
		$share2->setNode($node1)
			->setSharedBy(self::TEST_FILES_SHARING_API_USER1)
			->setShareType(\OCP\Share::SHARE_TYPE_LINK)
			->setPermissions(1);
		$share2 = $this->shareManager->createShare($share2);

		// update permissions
		$ocs = $this->createOCS(self::TEST_FILES_SHARING_API_USER1);
		$ocs->updateShare($share1->getId(), 1);
		$ocs->cleanup();

		$share1 = $this->shareManager->getShareById('ocinternal:' . $share1->getId());
		$this->assertEquals(1, $share1->getPermissions());

		// update password for link share
		$this->assertNull($share2->getPassword());

		$ocs = $this->createOCS(self::TEST_FILES_SHARING_API_USER1);
		$ocs->updateShare($share2->getId(), null, 'foo');
		$ocs->cleanup();

		$share2 = $this->shareManager->getShareById('ocinternal:' . $share2->getId());
		$this->assertNotNull($share2->getPassword());

		$ocs = $this->createOCS(self::TEST_FILES_SHARING_API_USER1);
		$ocs->updateShare($share2->getId(), null, '');
		$ocs->cleanup();

		$share2 = $this->shareManager->getShareById('ocinternal:' . $share2->getId());
		$this->assertNull($share2->getPassword());

		$this->shareManager->deleteShare($share1);
		$this->shareManager->deleteShare($share2);
	}

	/**
	 * @medium
	 * @depends testCreateShareUserFile
	 */
	public function testUpdateShareInvalidPermissions() {
		$node1 = $this->userFolder->get($this->filename);
		$share1 = $this->shareManager->newShare();
		$share1->setNode($node1)
			->setSharedBy(self::TEST_FILES_SHARING_API_USER1)
			->setSharedWith(self::TEST_FILES_SHARING_API_USER2)
			->setShareType(\OCP\Share::SHARE_TYPE_USER)
			->setPermissions(19);
		$share1 = $this->shareManager->createShare($share1);

		$ocs = $this->createOCS(self::TEST_FILES_SHARING_API_USER1);
		try {
			$ocs->updateShare($share1->getId());
			$this->fail();
		} catch (OCSBadRequestException $e) {

		}
		$ocs->cleanup();

		//Permissions should not have changed!
		$share1 = $this->shareManager->getShareById('ocinternal:' . $share1->getId());
		$this->assertEquals(19, $share1->getPermissions());

		$this->shareManager->deleteShare($share1);
	}

	/**
	 * @medium
	 */
	function testUpdateShareUpload() {
		$node1 = $this->userFolder->get($this->folder);
		$share1 = $this->shareManager->newShare();
		$share1->setNode($node1)
			->setSharedBy(self::TEST_FILES_SHARING_API_USER1)
			->setShareType(\OCP\Share::SHARE_TYPE_LINK)
			->setPermissions(1);
		$share1 = $this->shareManager->createShare($share1);

		// update public upload
		$ocs = $this->createOCS(self::TEST_FILES_SHARING_API_USER1);
		$ocs->updateShare($share1->getId(), null, null, 'true');
		$ocs->cleanup();

		$share1 = $this->shareManager->getShareById($share1->getFullId());
		$this->assertEquals(
			\OCP\Constants::PERMISSION_READ |
			\OCP\Constants::PERMISSION_CREATE |
			\OCP\Constants::PERMISSION_UPDATE |
			\OCP\Constants::PERMISSION_DELETE,
			$share1->getPermissions()
		);

		// cleanup
		$this->shareManager->deleteShare($share1);
	}

	/**
	 * @medium
	 */
	function testUpdateShareExpireDate() {
		$node1 = $this->userFolder->get($this->folder);
		$share1 = $this->shareManager->newShare();
		$share1->setNode($node1)
			->setSharedBy(self::TEST_FILES_SHARING_API_USER1)
			->setShareType(\OCP\Share::SHARE_TYPE_LINK)
			->setPermissions(1);
		$share1 = $this->shareManager->createShare($share1);

		$config = \OC::$server->getConfig();

		// enforce expire date, by default 7 days after the file was shared
		$config->setAppValue('core', 'shareapi_default_expire_date', 'yes');
		$config->setAppValue('core', 'shareapi_enforce_expire_date', 'yes');

		$dateWithinRange = new \DateTime();
		$dateWithinRange->setTime(0,0,0);
		$dateWithinRange->add(new \DateInterval('P5D'));
		$dateOutOfRange = new \DateTime();
		$dateOutOfRange->setTime(0,0,0);
		$dateOutOfRange->add(new \DateInterval('P8D'));

		// update expire date to a valid value
		$ocs = $this->createOCS(self::TEST_FILES_SHARING_API_USER1);
		$ocs->updateShare($share1->getId(), null, null, null, $dateWithinRange->format('Y-m-d'));
		$ocs->cleanup();

		$share1 = $this->shareManager->getShareById($share1->getFullId());

		// date should be changed
		$this->assertEquals($dateWithinRange, $share1->getExpirationDate());

		// update expire date to a value out of range
		$ocs = $this->createOCS(self::TEST_FILES_SHARING_API_USER1);
		try {
			$ocs->updateShare($share1->getId());
			$this->fail();
		} catch (OCSBadRequestException $e) {

		}
		$ocs->cleanup();

		$share1 = $this->shareManager->getShareById($share1->getFullId());

		// date shouldn't be changed
		$this->assertEquals($dateWithinRange, $share1->getExpirationDate());

		// Try to remove expire date
		$ocs = $this->createOCS(self::TEST_FILES_SHARING_API_USER1);
		try {
			$ocs->updateShare($share1->getId());
			$this->fail();
		} catch (OCSBadRequestException $e) {

		}
		$ocs->cleanup();

		$share1 = $this->shareManager->getShareById($share1->getFullId());


		// date shouldn't be changed
		$this->assertEquals($dateWithinRange, $share1->getExpirationDate());
		// cleanup
		$config->setAppValue('core', 'shareapi_default_expire_date', 'no');
		$config->setAppValue('core', 'shareapi_enforce_expire_date', 'no');
		$this->shareManager->deleteShare($share1);
	}

	/**
	 * @medium
	 * @depends testCreateShareUserFile
	 */
	function testDeleteShare() {
		$node1 = $this->userFolder->get($this->filename);
		$share1 = $this->shareManager->newShare();
		$share1->setNode($node1)
			->setSharedBy(self::TEST_FILES_SHARING_API_USER1)
			->setSharedWith(self::TEST_FILES_SHARING_API_USER2)
			->setShareType(\OCP\Share::SHARE_TYPE_USER)
			->setPermissions(19);
		$share1 = $this->shareManager->createShare($share1);

		$share2 = $this->shareManager->newShare();
		$share2->setNode($node1)
			->setSharedBy(self::TEST_FILES_SHARING_API_USER1)
			->setShareType(\OCP\Share::SHARE_TYPE_LINK)
			->setPermissions(1);
		$share2 = $this->shareManager->createShare($share1);

		$ocs = $this->createOCS(self::TEST_FILES_SHARING_API_USER1);
		$ocs->deleteShare($share1->getId());
		$ocs->cleanup();

		$ocs = $this->createOCS(self::TEST_FILES_SHARING_API_USER1);
		$ocs->deleteShare($share2->getId());
		$ocs->cleanup();

		$this->assertEmpty($this->shareManager->getSharesBy(self::TEST_FILES_SHARING_API_USER2, \OCP\Share::SHARE_TYPE_USER));
		$this->assertEmpty($this->shareManager->getSharesBy(self::TEST_FILES_SHARING_API_USER2, \OCP\Share::SHARE_TYPE_LINK));
	}

	/**
	 * test unshare of a reshared file
	 */
	function testDeleteReshare() {
		$node1 = $this->userFolder->get($this->folder);
		$share1 = $this->shareManager->newShare();
		$share1->setNode($node1)
			->setSharedBy(self::TEST_FILES_SHARING_API_USER1)
			->setSharedWith(self::TEST_FILES_SHARING_API_USER2)
			->setShareType(\OCP\Share::SHARE_TYPE_USER)
			->setPermissions(31);
		$share1 = $this->shareManager->createShare($share1);

		$user2folder = \OC::$server->getUserFolder(self::TEST_FILES_SHARING_API_USER2);
		$node2 = $user2folder->get($this->folder.'/'.$this->filename);
		$share2 = $this->shareManager->newShare();
		$share2->setNode($node2)
			->setSharedBy(self::TEST_FILES_SHARING_API_USER2)
			->setShareType(\OCP\Share::SHARE_TYPE_LINK)
			->setPermissions(1);
		$share2 = $this->shareManager->createShare($share2);

		// test if we can unshare the link again
		$ocs = $this->createOCS(self::TEST_FILES_SHARING_API_USER2);
		$ocs->deleteShare($share2->getId());
		$ocs->cleanup();

		$this->shareManager->deleteShare($share1);
	}

	/**
	 * share a folder which contains a share mount point, should be forbidden
	 */
	public function testShareFolderWithAMountPoint() {
		// user 1 shares a folder with user2
		self::loginHelper(self::TEST_FILES_SHARING_API_USER1);

		$share = $this->share(
			\OCP\Share::SHARE_TYPE_USER,
			$this->folder,
			self::TEST_FILES_SHARING_API_USER1,
			self::TEST_FILES_SHARING_API_USER2,
			\OCP\Constants::PERMISSION_ALL
		);

		// user2 shares a file from the folder as link
		self::loginHelper(self::TEST_FILES_SHARING_API_USER2);

		$view = new \OC\Files\View('/' . self::TEST_FILES_SHARING_API_USER2 . '/files');
		$view->mkdir("localDir");

		// move mount point to the folder "localDir"
		$result = $view->rename($this->folder, 'localDir/'.$this->folder);
		$this->assertTrue($result !== false);

		// try to share "localDir"
		$fileInfo2 = $view->getFileInfo('localDir');

		$this->assertTrue($fileInfo2 instanceof \OC\Files\FileInfo);

		$pass = true;
		try {
			$this->share(
				\OCP\Share::SHARE_TYPE_USER,
				'localDir',
				self::TEST_FILES_SHARING_API_USER2,
				self::TEST_FILES_SHARING_API_USER3,
				\OCP\Constants::PERMISSION_ALL
			);
		} catch (\Exception $e) {
			$pass = false;
		}

		$this->assertFalse($pass);

		//cleanup

		$result = $view->rename('localDir/' . $this->folder, $this->folder);
		$this->assertTrue($result !== false);
		$view->unlink('localDir');

		self::loginHelper(self::TEST_FILES_SHARING_API_USER1);

		$this->shareManager->deleteShare($share);
	}

	/**
	 * Post init mount points hook for mounting simulated ext storage
	 */
	public static function initTestMountPointsHook($data) {
		if ($data['user'] === self::TEST_FILES_SHARING_API_USER1) {
			\OC\Files\Filesystem::mount(self::$tempStorage, array(), '/' . self::TEST_FILES_SHARING_API_USER1 . '/files' . self::TEST_FOLDER_NAME);
		}
	}

	/**
	 * Tests mounting a folder that is an external storage mount point.
	 */
	public function testShareStorageMountPoint() {
		self::$tempStorage = new \OC\Files\Storage\Temporary(array());
		self::$tempStorage->file_put_contents('test.txt', 'abcdef');
		self::$tempStorage->getScanner()->scan('');

		// needed because the sharing code sometimes switches the user internally and mounts the user's
		// storages. In our case the temp storage isn't mounted automatically, so doing it in the post hook
		// (similar to how ext storage works)
		\OCP\Util::connectHook('OC_Filesystem', 'post_initMountPoints', '\OCA\Files_Sharing\Tests\ApiTest', 'initTestMountPointsHook');

		// logging in will auto-mount the temp storage for user1 as well
		self::loginHelper(self::TEST_FILES_SHARING_API_USER1);

		// user 1 shares the mount point folder with user2
		$share = $this->share(
			\OCP\Share::SHARE_TYPE_USER,
			$this->folder,
			self::TEST_FILES_SHARING_API_USER1,
			self::TEST_FILES_SHARING_API_USER2,
			\OCP\Constants::PERMISSION_ALL
		);

		// user2: check that mount point name appears correctly
		self::loginHelper(self::TEST_FILES_SHARING_API_USER2);

		$view = new \OC\Files\View('/' . self::TEST_FILES_SHARING_API_USER2 . '/files');

		$this->assertTrue($view->file_exists($this->folder));
		$this->assertTrue($view->file_exists($this->folder . '/test.txt'));

		self::loginHelper(self::TEST_FILES_SHARING_API_USER1);

		$this->shareManager->deleteShare($share);

		\OC_Hook::clear('OC_Filesystem', 'post_initMountPoints');
		\OC_Hook::clear('\OCA\Files_Sharing\Tests\ApiTest', 'initTestMountPointsHook');
	}
	/**
	 * @expectedException \Exception
	 */
	public function XtestShareNonExisting() {
		self::loginHelper(self::TEST_FILES_SHARING_API_USER1);

		$id = PHP_INT_MAX - 1;
		\OCP\Share::shareItem('file', $id, \OCP\Share::SHARE_TYPE_LINK, self::TEST_FILES_SHARING_API_USER2, 31);
	}

	/**
	 * @expectedException \Exception
	 */
	public function testShareNotOwner() {
		self::loginHelper(self::TEST_FILES_SHARING_API_USER2);
		\OC\Files\Filesystem::file_put_contents('foo.txt', 'bar');
		$info = \OC\Files\Filesystem::getFileInfo('foo.txt');

		self::loginHelper(self::TEST_FILES_SHARING_API_USER1);

		\OCP\Share::shareItem('file', $info->getId(), \OCP\Share::SHARE_TYPE_LINK, self::TEST_FILES_SHARING_API_USER2, 31);
	}

	public function testDefaultExpireDate() {
		self::loginHelper(self::TEST_FILES_SHARING_API_USER1);

		// TODO drop this once all code paths use the DI version - otherwise
		// the cache inside this config object is out of date because
		// OC_Appconfig is used and bypasses this cache which lead to integrity
		// constraint violations
		$config = \OC::$server->getConfig();
		$config->deleteAppValue('core', 'shareapi_default_expire_date');
		$config->deleteAppValue('core', 'shareapi_enforce_expire_date');
		$config->deleteAppValue('core', 'shareapi_expire_after_n_days');

		$config->setAppValue('core', 'shareapi_default_expire_date', 'yes');
		$config->setAppValue('core', 'shareapi_enforce_expire_date', 'yes');
		$config->setAppValue('core', 'shareapi_expire_after_n_days', '2');

		// default expire date is set to 2 days
		// the time when the share was created is set to 3 days in the past
		// user defined expire date is set to +2 days from now on
		// -> link should be already expired by the default expire date but the user
		//    share should still exists.
		$now = time();
		$dateFormat = 'Y-m-d H:i:s';
		$shareCreated = $now - 3 * 24 * 60 * 60;
		$expireDate = date($dateFormat, $now + 2 * 24 * 60 * 60);

		$info = \OC\Files\Filesystem::getFileInfo($this->filename);
		$this->assertTrue($info instanceof \OC\Files\FileInfo);

		$result = \OCP\Share::shareItem('file', $info->getId(), \OCP\Share::SHARE_TYPE_LINK, null, \OCP\Constants::PERMISSION_READ);
		$this->assertTrue(is_string($result));

		$result = \OCP\Share::shareItem('file', $info->getId(), \OCP\Share::SHARE_TYPE_USER, self::TEST_FILES_SHARING_API_USER2, 31);
		$this->assertTrue($result);

		$result = \OCP\Share::setExpirationDate('file', $info->getId() , $expireDate, $now);
		$this->assertTrue($result);

		//manipulate stime so that both shares are older then the default expire date
		$statement = "UPDATE `*PREFIX*share` SET `stime` = ? WHERE `share_type` = ?";
		$query = \OCP\DB::prepare($statement);
		$result = $query->execute(array($shareCreated, \OCP\Share::SHARE_TYPE_LINK));
		$this->assertSame(1, $result);
		$query = \OCP\DB::prepare($statement);
		$result = $query->execute(array($shareCreated, \OCP\Share::SHARE_TYPE_USER));
		$this->assertSame(1, $result);

		// now the link share should expire because of enforced default expire date
		// the user share should still exist
		$result = \OCP\Share::getItemShared('file', $info->getId());
		$this->assertTrue(is_array($result));
		$this->assertSame(1, count($result));
		$share = reset($result);
		$this->assertSame(\OCP\Share::SHARE_TYPE_USER, $share['share_type']);

		//cleanup
		$result = \OCP\Share::unshare('file', $info->getId(), \OCP\Share::SHARE_TYPE_USER, self::TEST_FILES_SHARING_API_USER2);
		$this->assertTrue($result);
		$config->setAppValue('core', 'shareapi_default_expire_date', 'no');
		$config->setAppValue('core', 'shareapi_enforce_expire_date', 'no');

	}

	public function datesProvider() {
		$date = new \DateTime();
		$date->add(new \DateInterval('P5D'));

		return [
			[$date->format('Y-m-d'), true],
			['abc', false],
			[$date->format('Y-m-d') . 'xyz', false],
		];
	}

	/**
	 * Make sure only ISO 8601 dates are accepted
	 *
	 * @dataProvider datesProvider
	 */
	public function testPublicLinkExpireDate($date, $valid) {
		$ocs = $this->createOCS(self::TEST_FILES_SHARING_API_USER1);

		try {
			$result = $ocs->createShare($this->folder, \OCP\Constants::PERMISSION_ALL, \OCP\Share::SHARE_TYPE_LINK, null, 'false', '', $date);
			$this->assertTrue($valid);
		} catch (OCSNotFoundException $e) {
			$this->assertFalse($valid);
			$this->assertEquals('Invalid date, date format must be YYYY-MM-DD', $e->getMessage());
			$ocs->cleanup();
			return;
		}
		$ocs->cleanup();

		$data = $result->getData();
		$this->assertTrue(is_string($data['token']));
		$this->assertEquals($date, substr($data['expiration'], 0, 10));

		// check for correct link
		$url = \OC::$server->getURLGenerator()->getAbsoluteURL('/index.php/s/' . $data['token']);
		$this->assertEquals($url, $data['url']);

		$share = $this->shareManager->getShareById('ocinternal:'.$data['id']);

		$this->assertEquals($date, $share->getExpirationDate()->format('Y-m-d'));

		$this->shareManager->deleteShare($share);
	}

	public function testCreatePublicLinkExpireDateValid() {
		$config = \OC::$server->getConfig();

		// enforce expire date, by default 7 days after the file was shared
		$config->setAppValue('core', 'shareapi_default_expire_date', 'yes');
		$config->setAppValue('core', 'shareapi_enforce_expire_date', 'yes');

		$date = new \DateTime();
		$date->add(new \DateInterval('P5D'));

		$ocs = $this->createOCS(self::TEST_FILES_SHARING_API_USER1);
		$result = $ocs->createShare($this->filename, \OCP\Constants::PERMISSION_ALL, \OCP\Share::SHARE_TYPE_LINK, null, 'false', '', $date->format('Y-m-d'));
		$ocs->cleanup();

		$data = $result->getData();
		$this->assertTrue(is_string($data['token']));
		$this->assertEquals($date->format('Y-m-d') . ' 00:00:00', $data['expiration']);

		// check for correct link
		$url = \OC::$server->getURLGenerator()->getAbsoluteURL('/index.php/s/' . $data['token']);
		$this->assertEquals($url, $data['url']);

		$share = $this->shareManager->getShareById('ocinternal:'.$data['id']);
		$date->setTime(0,0,0);
		$this->assertEquals($date, $share->getExpirationDate());

		$this->shareManager->deleteShare($share);

		$config->setAppValue('core', 'shareapi_default_expire_date', 'no');
		$config->setAppValue('core', 'shareapi_enforce_expire_date', 'no');
	}

	public function testCreatePublicLinkExpireDateInvalidFuture() {
		$config = \OC::$server->getConfig();

		// enforce expire date, by default 7 days after the file was shared
		$config->setAppValue('core', 'shareapi_default_expire_date', 'yes');
		$config->setAppValue('core', 'shareapi_enforce_expire_date', 'yes');

		$date = new \DateTime();
		$date->add(new \DateInterval('P8D'));

		$ocs = $this->createOCS(self::TEST_FILES_SHARING_API_USER1);

		try {
			$ocs->createShare($this->filename, \OCP\Constants::PERMISSION_ALL, \OCP\Share::SHARE_TYPE_LINK, null, 'false', '', $date->format('Y-m-d'));
			$this->fail();
		} catch (OCSException $e) {
			$this->assertEquals(404, $e->getCode());
			$this->assertEquals('Cannot set expiration date more than 7 days in the future', $e->getMessage());
		}
		$ocs->cleanup();

		$config->setAppValue('core', 'shareapi_default_expire_date', 'no');
		$config->setAppValue('core', 'shareapi_enforce_expire_date', 'no');
	}

	public function XtestCreatePublicLinkExpireDateInvalidPast() {
		$config = \OC::$server->getConfig();

		$date = new \DateTime();
		$date->sub(new \DateInterval('P8D'));

		$ocs = $this->createOCS(self::TEST_FILES_SHARING_API_USER1);

		try {
			$ocs->createShare($this->filename, \OCP\Constants::PERMISSION_ALL, \OCP\Share::SHARE_TYPE_LINK, null, 'false', '', $date->format('Y-m-d'));
			$this->fail();
		} catch(OCSException $e) {
			$this->assertEquals(404, $e->getCode());
			$this->assertEquals('Expiration date is in the past', $e->getMessage());
		}
		$ocs->cleanup();

		$config->setAppValue('core', 'shareapi_default_expire_date', 'no');
		$config->setAppValue('core', 'shareapi_enforce_expire_date', 'no');
	}

	/**
	 * test for no invisible shares
	 * See: https://github.com/owncloud/core/issues/22295
	 */
	public function testInvisibleSharesUser() {
		// simulate a post request
		$ocs = $this->createOCS(self::TEST_FILES_SHARING_API_USER1);
		$result = $ocs->createShare($this->folder, \OCP\Constants::PERMISSION_ALL, \OCP\Share::SHARE_TYPE_USER, self::TEST_FILES_SHARING_API_USER2);
		$ocs->cleanup();
		$data = $result->getData();

		$topId = $data['id'];

		$ocs = $this->createOCS(self::TEST_FILES_SHARING_API_USER2);
		$ocs->createShare($this->folder, \OCP\Constants::PERMISSION_ALL, \OCP\Share::SHARE_TYPE_LINK);
		$ocs->cleanup();

		$ocs = $this->createOCS(self::TEST_FILES_SHARING_API_USER1);
		$ocs->deleteShare($topId);
		$ocs->cleanup();

		$ocs = $this->createOCS(self::TEST_FILES_SHARING_API_USER1);
		$result = $ocs->getShares();
		$ocs->cleanup();

		$this->assertEmpty($result->getData());
	}

	/**
	 * test for no invisible shares
	 * See: https://github.com/owncloud/core/issues/22295
	 */
	public function testInvisibleSharesGroup() {
		// simulate a post request
		$ocs = $this->createOCS(self::TEST_FILES_SHARING_API_USER1);
		$result = $ocs->createShare($this->folder, \OCP\Constants::PERMISSION_ALL, \OCP\Share::SHARE_TYPE_GROUP, self::TEST_FILES_SHARING_API_GROUP1);
		$ocs->cleanup();
		$data = $result->getData();

		$topId = $data['id'];

		$ocs = $this->createOCS(self::TEST_FILES_SHARING_API_USER2);
		$ocs->createShare($this->folder, \OCP\Constants::PERMISSION_ALL, \OCP\Share::SHARE_TYPE_LINK);
		$ocs->cleanup();

		$ocs = $this->createOCS(self::TEST_FILES_SHARING_API_USER1);
		$ocs->deleteShare($topId);
		$ocs->cleanup();

		$ocs = $this->createOCS(self::TEST_FILES_SHARING_API_USER1);
		$result = $ocs->getShares();
		$ocs->cleanup();

		$this->assertEmpty($result->getData());
	}
}
