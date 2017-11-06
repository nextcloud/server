<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
 * @author Bjoern Schiessle <bjoern@schiessle.org>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Robin Appelman <robin@icewind.nl>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
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

namespace OCA\Files_Sharing\Tests\Controller;

use OCA\Files_Sharing\Controller\ShareesAPIController;
use OCA\Files_Sharing\Tests\TestCase;
use OCP\AppFramework\Http;
use OCP\AppFramework\OCS\OCSBadRequestException;
use OCP\Collaboration\Collaborators\ISearch;
use OCP\IConfig;
use OCP\IRequest;
use OCP\IURLGenerator;
use OCP\Share;
use OCP\Share\IManager;

/**
 * Class ShareesTest
 *
 * @group DB
 *
 * @package OCA\Files_Sharing\Tests\API
 */
class ShareesAPIControllerTest extends TestCase {
	/** @var ShareesAPIController */
	protected $sharees;

	/** @var IRequest|\PHPUnit_Framework_MockObject_MockObject */
	protected $request;

	/** @var IManager|\PHPUnit_Framework_MockObject_MockObject */
	protected $shareManager;

	/** @var  ISearch|\PHPUnit_Framework_MockObject_MockObject */
	protected $collaboratorSearch;

	protected function setUp() {
		parent::setUp();

		$this->request = $this->createMock(IRequest::class);
		$this->shareManager = $this->createMock(IManager::class);

		/** @var IConfig|\PHPUnit_Framework_MockObject_MockObject $configMock */
		$configMock = $this->createMock(IConfig::class);

		/** @var IURLGenerator|\PHPUnit_Framework_MockObject_MockObject $urlGeneratorMock */
		$urlGeneratorMock = $this->createMock(IURLGenerator::class);

		$this->collaboratorSearch = $this->createMock(ISearch::class);

		$this->sharees = new ShareesAPIController(
			'files_sharing',
			$this->request,
			$configMock,
			$urlGeneratorMock,
			$this->shareManager,
			$this->collaboratorSearch
		);
	}

	public function dataSearch() {
		$noRemote = [Share::SHARE_TYPE_USER, Share::SHARE_TYPE_GROUP, Share::SHARE_TYPE_EMAIL];
		$allTypes = [Share::SHARE_TYPE_USER, Share::SHARE_TYPE_GROUP, Share::SHARE_TYPE_REMOTE, Share::SHARE_TYPE_EMAIL];

		return [
			[[], '', 'yes', true, true, $noRemote, false, true, true],

			// Test itemType
			[[
				'search' => '',
			], '', 'yes', true, true, $noRemote, false, true, true],
			[[
				'search' => 'foobar',
			], '', 'yes', true, true, $noRemote, false, true, true],
			[[
				'search' => 0,
			], '', 'yes', true, true, $noRemote, false, true, true],

			// Test itemType
			[[
				'itemType' => '',
			], '', 'yes', true, true, $noRemote, false, true, true],
			[[
				'itemType' => 'folder',
			], '', 'yes', true, true, $allTypes, false, true, true],
			[[
				'itemType' => 0,
			], '', 'yes', true, true, $noRemote, false, true, true],

			// Test shareType
			[[
				'itemType' => 'call',
			], '', 'yes', true, true, $noRemote, false, true, true],
			[[
				'itemType' => 'folder',
			], '', 'yes', true, true, $allTypes, false, true, true],
			[[
				'itemType' => 'folder',
				'shareType' => 0,
			], '', 'yes', true, false, [0], false, true, true],
			[[
				'itemType' => 'folder',
				'shareType' => '0',
			], '', 'yes', true, false, [0], false, true, true],
			[[
				'itemType' => 'folder',
				'shareType' => 1,
			], '', 'yes', true, false, [1], false, true, true],
			[[
				'itemType' => 'folder',
				'shareType' => 12,
			], '', 'yes', true, false, [], false, true, true],
			[[
				'itemType' => 'folder',
				'shareType' => 'foobar',
			], '', 'yes', true, true, $allTypes, false, true, true],
			[[
				'itemType' => 'folder',
				'shareType' => [0, 1, 2],
			], '', 'yes', false, false, [0, 1], false, true, true],
			[[
				'itemType' => 'folder',
				'shareType' => [0, 1],
			], '', 'yes', false, false, [0, 1], false, true, true],
			[[
				'itemType' => 'folder',
				'shareType' => $allTypes,
			], '', 'yes', true, true, $allTypes, false, true, true],
			[[
				'itemType' => 'folder',
				'shareType' => $allTypes,
			], '', 'yes', false, false, [0, 1], false, true, true],
			[[
				'itemType' => 'folder',
				'shareType' => $allTypes,
			], '', 'yes', true, false, [0, 6], false, true, false],
			[[
				'itemType' => 'folder',
				'shareType' => $allTypes,
			], '', 'yes', false, true, [0, 4], false, true, false],

			// Test pagination
			[[
				'itemType' => 'folder',
				'page' => 1,
			], '', 'yes', true, true, $allTypes, false, true, true],
			[[
				'itemType' => 'folder',
				'page' => 10,
			], '', 'yes', true, true, $allTypes, false, true, true],

			// Test perPage
			[[
				'itemType' => 'folder',
				'perPage' => 1,
			], '', 'yes', true, true, $allTypes, false, true, true],
			[[
				'itemType' => 'folder',
				'perPage' => 10,
			], '', 'yes', true, true, $allTypes, false, true, true],

			// Test $shareWithGroupOnly setting
			[[
				'itemType' => 'folder',
			], 'no', 'yes',  true, true, $allTypes, false, true, true],
			[[
				'itemType' => 'folder',
			], 'yes', 'yes', true, true, $allTypes, true, true, true],

			// Test $shareeEnumeration setting
			[[
				'itemType' => 'folder',
			], 'no', 'yes',  true, true, $allTypes, false, true, true],
			[[
				'itemType' => 'folder',
			], 'no', 'no', true, true, $allTypes, false, false, true],
		];
	}

