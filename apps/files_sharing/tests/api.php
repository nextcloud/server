<?php
/**
 * ownCloud
 *
 * @author Bjoern Schiessle
 * @copyright 2013 Bjoern Schiessle <schiessle@owncloud.com>
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

require_once __DIR__ . '/base.php';

use OCA\Files\Share;

/**
 * Class Test_Files_Sharing_Api
 */
class Test_Files_Sharing_Api extends Test_Files_Sharing_Base {

	const TEST_FOLDER_NAME = '/folder_share_api_test';

	private static $tempStorage;

	function setUp() {
		parent::setUp();

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
	}

	function tearDown() {
		$this->view->unlink($this->filename);
		$this->view->deleteAll($this->folder);

		self::$tempStorage = null;

		parent::tearDown();
	}

	/**
	 * @medium
	 */
	function testCreateShare() {

		// share to user

		// simulate a post request
		$_POST['path'] = $this->filename;
		$_POST['shareWith'] = \Test_Files_Sharing_Api::TEST_FILES_SHARING_API_USER2;
		$_POST['shareType'] = \OCP\Share::SHARE_TYPE_USER;

		$result = Share\Api::createShare(array());

		$this->assertTrue($result->succeeded());
		$data = $result->getData();

		$share = $this->getShareFromId($data['id']);

		$items = \OCP\Share::getItemShared('file', $share['item_source']);

		$this->assertTrue(!empty($items));

		// share link

		// simulate a post request
		$_POST['path'] = $this->folder;
		$_POST['shareType'] = \OCP\Share::SHARE_TYPE_LINK;

		$result = Share\Api::createShare(array());

		// check if API call was successful
		$this->assertTrue($result->succeeded());

		$data = $result->getData();

		// check if we have a token
		$this->assertTrue(is_string($data['token']));

		$share = $this->getShareFromId($data['id']);

		$items = \OCP\Share::getItemShared('file', $share['item_source']);

		$this->assertTrue(!empty($items));

		$fileinfo = $this->view->getFileInfo($this->filename);

		\OCP\Share::unshare('file', $fileinfo['fileid'], \OCP\Share::SHARE_TYPE_USER,
				\Test_Files_Sharing_Api::TEST_FILES_SHARING_API_USER2);

		$fileinfo = $this->view->getFileInfo($this->folder);

		\OCP\Share::unshare('folder', $fileinfo['fileid'], \OCP\Share::SHARE_TYPE_LINK, null);



	}

	/**
	 * @medium
	 * @depends testCreateShare
	 */
	function testGetAllShares() {

		$fileinfo = $this->view->getFileInfo($this->filename);

		\OCP\Share::shareItem('file', $fileinfo['fileid'], \OCP\Share::SHARE_TYPE_USER,
		\Test_Files_Sharing_Api::TEST_FILES_SHARING_API_USER2, 31);

		$result = Share\Api::getAllShares(array());

		$this->assertTrue($result->succeeded());

        // test should return two shares created from testCreateShare()
		$this->assertTrue(count($result->getData()) === 1);

		\OCP\Share::unshare('file', $fileinfo['fileid'], \OCP\Share::SHARE_TYPE_USER,
				\Test_Files_Sharing_Api::TEST_FILES_SHARING_API_USER2);
	}

	/**
	 * @medium
	 * @depends testCreateShare
	 */
	function testGetShareFromSource() {

		$fileInfo = $this->view->getFileInfo($this->filename);

		\OCP\Share::shareItem('file', $fileInfo['fileid'], \OCP\Share::SHARE_TYPE_USER,
				\Test_Files_Sharing_Api::TEST_FILES_SHARING_API_USER2, 31);

		\OCP\Share::shareItem('file', $fileInfo['fileid'], \OCP\Share::SHARE_TYPE_LINK,
				null, 1);

		$_GET['path'] = $this->filename;

		$result = Share\Api::getAllShares(array());

		$this->assertTrue($result->succeeded());

        // test should return one share created from testCreateShare()
		$this->assertTrue(count($result->getData()) === 2);

		\OCP\Share::unshare('file', $fileInfo['fileid'], \OCP\Share::SHARE_TYPE_USER,
				\Test_Files_Sharing_Api::TEST_FILES_SHARING_API_USER2);

		\OCP\Share::unshare('file', $fileInfo['fileid'], \OCP\Share::SHARE_TYPE_LINK, null);

	}

