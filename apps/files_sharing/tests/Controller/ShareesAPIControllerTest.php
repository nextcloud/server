<?php
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Files_Sharing\Tests\Controller;

use OCA\Files_Sharing\Controller\ShareesAPIController;
use OCA\Files_Sharing\Tests\TestCase;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\OCS\OCSBadRequestException;
use OCP\Collaboration\Collaborators\ISearch;
use OCP\IConfig;
use OCP\IRequest;
use OCP\IURLGenerator;
use OCP\Share\IManager;
use OCP\Share\IShare;
use PHPUnit\Framework\MockObject\MockObject;

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

	/** @var string */
	protected $uid;

	/** @var IRequest|MockObject */
	protected $request;

	/** @var IManager|MockObject */
	protected $shareManager;

	/** @var ISearch|MockObject */
	protected $collaboratorSearch;

	/** @var IConfig|MockObject */
	protected $config;

	protected function setUp(): void {
		parent::setUp();

		$this->uid = 'test123';
		$this->request = $this->createMock(IRequest::class);
		$this->shareManager = $this->createMock(IManager::class);
		$this->config = $this->createMock(IConfig::class);

		/** @var IURLGenerator|MockObject $urlGeneratorMock */
		$urlGeneratorMock = $this->createMock(IURLGenerator::class);

		$this->collaboratorSearch = $this->createMock(ISearch::class);

		$this->sharees = new ShareesAPIController(
			'files_sharing',
			$this->request,
			$this->uid,
			$this->config,
			$urlGeneratorMock,
			$this->shareManager,
			$this->collaboratorSearch
		);
	}

	public function dataSearch(): array {
		$noRemote = [IShare::TYPE_USER, IShare::TYPE_GROUP, IShare::TYPE_EMAIL];
		$allTypes = [IShare::TYPE_USER, IShare::TYPE_GROUP, IShare::TYPE_REMOTE, IShare::TYPE_REMOTE_GROUP, IShare::TYPE_EMAIL];

		return [
			[[], '', 'yes', false, true, true, true, $noRemote, false, true, true],

			// Test itemType
			[[
				'search' => '',
			], '', 'yes', false, true, true, true, $noRemote, false, true, true],
			[[
				'search' => 'foobar',
			], '', 'yes', false, true, true, true, $noRemote, false, true, true],
			[[
				'search' => 0,
			], '', 'yes', false, true, true, true, $noRemote, false, true, true],

			// Test itemType
			[[
				'itemType' => '',
			], '', 'yes', false, true, true, true, $noRemote, false, true, true],
			[[
				'itemType' => 'folder',
			], '', 'yes', false, true, true, true, $allTypes, false, true, true],
			[[
				'itemType' => 0,
			], '', 'yes', false, true, true , true, $noRemote, false, true, true],
			// Test shareType
			[[
				'itemType' => 'call',
			], '', 'yes', false, true, true, true, $noRemote, false, true, true],
			[[
				'itemType' => 'call',
			], '', 'yes', false, true, true, true, [0, 4], false, true, false],
			[[
				'itemType' => 'folder',
			], '', 'yes', false, true, true, true, $allTypes, false, true, true],
			[[
				'itemType' => 'folder',
				'shareType' => 0,
			], '', 'yes', false, true, true, false, [0], false, true, true],
			[[
				'itemType' => 'folder',
				'shareType' => '0',
			], '', 'yes', false, true, true, false, [0], false, true, true],
			[[
				'itemType' => 'folder',
				'shareType' => 1,
			], '', 'yes', false, true, true, false, [1], false, true, true],
			[[
				'itemType' => 'folder',
				'shareType' => 12,
			], '', 'yes', false, true, true, false, [], false, true, true],
			[[
				'itemType' => 'folder',
				'shareType' => 'foobar',
			], '', 'yes', false, true, true, true, $allTypes, false, true, true],

			[[
				'itemType' => 'folder',
				'shareType' => [0, 1, 2],
			], '', 'yes', false, false, false, false, [0, 1], false, true, true],
			[[
				'itemType' => 'folder',
				'shareType' => [0, 1],
			], '', 'yes', false, false, false, false, [0, 1], false, true, true],
			[[
				'itemType' => 'folder',
				'shareType' => $allTypes,
			], '', 'yes', false, true, true, true, $allTypes, false, true, true],
			[[
				'itemType' => 'folder',
				'shareType' => $allTypes,
			], '', 'yes', false, false, false, false, [0, 1], false, true, true],
			[[
				'itemType' => 'folder',
				'shareType' => $allTypes,
			], '', 'yes', false, true, false, false, [0, 6], false, true, false],
			[[
				'itemType' => 'folder',
				'shareType' => $allTypes,
			], '', 'yes', false, false, false, true, [0, 4], false, true, false],
			[[
				'itemType' => 'folder',
				'shareType' => $allTypes,
			], '', 'yes', false, true, true, false, [0, 6, 9], false, true, false],

			// Test pagination
			[[
				'itemType' => 'folder',
				'page' => 1,
			], '', 'yes', false, true, true, true, $allTypes, false, true, true],
			[[
				'itemType' => 'folder',
				'page' => 10,
			], '', 'yes', false, true, true, true, $allTypes, false, true, true],

			// Test perPage
			[[
				'itemType' => 'folder',
				'perPage' => 1,
			], '', 'yes', false, true, true, true, $allTypes, false, true, true],
			[[
				'itemType' => 'folder',
				'perPage' => 10,
			], '', 'yes', false, true, true, true, $allTypes, false, true, true],

			// Test $shareWithGroupOnly setting
			[[
				'itemType' => 'folder',
			], 'no', 'yes', false, true, true, true, $allTypes, false, true, true],
			[[
				'itemType' => 'folder',
			], 'yes', 'yes', false, true, true, true, $allTypes, true, true, true],

			// Test $shareeEnumeration setting
			[[
				'itemType' => 'folder',
			], 'no', 'yes', false, true, true, true, $allTypes, false, true, true],
			[[
				'itemType' => 'folder',
			], 'no', 'no', false, true, true, true, $allTypes, false, false, true],

		];
	}

	/**
	 * @dataProvider dataSearch
	 *
	 * @param array $getData
	 * @param string $apiSetting
	 * @param string $enumSetting
	 * @param bool $remoteSharingEnabled
	 * @param bool $isRemoteGroupSharingEnabled
	 * @param bool $emailSharingEnabled
	 * @param array $shareTypes
	 * @param bool $shareWithGroupOnly
	 * @param bool $shareeEnumeration
	 * @param bool $allowGroupSharing
	 * @throws OCSBadRequestException
	 */
	public function testSearch(
		array $getData,
		string $apiSetting,
		string $enumSetting,
		bool $sharingDisabledForUser,
		bool $remoteSharingEnabled,
		bool $isRemoteGroupSharingEnabled,
		bool $emailSharingEnabled,
		array $shareTypes,
		bool $shareWithGroupOnly,
		bool $shareeEnumeration,
		bool $allowGroupSharing,
	): void {
		$search = $getData['search'] ?? '';
		$itemType = $getData['itemType'] ?? 'irrelevant';
		$page = $getData['page'] ?? 1;
		$perPage = $getData['perPage'] ?? 200;
		$shareType = $getData['shareType'] ?? null;

		/** @var IConfig|MockObject $config */
		$config = $this->createMock(IConfig::class);
		$config->expects($this->exactly(1))
			->method('getAppValue')
			->with($this->anything(), $this->anything(), $this->anything())
			->willReturnMap([
				['files_sharing', 'lookupServerEnabled', 'yes', 'yes'],
			]);

		$this->shareManager->expects($this->once())
			->method('allowGroupSharing')
			->willReturn($allowGroupSharing);

		/** @var string */
		$uid = 'test123';
		/** @var IRequest|MockObject $request */
		$request = $this->createMock(IRequest::class);
		/** @var IURLGenerator|MockObject $urlGenerator */
		$urlGenerator = $this->createMock(IURLGenerator::class);

		/** @var MockObject|ShareesAPIController $sharees */
		$sharees = $this->getMockBuilder(ShareesAPIController::class)
			->setConstructorArgs([
				'files_sharing',
				$request,
				$uid,
				$config,
				$urlGenerator,
				$this->shareManager,
				$this->collaboratorSearch
			])
			->onlyMethods(['isRemoteSharingAllowed', 'isRemoteGroupSharingAllowed'])
			->getMock();

		$expectedShareTypes = $shareTypes;
		sort($expectedShareTypes);

		$this->collaboratorSearch->expects($this->once())
			->method('search')
			->with($search, $expectedShareTypes, $this->anything(), $perPage, $perPage * ($page - 1))
			->willReturn([[], false]);

		$sharees->expects($this->any())
			->method('isRemoteSharingAllowed')
			->with($itemType)
			->willReturn($remoteSharingEnabled);


		$sharees->expects($this->any())
			->method('isRemoteGroupSharingAllowed')
			->with($itemType)
			->willReturn($isRemoteGroupSharingEnabled);

		$this->shareManager->expects($this->any())
			->method('sharingDisabledForUser')
			->with($uid)
			->willReturn($sharingDisabledForUser);

		$this->shareManager->expects($this->any())
			->method('shareProviderExists')
			->willReturnCallback(function ($shareType) use ($emailSharingEnabled) {
				if ($shareType === IShare::TYPE_EMAIL) {
					return $emailSharingEnabled;
				} else {
					return false;
				}
			});

		$this->assertInstanceOf(DataResponse::class, $sharees->search($search, $itemType, $page, $perPage, $shareType));
	}

	public function dataSearchInvalid(): array {
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
	public function testSearchInvalid($getData, $message): void {
		$page = $getData['page'] ?? 1;
		$perPage = $getData['perPage'] ?? 200;

		/** @var IConfig|MockObject $config */
		$config = $this->createMock(IConfig::class);
		$config->expects($this->never())
			->method('getAppValue');

		/** @var string */
		$uid = 'test123';
		/** @var IRequest|MockObject $request */
		$request = $this->createMock(IRequest::class);
		/** @var IURLGenerator|MockObject $urlGenerator */
		$urlGenerator = $this->createMock(IURLGenerator::class);

		/** @var MockObject|ShareesAPIController $sharees */
		$sharees = $this->getMockBuilder('\OCA\Files_Sharing\Controller\ShareesAPIController')
			->setConstructorArgs([
				'files_sharing',
				$request,
				$uid,
				$config,
				$urlGenerator,
				$this->shareManager,
				$this->collaboratorSearch
			])
			->onlyMethods(['isRemoteSharingAllowed'])
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
	public function testIsRemoteSharingAllowed($itemType, $expected): void {
		$this->assertSame($expected, $this->invokePrivate($this->sharees, 'isRemoteSharingAllowed', [$itemType]));
	}

	public function testSearchSharingDisabled(): void {
		$this->shareManager->expects($this->once())
			->method('sharingDisabledForUser')
			->with($this->uid)
			->willReturn(true);

		$this->config->expects($this->once())
			->method('getSystemValueInt')
			->with('sharing.minSearchStringLength', 0)
			->willReturn(0);

		$this->shareManager->expects($this->never())
			->method('allowGroupSharing');

		$this->assertInstanceOf(DataResponse::class, $this->sharees->search('', null, 1, 10, [], false));
	}

	public function testSearchNoItemType(): void {
		$this->expectException(OCSBadRequestException::class);
		$this->expectExceptionMessage('Missing itemType');

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
	public function testGetPaginationLink($page, $scriptName, $params, $expected): void {
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
	public function testIsV2($scriptName, $expected): void {
		$this->request->expects($this->once())
			->method('getScriptName')
			->willReturn($scriptName);

		$this->assertEquals($expected, $this->invokePrivate($this->sharees, 'isV2'));
	}
}
