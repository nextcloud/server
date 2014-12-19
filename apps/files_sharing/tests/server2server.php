<?php
/**
 * ownCloud - test server-to-server OCS API
 *
 * @copyright (c) ownCloud, Inc.
 *
 * @author Bjoern Schiessle <schiessle@owncloud.com>
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

use OCA\Files_Sharing\Tests\TestCase;

/**
 * Class Test_Files_Sharing_Api
 */
class Test_Files_Sharing_S2S_OCS_API extends TestCase {

	const TEST_FOLDER_NAME = '/folder_share_api_test';

	private $s2s;

	protected function setUp() {
		parent::setUp();

		self::loginHelper(self::TEST_FILES_SHARING_API_USER1);
		\OCP\Share::registerBackend('test', 'Test_Share_Backend');

		$config = $this->getMockBuilder('\OCP\IConfig')
				->disableOriginalConstructor()->getMock();
		$certificateManager = $this->getMock('\OCP\ICertificateManager');
		$httpHelperMock = $this->getMockBuilder('\OC\HTTPHelper')
				->setConstructorArgs(array($config, $certificateManager))
				->getMock();
		$httpHelperMock->expects($this->any())->method('post')->with($this->anything())->will($this->returnValue(true));

		$this->registerHttpHelper($httpHelperMock);

		$this->s2s = new \OCA\Files_Sharing\API\Server2Server();
	}

	protected function tearDown() {
		$query = \OCP\DB::prepare('DELETE FROM `*PREFIX*share_external`');
		$query->execute();

		$this->restoreHttpHelper();

		parent::tearDown();
	}

	/**
	 * Register an http helper mock for testing purposes.
	 * @param $httpHelper http helper mock
	 */
	private function registerHttpHelper($httpHelper) {
		$this->oldHttpHelper = \OC::$server->query('HTTPHelper');
		\OC::$server->registerService('HTTPHelper', function ($c) use ($httpHelper) {
			return $httpHelper;
		});
	}

	/**
	 * Restore the original http helper
	 */
	private function restoreHttpHelper() {
		$oldHttpHelper = $this->oldHttpHelper;
		\OC::$server->registerService('HTTPHelper', function ($c) use ($oldHttpHelper) {
			return $oldHttpHelper;
		});
	}

	/**
	 * @medium
	 */
	function testCreateShare() {
		// simulate a post request
		$_POST['remote'] = 'localhost';
		$_POST['token'] = 'token';
		$_POST['name'] = 'name';
		$_POST['owner'] = 'owner';
		$_POST['shareWith'] = self::TEST_FILES_SHARING_API_USER2;
		$_POST['remoteId'] = 1;

		$result = $this->s2s->createShare(null);

		$this->assertTrue($result->succeeded());

		$query = \OCP\DB::prepare('SELECT * FROM `*PREFIX*share_external` WHERE `remote_id` = ?');
		$result = $query->execute(array('1'));
		$data = $result->fetchRow();

		$this->assertSame('localhost', $data['remote']);
		$this->assertSame('token', $data['share_token']);
		$this->assertSame('/name', $data['name']);
		$this->assertSame('owner', $data['owner']);
		$this->assertSame(self::TEST_FILES_SHARING_API_USER2, $data['user']);
		$this->assertSame(1, (int)$data['remote_id']);
		$this->assertSame(0, (int)$data['accepted']);
	}


	function testDeclineShare() {
		$dummy = \OCP\DB::prepare('
			INSERT INTO `*PREFIX*share`
			(`share_type`, `uid_owner`, `item_type`, `item_source`, `item_target`, `file_source`, `file_target`, `permissions`, `stime`, `token`, `share_with`)
			VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
			');
		$dummy->execute(array(\OCP\Share::SHARE_TYPE_REMOTE, self::TEST_FILES_SHARING_API_USER1, 'test', '1', '/1', '1', '/test.txt', '1', time(), 'token', 'foo@bar'));

		$verify = \OCP\DB::prepare('SELECT * FROM `*PREFIX*share`');
		$result = $verify->execute();
		$data = $result->fetchAll();
		$this->assertSame(1, count($data));

		$_POST['token'] = 'token';
		$this->s2s->declineShare(array('id' => $data[0]['id']));

		$verify = \OCP\DB::prepare('SELECT * FROM `*PREFIX*share`');
		$result = $verify->execute();
		$data = $result->fetchAll();
		$this->assertEmpty($data);
	}
}