	/**
	 * @medium
	 * @depends testCreateShare
	 */
	function testGetShareFromSourceWithReshares() {

		$fileInfo = $this->view->getFileInfo($this->filename);

		// share the file as user1 to user2
		\OCP\Share::shareItem('file', $fileInfo['fileid'], \OCP\Share::SHARE_TYPE_USER,
				\Test_Files_Sharing_Api::TEST_FILES_SHARING_API_USER2, 31);

		// login as user2 and reshare the file to user3
		\Test_Files_Sharing_Api::loginHelper(\Test_Files_Sharing_Api::TEST_FILES_SHARING_API_USER2);

		\OCP\Share::shareItem('file', $fileInfo['fileid'], \OCP\Share::SHARE_TYPE_USER,
				\Test_Files_Sharing_Api::TEST_FILES_SHARING_API_USER3, 31);

		// login as user1 again
		\Test_Files_Sharing_Api::loginHelper(\Test_Files_Sharing_Api::TEST_FILES_SHARING_API_USER1);

		$_GET['path'] = $this->filename;

		$result = Share\Api::getAllShares(array());

		$this->assertTrue($result->succeeded());

		// test should return one share
		$this->assertTrue(count($result->getData()) === 1);

		// now also ask for the reshares
		$_GET['reshares'] = 'true';

		$result = Share\Api::getAllShares(array());

		$this->assertTrue($result->succeeded());

		// now we should get two shares, the initial share and the reshare
		$this->assertTrue(count($result->getData()) === 2);

		// unshare files again

		\Test_Files_Sharing_Api::loginHelper(\Test_Files_Sharing_Api::TEST_FILES_SHARING_API_USER2);

		\OCP\Share::unshare('file', $fileInfo['fileid'], \OCP\Share::SHARE_TYPE_USER,
				\Test_Files_Sharing_Api::TEST_FILES_SHARING_API_USER3);

		\Test_Files_Sharing_Api::loginHelper(\Test_Files_Sharing_Api::TEST_FILES_SHARING_API_USER1);

		\OCP\Share::unshare('file', $fileInfo['fileid'], \OCP\Share::SHARE_TYPE_USER,
				\Test_Files_Sharing_Api::TEST_FILES_SHARING_API_USER2);

	}

	/**
	 * @medium
	 * @depends testCreateShare
	 */
	function testGetShareFromId() {

		$fileInfo = $this->view->getFileInfo($this->filename);

		$result = \OCP\Share::shareItem('file', $fileInfo['fileid'], \OCP\Share::SHARE_TYPE_USER,
			\Test_Files_Sharing_Api::TEST_FILES_SHARING_API_USER2, 31);

		// share was successful?
		$this->assertTrue($result);

		// get item to determine share ID
		$result = \OCP\Share::getItemShared('file', $fileInfo['fileid']);

		$this->assertEquals(1, count($result));

		// get first element
		$share = reset($result);

		// call getShare() with share ID
		$params = array('id' => $share['id']);
		$result = Share\Api::getShare($params);

		$this->assertTrue($result->succeeded());

		// test should return one share created from testCreateShare()
		$this->assertEquals(1, count($result->getData()));

		\OCP\Share::unshare('file', $fileInfo['fileid'], \OCP\Share::SHARE_TYPE_USER,
			\Test_Files_Sharing_Api::TEST_FILES_SHARING_API_USER2);

	}