	/**
	 * @dataProvider dataSearch
	 *
	 * @param array $getData
	 * @param string $apiSetting
	 * @param string $enumSetting
	 * @param bool $remoteSharingEnabled
	 * @param bool $emailSharingEnabled
	 * @param array $shareTypes
	 * @param bool $shareWithGroupOnly
	 * @param bool $shareeEnumeration
	 * @param bool $allowGroupSharing
	 */
	public function testSearch($getData, $apiSetting, $enumSetting, $remoteSharingEnabled, $emailSharingEnabled, $shareTypes, $shareWithGroupOnly, $shareeEnumeration, $allowGroupSharing) {
		$search = isset($getData['search']) ? $getData['search'] : '';
		$itemType = isset($getData['itemType']) ? $getData['itemType'] : 'irrelevant';
		$page = isset($getData['page']) ? $getData['page'] : 1;
		$perPage = isset($getData['perPage']) ? $getData['perPage'] : 200;
		$shareType = isset($getData['shareType']) ? $getData['shareType'] : null;

		/** @var IConfig|\PHPUnit_Framework_MockObject_MockObject $config */
		$config = $this->createMock(IConfig::class);
		$config->expects($this->exactly(2))
			->method('getAppValue')
			->with('core', $this->anything(), $this->anything())
			->willReturnMap([
				['core', 'shareapi_only_share_with_group_members', 'no', $apiSetting],
				['core', 'shareapi_allow_share_dialog_user_enumeration', 'yes', $enumSetting],
			]);

		$this->shareManager->expects($itemType === 'file' || $itemType === 'folder' ? $this->once() : $this->never())
			->method('allowGroupSharing')
			->willReturn($allowGroupSharing);

		/** @var IRequest|\PHPUnit_Framework_MockObject_MockObject $request */
		$request = $this->createMock(IRequest::class);
		/** @var IURLGenerator|\PHPUnit_Framework_MockObject_MockObject $urlGenerator */
		$urlGenerator = $this->createMock(IURLGenerator::class);

		/** @var \PHPUnit_Framework_MockObject_MockObject|\OCA\Files_Sharing\Controller\ShareesAPIController $sharees */
		$sharees = $this->getMockBuilder('\OCA\Files_Sharing\Controller\ShareesAPIController')
			->setConstructorArgs([
				'files_sharing',
				$request,
				$config,
				$urlGenerator,
				$this->shareManager,
				$this->collaboratorSearch
			])
			->setMethods(['isRemoteSharingAllowed', 'shareProviderExists'])
			->getMock();

		$this->collaboratorSearch->expects($this->once())
			->method('search')
			->with($search, $shareTypes, $this->anything(), $perPage, $perPage * ($page -1))
			->willReturn([[], false]);

		$sharees->expects($this->any())
			->method('isRemoteSharingAllowed')
			->with($itemType)
			->willReturn($remoteSharingEnabled);

		$this->shareManager->expects($this->any())
			->method('shareProviderExists')
			->with(\OCP\Share::SHARE_TYPE_EMAIL)
			->willReturn($emailSharingEnabled);

		$this->assertInstanceOf(Http\DataResponse::class, $sharees->search($search, $itemType, $page, $perPage, $shareType));

		$this->assertSame($shareWithGroupOnly, $this->invokePrivate($sharees, 'shareWithGroupOnly'));
		$this->assertSame($shareeEnumeration, $this->invokePrivate($sharees, 'shareeEnumeration'));
	}

