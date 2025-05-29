<?php

declare(strict_types=1);
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
use OCP\GlobalScale\IConfig as GlobalScaleIConfig;
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
	protected ShareesAPIController $sharees;
	protected string $uid;
	protected IRequest&MockObject $request;
	protected IManager $shareManager;
	protected ISearch&MockObject $collaboratorSearch;
	protected IConfig&MockObject $config;

	protected function setUp(): void {
		parent::setUp();

		$this->uid = 'test123';
		$this->request = $this->createMock(IRequest::class);
		$this->shareManager = $this->createMock(IManager::class);
		$this->config = $this->createMock(IConfig::class);
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

	public static function dataSearch(): array {
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
				'search' => '0',
			], '', 'yes', false, true, true, true, $noRemote, false, true, true],

			// Test itemType
			[[
				'itemType' => '',
			], '', 'yes', false, true, true, true, $noRemote, false, true, true],
			[[
				'itemType' => 'folder',
			], '', 'yes', false, true, true, true, $allTypes, false, true, true],
			[[
				'itemType' => null,
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

		$globalConfig = $this->createMock(GlobalScaleIConfig::class);
		$globalConfig->expects(self::once())
			->method('isGlobalScaleEnabled')
			->willReturn(true);
		$this->overwriteService(GlobalScaleIConfig::class, $globalConfig);

		/** @var IConfig&MockObject $config */
		$config = $this->createMock(IConfig::class);

		$this->shareManager->expects($this->once())
			->method('allowGroupSharing')
			->willReturn($allowGroupSharing);

		$uid = 'test123';
		$request = $this->createMock(IRequest::class);
		$urlGenerator = $this->createMock(IURLGenerator::class);
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

	public static function dataSearchInvalid(): array {
		return [
			// Test invalid pagination
			[[
				'page' => 0,
			], 'Invalid page'],
			[[
				'page' => -1,
			], 'Invalid page'],

			// Test invalid perPage
			[[
				'perPage' => 0,
			], 'Invalid perPage argument'],
			[[
				'perPage' => -1,
			], 'Invalid perPage argument'],
		];
	}

	/**
	 * @dataProvider dataSearchInvalid
	 */
	public function testSearchInvalid(array $getData, string $message): void {
		$page = $getData['page'] ?? 1;
		$perPage = $getData['perPage'] ?? 200;

		/** @var IConfig&MockObject $config */
		$config = $this->createMock(IConfig::class);
		$config->expects($this->never())
			->method('getAppValue');

		$uid = 'test123';
		$request = $this->createMock(IRequest::class);
		$urlGenerator = $this->createMock(IURLGenerator::class);

		/** @var ShareesAPIController&MockObject $sharees */
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

	public static function dataIsRemoteSharingAllowed(): array {
		return [
			['file', true],
			['folder', true],
			['', false],
			['contacts', false],
		];
	}

	/**
	 * @dataProvider dataIsRemoteSharingAllowed
	 */
	public function testIsRemoteSharingAllowed(string $itemType, bool $expected): void {
		$this->assertSame($expected, self::invokePrivate($this->sharees, 'isRemoteSharingAllowed', [$itemType]));
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

	public static function dataGetPaginationLink(): array {
		return [
			[1, '/ocs/v1.php', ['perPage' => 2], '<?perPage=2&page=2>; rel="next"'],
			[10, '/ocs/v2.php', ['perPage' => 2], '<?perPage=2&page=11>; rel="next"'],
		];
	}

	/**
	 * @dataProvider dataGetPaginationLink
	 */
	public function testGetPaginationLink(int $page, string $scriptName, array $params, string $expected): void {
		$this->request->expects($this->once())
			->method('getScriptName')
			->willReturn($scriptName);

		$this->assertEquals($expected, self::invokePrivate($this->sharees, 'getPaginationLink', [$page, $params]));
	}

	public static function dataIsV2(): array {
		return [
			['/ocs/v1.php', false],
			['/ocs/v2.php', true],
		];
	}

	/**
	 * @dataProvider dataIsV2
	 */
	public function testIsV2(string $scriptName, bool $expected): void {
		$this->request->expects($this->once())
			->method('getScriptName')
			->willReturn($scriptName);

		$this->assertEquals($expected, self::invokePrivate($this->sharees, 'isV2'));
	}
}