	/**
	 * @medium
	 */
	function testGetShareFromFolder() {

		$fileInfo1 = $this->view->getFileInfo($this->filename);
		$fileInfo2 = $this->view->getFileInfo($this->folder.'/'.$this->filename);

		$result = \OCP\Share::shareItem('file', $fileInfo1['fileid'], \OCP\Share::SHARE_TYPE_USER,
				\Test_Files_Sharing_Api::TEST_FILES_SHARING_API_USER2, 31);

		// share was successful?
		$this->assertTrue($result);

		$result = \OCP\Share::shareItem('folder', $fileInfo2['fileid'], \OCP\Share::SHARE_TYPE_LINK,
				null, 1);

		// share was successful?
		$this->assertTrue(is_string($result));

		$_GET['path'] = $this->folder;
		$_GET['subfiles'] = 'true';

		$result = Share\Api::getAllShares(array());

		$this->assertTrue($result->succeeded());

        // test should return one share within $this->folder
		$this->assertTrue(count($result->getData()) === 1);

		\OCP\Share::unshare('file', $fileInfo1['fileid'], \OCP\Share::SHARE_TYPE_USER,
				\Test_Files_Sharing_Api::TEST_FILES_SHARING_API_USER2);

		\OCP\Share::unshare('folder', $fileInfo2['fileid'], \OCP\Share::SHARE_TYPE_LINK, null);

	}

	/**
	 * @brief share a folder, than reshare a file within the shared folder and check if we construct the correct path
	 * @medium
	 */
	function testGetShareFromFolderReshares() {

		self::loginHelper(self::TEST_FILES_SHARING_API_USER1);

		$fileInfo1 = $this->view->getFileInfo($this->folder);
		$fileInfo2 = $this->view->getFileInfo($this->folder.'/'.$this->filename);
		$fileInfo3 = $this->view->getFileInfo($this->folder.'/' . $this->subfolder . '/' .$this->filename);

		// share root folder to user2
		$result = \OCP\Share::shareItem('folder', $fileInfo1['fileid'], \OCP\Share::SHARE_TYPE_USER,
				\Test_Files_Sharing_Api::TEST_FILES_SHARING_API_USER2, 31);

		// share was successful?
		$this->assertTrue($result);

		// login as user2
		self::loginHelper(self::TEST_FILES_SHARING_API_USER2);

		// share file in root folder
		$result = \OCP\Share::shareItem('file', $fileInfo2['fileid'], \OCP\Share::SHARE_TYPE_LINK, null, 1);
		// share was successful?
		$this->assertTrue(is_string($result));

		// share file in subfolder
		$result = \OCP\Share::shareItem('file', $fileInfo3['fileid'], \OCP\Share::SHARE_TYPE_LINK, null, 1);
		// share was successful?
		$this->assertTrue(is_string($result));

		$testValues=array(
			array('query' => 'Shared/' . $this->folder,
				'expectedResult' => '/Shared' . $this->folder . $this->filename),
			array('query' => 'Shared/' . $this->folder . $this->subfolder,
				'expectedResult' => '/Shared' . $this->folder . $this->subfolder . $this->filename),
		);
		foreach ($testValues as $value) {

			$_GET['path'] = $value['query'];
			$_GET['subfiles'] = 'true';

			$result = Share\Api::getAllShares(array());

			$this->assertTrue($result->succeeded());

			// test should return one share within $this->folder
			$data = $result->getData();

			$this->assertEquals($value['expectedResult'], $data[0]['path']);
		}

		// cleanup

		\OCP\Share::unshare('file', $fileInfo2['fileid'], \OCP\Share::SHARE_TYPE_LINK, null);
		\OCP\Share::unshare('file', $fileInfo3['fileid'], \OCP\Share::SHARE_TYPE_LINK, null);

		self::loginHelper(self::TEST_FILES_SHARING_API_USER1);

		\OCP\Share::unshare('folder', $fileInfo1['fileid'], \OCP\Share::SHARE_TYPE_USER,
				\Test_Files_Sharing_Api::TEST_FILES_SHARING_API_USER2);

	}