	public function dataSearchInvalid() {
		return [
			// Test invalid pagination
			[[
				'page' => 0,
			], 'Invalid page'],
			[[
				'page' => '0',
			], 'Invalid page'],
			[[
				'page' => -1,
			], 'Invalid page'],

			// Test invalid perPage
			[[
				'perPage' => 0,
			], 'Invalid perPage argument'],
			[[
				'perPage' => '0',
			], 'Invalid perPage argument'],
			[[
				'perPage' => -1,
			], 'Invalid perPage argument'],
		];
	}

	/**
	 * @dataProvider dataSearchInvalid
	 *
	 * @param array $getData
	 * @param string $message
	 */
	public function testSearchInvalid($getData, $message) {
		$page = isset($getData['page']) ? $getData['page'] : 1;
		$perPage = isset($getData['perPage']) ? $getData['perPage'] : 200;

		/** @var IConfig|\PHPUnit_Framework_MockObject_MockObject $config */
		$config = $this->createMock(IConfig::class);
		$config->expects($this->never())
			->method('getAppValue');

		/** @var IRequest|\PHPUnit_Framework_MockObject_MockObject $request */
		$request = $this->createMock(IRequest::class);
		/** @var IURLGenerator|\PHPUnit_Framework_MockObject_MockObject $urlGenerator */
		$urlGenerator = $this->createMock(IURLGenerator::class);

		/** @var \PHPUnit_Framework_MockObject_MockObject|\OCA\Files_Sharing\Controller\ShareesAPIController $sharees */
		$sharees = $this->getMockBuilder('\OCA\Files_Sharing\Controller\ShareesAPIController')
			->setConstructorArgs([
				'files_sharing',
				$request,
				$config,
				$urlGenerator,
				$this->shareManager,
				$this->collaboratorSearch
			])
			->setMethods(['isRemoteSharingAllowed'])
			->getMock();
		$sharees->expects($this->never())
			->method('isRemoteSharingAllowed');

		$this->collaboratorSearch->expects($this->never())
			->method('search');

		try {
			$sharees->search('', null, $page, $perPage, null);
			$this->fail();
		} catch (OCSBadRequestException $e) {
			$this->assertEquals($message, $e->getMessage());
		}
	}

	public function dataIsRemoteSharingAllowed() {
		return [
			['file', true],
			['folder', true],
			['', false],
			['contacts', false],
		];
	}

	/**
	 * @dataProvider dataIsRemoteSharingAllowed
	 *
	 * @param string $itemType
	 * @param bool $expected
	 */
	public function testIsRemoteSharingAllowed($itemType, $expected) {
		$this->assertSame($expected, $this->invokePrivate($this->sharees, 'isRemoteSharingAllowed', [$itemType]));
	}

	/**
	 * @expectedException \OCP\AppFramework\OCS\OCSBadRequestException
	 * @expectedExceptionMessage Missing itemType
	 */
	public function testSearchNoItemType() {
		$this->sharees->search('', null, 1, 10, [], false);
	}

	public function dataGetPaginationLink() {
		return [
			[1, '/ocs/v1.php', ['perPage' => 2], '<?perPage=2&page=2>; rel="next"'],
			[10, '/ocs/v2.php', ['perPage' => 2], '<?perPage=2&page=11>; rel="next"'],
		];
	}

	/**
	 * @dataProvider dataGetPaginationLink
	 *
	 * @param int $page
	 * @param string $scriptName
	 * @param array $params
	 * @param array $expected
	 */
	public function testGetPaginationLink($page, $scriptName, $params, $expected) {
		$this->request->expects($this->once())
			->method('getScriptName')
			->willReturn($scriptName);

		$this->assertEquals($expected, $this->invokePrivate($this->sharees, 'getPaginationLink', [$page, $params]));
	}

	public function dataIsV2() {
		return [
			['/ocs/v1.php', false],
			['/ocs/v2.php', true],
		];
	}

	/**
	 * @dataProvider dataIsV2
	 *
	 * @param string $scriptName
	 * @param bool $expected
	 */
	public function testIsV2($scriptName, $expected) {
		$this->request->expects($this->once())
			->method('getScriptName')
			->willReturn($scriptName);

		$this->assertEquals($expected, $this->invokePrivate($this->sharees, 'isV2'));
	}
}