	/**
	 * @brief reshare a sub folder and check if we get the correct path
	 * @medium
	 */
	function testGetShareFromSubFolderReShares() {

		self::loginHelper(self::TEST_FILES_SHARING_API_USER1);

		$fileInfo = $this->view->getFileInfo($this->folder . $this->subfolder);

		// share sub-folder to user2
		$result = \OCP\Share::shareItem('folder', $fileInfo['fileid'], \OCP\Share::SHARE_TYPE_USER,
				\Test_Files_Sharing_Api::TEST_FILES_SHARING_API_USER2, 31);

		// share was successful?
		$this->assertTrue($result);

		// login as user2
		self::loginHelper(self::TEST_FILES_SHARING_API_USER2);

		// reshare subfolder
		$result = \OCP\Share::shareItem('folder', $fileInfo['fileid'], \OCP\Share::SHARE_TYPE_LINK, null, 1);

		// share was successful?
		$this->assertTrue(is_string($result));

		$_GET['path'] = '/Shared';
		$_GET['subfiles'] = 'true';

		$result = Share\Api::getAllShares(array());

		$this->assertTrue($result->succeeded());

		// test should return one share within $this->folder
		$data = $result->getData();

		// we should get exactly one result
		$this->assertEquals(1, count($data));

		$expectedPath = '/Shared' . $this->subfolder;
		$this->assertEquals($expectedPath, $data[0]['path']);

		// cleanup
		$result = \OCP\Share::unshare('folder', $fileInfo['fileid'], \OCP\Share::SHARE_TYPE_LINK, null);
		$this->assertTrue($result);

		self::loginHelper(self::TEST_FILES_SHARING_API_USER1);
		$result = \OCP\Share::unshare('folder', $fileInfo['fileid'], \OCP\Share::SHARE_TYPE_USER,
				\Test_Files_Sharing_Api::TEST_FILES_SHARING_API_USER2);
		$this->assertTrue($result);

	}

	/**
	 * @brief test re-re-share of folder if the path gets constructed correctly
	 * @medium
	 */
	function testGetShareFromFolderReReShares() {

		self::loginHelper(self::TEST_FILES_SHARING_API_USER1);

		$fileInfo1 = $this->view->getFileInfo($this->folder . $this->subfolder);
		$fileInfo2 = $this->view->getFileInfo($this->folder . $this->subfolder . $this->subsubfolder);

		// share sub-folder to user2
		$result = \OCP\Share::shareItem('folder', $fileInfo1['fileid'], \OCP\Share::SHARE_TYPE_USER,
				\Test_Files_Sharing_Api::TEST_FILES_SHARING_API_USER2, 31);

		// share was successful?
		$this->assertTrue($result);

		// login as user2
		self::loginHelper(self::TEST_FILES_SHARING_API_USER2);

		// reshare subsubfolder
		$result = \OCP\Share::shareItem('folder', $fileInfo2['fileid'], \OCP\Share::SHARE_TYPE_USER,
				\Test_Files_Sharing_Api::TEST_FILES_SHARING_API_USER3, 31);
		// share was successful?
		$this->assertTrue($result);

		// login as user3
		self::loginHelper(self::TEST_FILES_SHARING_API_USER3);

		$result = \OCP\Share::shareItem('folder', $fileInfo2['fileid'], \OCP\Share::SHARE_TYPE_LINK, null, 1);
		// share was successful?
		$this->assertTrue(is_string($result));


		$_GET['path'] = '/Shared';
		$_GET['subfiles'] = 'true';

		$result = Share\Api::getAllShares(array());

		$this->assertTrue($result->succeeded());

		// test should return one share within $this->folder
		$data = $result->getData();

		// we should get exactly one result
		$this->assertEquals(1, count($data));

		$expectedPath = '/Shared' . $this->subsubfolder;
		$this->assertEquals($expectedPath, $data[0]['path']);


		// cleanup
		$result = \OCP\Share::unshare('folder', $fileInfo2['fileid'], \OCP\Share::SHARE_TYPE_LINK, null);
		$this->assertTrue($result);

		self::loginHelper(self::TEST_FILES_SHARING_API_USER2);
		$result = \OCP\Share::unshare('folder', $fileInfo2['fileid'], \OCP\Share::SHARE_TYPE_USER,
				\Test_Files_Sharing_Api::TEST_FILES_SHARING_API_USER3);
		$this->assertTrue($result);

		self::loginHelper(self::TEST_FILES_SHARING_API_USER1);
		$result = \OCP\Share::unshare('folder', $fileInfo1['fileid'], \OCP\Share::SHARE_TYPE_USER,
				\Test_Files_Sharing_Api::TEST_FILES_SHARING_API_USER2);
		$this->assertTrue($result);

	}

	/**
	 * @brief test multiple shared folder if the path gets constructed correctly
	 * @medium
	 */
	function testGetShareMultipleSharedFolder() {

		self::loginHelper(self::TEST_FILES_SHARING_API_USER1);

		$fileInfo1 = $this->view->getFileInfo($this->folder);
		$fileInfo2 = $this->view->getFileInfo($this->folder . $this->subfolder);


		// share sub-folder to user2
		$result = \OCP\Share::shareItem('folder', $fileInfo2['fileid'], \OCP\Share::SHARE_TYPE_USER,
				\Test_Files_Sharing_Api::TEST_FILES_SHARING_API_USER2, 31);

		// share was successful?
		$this->assertTrue($result);

		// share folder to user2
		$result = \OCP\Share::shareItem('folder', $fileInfo1['fileid'], \OCP\Share::SHARE_TYPE_USER,
				\Test_Files_Sharing_Api::TEST_FILES_SHARING_API_USER2, 31);

		// share was successful?
		$this->assertTrue($result);


		// login as user2
		self::loginHelper(self::TEST_FILES_SHARING_API_USER2);

		$result = \OCP\Share::shareItem('folder', $fileInfo2['fileid'], \OCP\Share::SHARE_TYPE_LINK, null, 1);
		// share was successful?
		$this->assertTrue(is_string($result));


		// ask for shared/subfolder
		$expectedPath1 = '/Shared' . $this->subfolder;
		$_GET['path'] = $expectedPath1;

		$result1 = Share\Api::getAllShares(array());

		$this->assertTrue($result1->succeeded());

		// test should return one share within $this->folder
		$data1 = $result1->getData();
		$share1 = reset($data1);

		// ask for shared/folder/subfolder
		$expectedPath2 = '/Shared' . $this->folder . $this->subfolder;
		$_GET['path'] = $expectedPath2;

		$result2 = Share\Api::getAllShares(array());

		$this->assertTrue($result2->succeeded());

		// test should return one share within $this->folder
		$data2 = $result2->getData();
		$share2 = reset($data2);


		// validate results
		// we should get exactly one result each time
		$this->assertEquals(1, count($data1));
		$this->assertEquals(1, count($data2));

		$this->assertEquals($expectedPath1, $share1['path']);
		$this->assertEquals($expectedPath2, $share2['path']);


		// cleanup
		$result = \OCP\Share::unshare('folder', $fileInfo2['fileid'], \OCP\Share::SHARE_TYPE_LINK, null);
		$this->assertTrue($result);

		self::loginHelper(self::TEST_FILES_SHARING_API_USER1);
		$result = \OCP\Share::unshare('folder', $fileInfo1['fileid'], \OCP\Share::SHARE_TYPE_USER,
				\Test_Files_Sharing_Api::TEST_FILES_SHARING_API_USER2);
		$this->assertTrue($result);
		$result = \OCP\Share::unshare('folder', $fileInfo2['fileid'], \OCP\Share::SHARE_TYPE_USER,
				\Test_Files_Sharing_Api::TEST_FILES_SHARING_API_USER2);
		$this->assertTrue($result);

	}

	/**
	 * @brief test re-re-share of folder if the path gets constructed correctly
	 * @medium
	 */
	function testGetShareFromFileReReShares() {

		self::loginHelper(self::TEST_FILES_SHARING_API_USER1);

		$fileInfo1 = $this->view->getFileInfo($this->folder . $this->subfolder);
		$fileInfo2 = $this->view->getFileInfo($this->folder. $this->subfolder . $this->filename);

		// share sub-folder to user2
		$result = \OCP\Share::shareItem('folder', $fileInfo1['fileid'], \OCP\Share::SHARE_TYPE_USER,
				\Test_Files_Sharing_Api::TEST_FILES_SHARING_API_USER2, 31);

		// share was successful?
		$this->assertTrue($result);

		// login as user2
		self::loginHelper(self::TEST_FILES_SHARING_API_USER2);

		// reshare subsubfolder
		$result = \OCP\Share::shareItem('file', $fileInfo2['fileid'], \OCP\Share::SHARE_TYPE_USER,
				\Test_Files_Sharing_Api::TEST_FILES_SHARING_API_USER3, 31);
		// share was successful?
		$this->assertTrue($result);

		// login as user3
		self::loginHelper(self::TEST_FILES_SHARING_API_USER3);

		$result = \OCP\Share::shareItem('file', $fileInfo2['fileid'], \OCP\Share::SHARE_TYPE_LINK, null, 1);
		// share was successful?
		$this->assertTrue(is_string($result));


		$_GET['path'] = '/Shared';
		$_GET['subfiles'] = 'true';

		$result = Share\Api::getAllShares(array());

		$this->assertTrue($result->succeeded());

		// test should return one share within $this->folder
		$data = $result->getData();

		// we should get exactly one result
		$this->assertEquals(1, count($data));

		$expectedPath = '/Shared' . $this->filename;
		$this->assertEquals($expectedPath, $data[0]['path']);


		// cleanup
		$result = \OCP\Share::unshare('file', $fileInfo2['fileid'], \OCP\Share::SHARE_TYPE_LINK, null);
		$this->assertTrue($result);

		self::loginHelper(self::TEST_FILES_SHARING_API_USER2);
		$result = \OCP\Share::unshare('file', $fileInfo2['fileid'], \OCP\Share::SHARE_TYPE_USER,
				\Test_Files_Sharing_Api::TEST_FILES_SHARING_API_USER3);
		$this->assertTrue($result);

		self::loginHelper(self::TEST_FILES_SHARING_API_USER1);
		$result = \OCP\Share::unshare('folder', $fileInfo1['fileid'], \OCP\Share::SHARE_TYPE_USER,
				\Test_Files_Sharing_Api::TEST_FILES_SHARING_API_USER2);
		$this->assertTrue($result);

	}

	/**
	 * @medium
	 */
	function testGetShareFromUnknownId() {

		$params = array('id' => 0);

		$result = Share\Api::getShare($params);

		$this->assertEquals(404, $result->getStatusCode());
        $meta = $result->getMeta();
		$this->assertEquals('share doesn\'t exist', $meta['message']);

	}

	/**
	 * @medium
	 * @depends testCreateShare
	 */
	function testUpdateShare() {

		$fileInfo = $this->view->getFileInfo($this->filename);

		$result = \OCP\Share::shareItem('file', $fileInfo['fileid'], \OCP\Share::SHARE_TYPE_USER,
				\Test_Files_Sharing_Api::TEST_FILES_SHARING_API_USER2, 31);

		// share was successful?
		$this->assertTrue($result);

		$result = \OCP\Share::shareItem('file', $fileInfo['fileid'], \OCP\Share::SHARE_TYPE_LINK,
				null, 1);

		// share was successful?
		$this->assertTrue(is_string($result));

		$items = \OCP\Share::getItemShared('file', null);

		// make sure that we found a link share and a user share
		$this->assertEquals(count($items), 2);

		$linkShare = null;
		$userShare = null;

		foreach ($items as $item) {
			if ($item['share_type'] === \OCP\Share::SHARE_TYPE_LINK) {
				$linkShare = $item;
			}
			if ($item['share_type'] === \OCP\Share::SHARE_TYPE_USER) {
				$userShare = $item;
			}
		}

		// make sure that we found a link share and a user share
		$this->assertTrue(is_array($linkShare));
		$this->assertTrue(is_array($userShare));

		// update permissions

		$this->assertEquals('31', $userShare['permissions']);

		$params = array();
		$params['id'] = $userShare['id'];
		$params['_put'] = array();
		$params['_put']['permissions'] = 1;

		$result = Share\Api::updateShare($params);

        $meta = $result->getMeta();
		$this->assertTrue($result->succeeded(), $meta['message']);

		$items = \OCP\Share::getItemShared('file', $userShare['file_source']);

		$newUserShare = null;
		foreach ($items as $item) {
			if ($item['share_with'] === \Test_Files_Sharing_Api::TEST_FILES_SHARING_API_USER2) {
				$newUserShare = $item;
				break;
			}
		}

		$this->assertTrue(is_array($newUserShare));

		$this->assertEquals('1', $newUserShare['permissions']);

		// update password for link share

		$this->assertTrue(empty($linkShare['share_with']));

		$params = array();
		$params['id'] = $linkShare['id'];
		$params['_put'] = array();
		$params['_put']['password'] = 'foo';

		$result = Share\Api::updateShare($params);

		$this->assertTrue($result->succeeded());

		$items = \OCP\Share::getItemShared('file', $linkShare['file_source']);

		$newLinkShare = null;
		foreach ($items as $item) {
			if ($item['share_type'] === \OCP\Share::SHARE_TYPE_LINK) {
				$newLinkShare = $item;
				break;
			}
		}

		$this->assertTrue(is_array($newLinkShare));
		$this->assertTrue(!empty($newLinkShare['share_with']));

		\OCP\Share::unshare('file', $fileInfo['fileid'], \OCP\Share::SHARE_TYPE_USER,
				\Test_Files_Sharing_Api::TEST_FILES_SHARING_API_USER2);

		\OCP\Share::unshare('file', $fileInfo['fileid'], \OCP\Share::SHARE_TYPE_LINK, null);

	}

	/**
	 * @medium
	 */
	function testUpdateShareUpload() {

		$fileInfo = $this->view->getFileInfo($this->folder);

		$result = \OCP\Share::shareItem('folder', $fileInfo['fileid'], \OCP\Share::SHARE_TYPE_LINK,
				null, 1);

		// share was successful?
		$this->assertTrue(is_string($result));

		$items = \OCP\Share::getItemShared('file', null);

		// make sure that we found a link share and a user share
		$this->assertEquals(count($items), 1);

		$linkShare = null;

		foreach ($items as $item) {
			if ($item['share_type'] === \OCP\Share::SHARE_TYPE_LINK) {
				$linkShare = $item;
			}
		}

		// make sure that we found a link share
		$this->assertTrue(is_array($linkShare));

		// update public upload

		$params = array();
		$params['id'] = $linkShare['id'];
		$params['_put'] = array();
		$params['_put']['publicUpload'] = 'true';

		$result = Share\Api::updateShare($params);

		$this->assertTrue($result->succeeded());

		$items = \OCP\Share::getItemShared('file', $linkShare['file_source']);

		$updatedLinkShare = null;
		foreach ($items as $item) {
			if ($item['share_type'] === \OCP\Share::SHARE_TYPE_LINK) {
				$updatedLinkShare = $item;
				break;
			}
		}

		$this->assertTrue(is_array($updatedLinkShare));
		$this->assertEquals(7, $updatedLinkShare['permissions']);

		// cleanup

		\OCP\Share::unshare('file', $fileInfo['fileid'], \OCP\Share::SHARE_TYPE_LINK, null);

	}

	/**
	 * @medium
	 * @depends testCreateShare
	 */
	function testDeleteShare() {

		$fileInfo = $this->view->getFileInfo($this->filename);

		\OCP\Share::shareItem('file', $fileInfo['fileid'], \OCP\Share::SHARE_TYPE_USER,
				\Test_Files_Sharing_Api::TEST_FILES_SHARING_API_USER2, 31);

		\OCP\Share::shareItem('file', $fileInfo['fileid'], \OCP\Share::SHARE_TYPE_LINK,
				null, 1);

		$items = \OCP\Share::getItemShared('file', null);

		$this->assertEquals(2, count($items));

		foreach ($items as $item) {
			$result = Share\Api::deleteShare(array('id' => $item['id']));

			$this->assertTrue($result->succeeded());
		}

		$itemsAfterDelete = \OCP\Share::getItemShared('file', null);

		$this->assertTrue(empty($itemsAfterDelete));

	}

	/**
	 * @brief test unshare of a reshared file
	 */
	function testDeleteReshare() {

		// user 1 shares a folder with user2
		\Test_Files_Sharing_Api::loginHelper(\Test_Files_Sharing_Api::TEST_FILES_SHARING_API_USER1);

		$fileInfo1 = $this->view->getFileInfo($this->folder);
		$fileInfo2 = $this->view->getFileInfo($this->folder.'/'.$this->filename);

		$result1 = \OCP\Share::shareItem('folder', $fileInfo1['fileid'], \OCP\Share::SHARE_TYPE_USER,
				\Test_Files_Sharing_Api::TEST_FILES_SHARING_API_USER2, 31);

		$this->assertTrue($result1);

		// user2 shares a file from the folder as link
		\Test_Files_Sharing_Api::loginHelper(\Test_Files_Sharing_Api::TEST_FILES_SHARING_API_USER2);

		$result2 = \OCP\Share::shareItem('file', $fileInfo2['fileid'], \OCP\Share::SHARE_TYPE_LINK, null, 1);

		$this->assertTrue(is_string($result2));

		// test if we can unshare the link again
		$items = \OCP\Share::getItemShared('file', null);
		$this->assertEquals(1, count($items));

		$item = reset($items);
		$result3 = Share\Api::deleteShare(array('id' => $item['id']));

		$this->assertTrue($result3->succeeded());

	}

	function testCorrectPath() {
		$path = "/foo/bar/test.txt";
		$folder = "/correct/path";
		$expectedResult = "/correct/path/test.txt";

		$shareApiDummy = new TestShareApi();

		$this->assertSame($expectedResult, $shareApiDummy->correctPathTest($path, $folder));
	}

	/**
	 * Post init mount points hook for mounting simulated ext storage
	 */
	public static function initTestMountPointsHook($data) {
		if ($data['user'] === \Test_Files_Sharing_Api::TEST_FILES_SHARING_API_USER1) {
			\OC\Files\Filesystem::mount(self::$tempStorage, array(), '/' . \Test_Files_Sharing_Api::TEST_FILES_SHARING_API_USER1 . '/files' . self::TEST_FOLDER_NAME);
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
		OCP\Util::connectHook('OC_Filesystem', 'post_initMountPoints', '\Test_Files_Sharing_Api', 'initTestMountPointsHook');

		// logging in will auto-mount the temp storage for user1 as well
		\Test_Files_Sharing_Api::loginHelper(\Test_Files_Sharing_Api::TEST_FILES_SHARING_API_USER1);

		$fileInfo = $this->view->getFileInfo($this->folder);

		// user 1 shares the mount point folder with user2
		$result = \OCP\Share::shareItem('folder', $fileInfo['fileid'], \OCP\Share::SHARE_TYPE_USER,
				\Test_Files_Sharing_Api::TEST_FILES_SHARING_API_USER2, 31);

		$this->assertTrue($result);

		// user2: check that mount point name appears correctly
		\Test_Files_Sharing_Api::loginHelper(\Test_Files_Sharing_Api::TEST_FILES_SHARING_API_USER2);

		$view = new \OC\Files\View('/' . \Test_Files_Sharing_Api::TEST_FILES_SHARING_API_USER2 . '/files/Shared');

		$this->assertTrue($view->file_exists($this->folder));
		$this->assertTrue($view->file_exists($this->folder . '/test.txt'));

		\Test_Files_Sharing_Api::loginHelper(\Test_Files_Sharing_Api::TEST_FILES_SHARING_API_USER1);

		\OCP\Share::unshare('folder', $fileInfo['fileid'], \OCP\Share::SHARE_TYPE_USER,
			\Test_Files_Sharing_Api::TEST_FILES_SHARING_API_USER2);

		\OC_Hook::clear('OC_Filesystem', 'post_initMountPoints', '\Test_Files_Sharing_Api', 'initTestMountPointsHook');
	}

}

/**
 * @brief dumnmy class to test protected methods
 */
class TestShareApi extends \OCA\Files\Share\Api {
	public function correctPathTest($path, $folder) {
		return self::correctPath($path, $folder);
}
}
