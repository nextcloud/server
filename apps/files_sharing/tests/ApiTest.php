<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Files_Sharing\Tests;

use OC\Core\AppInfo\ConfigLexicon;
use OC\Files\Cache\Scanner;
use OC\Files\FileInfo;
use OC\Files\Filesystem;
use OC\Files\Storage\Temporary;
use OC\Files\View;
use OCA\Federation\TrustedServers;
use OCA\Files_Sharing\Controller\ShareAPIController;
use OCP\App\IAppManager;
use OCP\AppFramework\OCS\OCSBadRequestException;
use OCP\AppFramework\OCS\OCSException;
use OCP\AppFramework\OCS\OCSForbiddenException;
use OCP\AppFramework\OCS\OCSNotFoundException;
use OCP\Constants;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use OCP\IAppConfig;
use OCP\IConfig;
use OCP\IDateTimeZone;
use OCP\IGroupManager;
use OCP\IL10N;
use OCP\IPreview;
use OCP\IRequest;
use OCP\ITagManager;
use OCP\IURLGenerator;
use OCP\IUserManager;
use OCP\Mail\IMailer;
use OCP\Server;
use OCP\Share\IProviderFactory;
use OCP\Share\IShare;
use OCP\UserStatus\IManager as IUserStatusManager;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Test\Traits\EmailValidatorTrait;

// TODO: convert to real integration tests
/**
 * Class ApiTest
 */
#[\PHPUnit\Framework\Attributes\Medium]
#[\PHPUnit\Framework\Attributes\Group(name: 'DB')]
class ApiTest extends TestCase {
	use EmailValidatorTrait;

	public const TEST_FOLDER_NAME = '/folder_share_api_test';
	public const APP_NAME = 'files_sharing';

	private static $tempStorage;

	private Folder $userFolder;
	private string $subsubfolder;
	protected IAppConfig&MockObject $appConfig;

	protected function setUp(): void {
		parent::setUp();

		Server::get(IConfig::class)->setAppValue('core', 'shareapi_exclude_groups', 'no');
		Server::get(IConfig::class)->setAppValue('core', 'shareapi_expire_after_n_days', '7');

		Filesystem::getLoader()->removeStorageWrapper('sharing_mask');

		$this->folder = self::TEST_FOLDER_NAME;
		$this->subfolder = '/subfolder_share_api_test';
		$this->subsubfolder = '/subsubfolder_share_api_test';

		$this->filename = '/share-api-test.txt';

		// save file with content
		$this->view->file_put_contents($this->filename, $this->data);
		$this->view->mkdir($this->folder);
		$this->view->mkdir($this->folder . $this->subfolder);
		$this->view->mkdir($this->folder . $this->subfolder . $this->subsubfolder);
		$this->view->file_put_contents($this->folder . $this->filename, $this->data);
		$this->view->file_put_contents($this->folder . $this->subfolder . $this->filename, $this->data);
		$mount = $this->view->getMount($this->filename);
		$mount->getStorage()->getScanner()->scan('', Scanner::SCAN_RECURSIVE);

		$this->userFolder = \OC::$server->getUserFolder(self::TEST_FILES_SHARING_API_USER1);

		$this->appConfig = $this->createMock(IAppConfig::class);
	}

	protected function tearDown(): void {
		if ($this->view instanceof View) {
			$this->view->unlink($this->filename);
			$this->view->deleteAll($this->folder);
		}

		self::$tempStorage = null;

		parent::tearDown();
	}

	/**
	 * @param string $userId The userId of the caller
	 * @return ShareAPIController
	 */
	private function createOCS($userId) {
		$l = $this->getMockBuilder(IL10N::class)->getMock();
		$l->method('t')
			->willReturnCallback(function ($text, $parameters = []) {
				return vsprintf($text, $parameters);
			});
		$config = $this->createMock(IConfig::class);
		$appManager = $this->createMock(IAppManager::class);
		$serverContainer = $this->createMock(ContainerInterface::class);
		$userStatusManager = $this->createMock(IUserStatusManager::class);
		$previewManager = $this->createMock(IPreview::class);
		$dateTimeZone = $this->createMock(IDateTimeZone::class);
		$logger = $this->createMock(LoggerInterface::class);
		$providerFactory = $this->createMock(IProviderFactory::class);
		$mailer = $this->createMock(IMailer::class);
		$tagManager = $this->createMock(ITagManager::class);
		$trustedServers = $this->createMock(TrustedServers::class);
		$dateTimeZone->method('getTimeZone')->willReturn(new \DateTimeZone(date_default_timezone_get()));

		return new ShareAPIController(
			self::APP_NAME,
			$this->getMockBuilder(IRequest::class)->getMock(),
			$this->shareManager,
			Server::get(IGroupManager::class),
			Server::get(IUserManager::class),
			Server::get(IRootFolder::class),
			Server::get(IURLGenerator::class),
			$l,
			$config,
			$this->appConfig,
			$appManager,
			$serverContainer,
			$userStatusManager,
			$previewManager,
			$dateTimeZone,
			$logger,
			$providerFactory,
			$mailer,
			$tagManager,
			$this->getEmailValidatorWithStrictEmailCheck(),
			$trustedServers,
			$userId,
		);
	}

	public function testCreateShareUserFile(): void {
		$this->setUp(); // for some reasons phpunit refuses to do this for us only for this test
		$ocs = $this->createOCS(self::TEST_FILES_SHARING_API_USER1);
		$result = $ocs->createShare($this->filename, Constants::PERMISSION_ALL, IShare::TYPE_USER, self::TEST_FILES_SHARING_API_USER2);
		$ocs->cleanup();

		$data = $result->getData();
		$this->assertEquals(19, $data['permissions']);
		$this->assertEmpty($data['expiration']);

		$this->shareManager->getShareById('ocinternal:' . $data['id']);

		$ocs = $this->createOCS(self::TEST_FILES_SHARING_API_USER1);
		$ocs->deleteShare($data['id']);

		$ocs->cleanup();
	}

	public function testCreateShareUserFolder(): void {
		$ocs = $this->createOCS(self::TEST_FILES_SHARING_API_USER1);
		$result = $ocs->createShare($this->folder, Constants::PERMISSION_ALL, IShare::TYPE_USER, self::TEST_FILES_SHARING_API_USER2);
		$ocs->cleanup();

		$data = $result->getData();
		$this->assertEquals(31, $data['permissions']);
		$this->assertEmpty($data['expiration']);

		$this->shareManager->getShareById('ocinternal:' . $data['id']);

		$ocs = $this->createOCS(self::TEST_FILES_SHARING_API_USER1);
		$ocs->deleteShare($data['id']);
		$ocs->cleanup();
	}

	public function testCreateShareGroupFile(): void {
		$ocs = $this->createOCS(self::TEST_FILES_SHARING_API_USER1);
		$result = $ocs->createShare($this->filename, Constants::PERMISSION_ALL, IShare::TYPE_GROUP, self::TEST_FILES_SHARING_API_GROUP1);
		$ocs->cleanup();

		$data = $result->getData();
		$this->assertEquals(19, $data['permissions']);
		$this->assertEmpty($data['expiration']);

		$this->shareManager->getShareById('ocinternal:' . $data['id']);

		$ocs = $this->createOCS(self::TEST_FILES_SHARING_API_USER1);
		$ocs->deleteShare($data['id']);
		$ocs->cleanup();
	}

	public function testCreateShareGroupFolder(): void {
		$ocs = $this->createOCS(self::TEST_FILES_SHARING_API_USER1);
		$result = $ocs->createShare($this->folder, Constants::PERMISSION_ALL, IShare::TYPE_GROUP, self::TEST_FILES_SHARING_API_GROUP1);
		$ocs->cleanup();

		$data = $result->getData();
		$this->assertEquals(31, $data['permissions']);
		$this->assertEmpty($data['expiration']);

		$this->shareManager->getShareById('ocinternal:' . $data['id']);

		$ocs = $this->createOCS(self::TEST_FILES_SHARING_API_USER1);
		$ocs->deleteShare($data['id']);
		$ocs->cleanup();
	}

	#[\PHPUnit\Framework\Attributes\Group(name: 'RoutingWeirdness')]
	public function testCreateShareLink(): void {
		$ocs = $this->createOCS(self::TEST_FILES_SHARING_API_USER1);
		$result = $ocs->createShare($this->folder, Constants::PERMISSION_ALL, IShare::TYPE_LINK);
		$ocs->cleanup();

		$data = $result->getData();
		$this->assertEquals(Constants::PERMISSION_ALL,
			$data['permissions']);
		$this->assertEmpty($data['expiration']);
		$this->assertTrue(is_string($data['token']));

		// check for correct link
		$url = Server::get(IURLGenerator::class)->getAbsoluteURL('/index.php/s/' . $data['token']);
		$this->assertEquals($url, $data['url']);

		$this->shareManager->getShareById('ocinternal:' . $data['id']);

		$ocs = $this->createOCS(self::TEST_FILES_SHARING_API_USER1);
		$ocs->deleteShare($data['id']);
		$ocs->cleanup();
	}

	#[\PHPUnit\Framework\Attributes\DataProvider(methodName: 'dataAllowFederationOnPublicShares')]
	#[\PHPUnit\Framework\Attributes\Group(name: 'RoutingWeirdness')]
	public function testCreateShareLinkPublicUpload(array $appConfig, int $permissions): void {
		$this->appConfig->method('getValueBool')
			->willReturnMap([$appConfig]);

		$ocs = $this->createOCS(self::TEST_FILES_SHARING_API_USER1);
		$result = $ocs->createShare($this->folder, Constants::PERMISSION_ALL, IShare::TYPE_LINK, null, 'true');
		$ocs->cleanup();

		$data = $result->getData();
		$this->assertEquals(
			Constants::PERMISSION_READ
			| Constants::PERMISSION_CREATE
			| Constants::PERMISSION_UPDATE
			| Constants::PERMISSION_DELETE
			| $permissions,
			$data['permissions']
		);
		$this->assertEmpty($data['expiration']);
		$this->assertTrue(is_string($data['token']));

		// check for correct link
		$url = Server::get(IURLGenerator::class)->getAbsoluteURL('/index.php/s/' . $data['token']);
		$this->assertEquals($url, $data['url']);

		$this->shareManager->getShareById('ocinternal:' . $data['id']);

		$ocs = $this->createOCS(self::TEST_FILES_SHARING_API_USER1);
		$ocs->deleteShare($data['id']);
		$ocs->cleanup();
	}

	public function testEnforceLinkPassword(): void {
		$password = md5(time());
		$config = Server::get(IConfig::class);
		$config->setAppValue('core', 'shareapi_enforce_links_password', 'yes');

		$ocs = $this->createOCS(self::TEST_FILES_SHARING_API_USER1);
		try {
			$ocs->createShare($this->folder, Constants::PERMISSION_ALL, IShare::TYPE_LINK);
			$this->fail();
		} catch (OCSForbiddenException $e) {
		}
		$ocs->cleanup();

		$ocs = $this->createOCS(self::TEST_FILES_SHARING_API_USER1);
		try {
			$ocs->createShare($this->folder, Constants::PERMISSION_ALL, IShare::TYPE_LINK, null, 'false', '');
			$this->fail();
		} catch (OCSForbiddenException $e) {
		}
		$ocs->cleanup();

		// share with password should succeed
		$ocs = $this->createOCS(self::TEST_FILES_SHARING_API_USER1);
		$result = $ocs->createShare($this->folder, Constants::PERMISSION_ALL, IShare::TYPE_LINK, null, 'false', $password);
		$ocs->cleanup();

		$data = $result->getData();

		// setting new password should succeed
		$ocs = $this->createOCS(self::TEST_FILES_SHARING_API_USER1);
		$ocs->updateShare($data['id'], null, $password);
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

		$config->setAppValue('core', 'shareapi_enforce_links_password', 'no');
		$this->addToAssertionCount(1);
	}

	public function testSharePermissions(): void {
		// sharing file to a user should work if shareapi_exclude_groups is set
		// to no
		Server::get(IConfig::class)->setAppValue('core', 'shareapi_exclude_groups', 'no');

		$ocs = $this->createOCS(self::TEST_FILES_SHARING_API_USER1);
		$result = $ocs->createShare($this->filename, Constants::PERMISSION_ALL, IShare::TYPE_USER, self::TEST_FILES_SHARING_API_USER2);
		$ocs->cleanup();

		$data = $result->getData();

		$this->shareManager->getShareById('ocinternal:' . $data['id']);

		$ocs = $this->createOCS(self::TEST_FILES_SHARING_API_USER1);
		$ocs->deleteShare($data['id']);
		$ocs->cleanup();

		// exclude groups, but not the group the user belongs to. Sharing should still work
		Server::get(IConfig::class)->setAppValue('core', 'shareapi_exclude_groups', 'yes');
		Server::get(IConfig::class)->setAppValue('core', 'shareapi_exclude_groups_list', 'admin,group1,group2');

		$ocs = $this->createOCS(self::TEST_FILES_SHARING_API_USER1);
		$result = $ocs->createShare($this->filename, Constants::PERMISSION_ALL, IShare::TYPE_USER, self::TEST_FILES_SHARING_API_USER2);
		$ocs->cleanup();

		$data = $result->getData();

		$this->shareManager->getShareById('ocinternal:' . $data['id']);

		$ocs = $this->createOCS(self::TEST_FILES_SHARING_API_USER1);
		$ocs->deleteShare($data['id']);
		$ocs->cleanup();

		// now we exclude the group the user belongs to ('group'), sharing should fail now
		Server::get(IConfig::class)->setAppValue('core', 'shareapi_exclude_groups_list', 'admin,group');

		$ocs = $this->createOCS(self::TEST_FILES_SHARING_API_USER1);
		$ocs->createShare($this->filename, Constants::PERMISSION_ALL, IShare::TYPE_USER, self::TEST_FILES_SHARING_API_USER2);
		$ocs->cleanup();

		// cleanup
		Server::get(IConfig::class)->setAppValue('core', 'shareapi_exclude_groups', 'no');
		Server::get(IConfig::class)->setAppValue('core', 'shareapi_exclude_groups_list', '');

		$this->addToAssertionCount(1);
	}

	public function testGetAllShares(): void {
		$node = $this->userFolder->get($this->filename);

		$share = $this->shareManager->newShare();
		$share->setNode($node)
			->setSharedBy(self::TEST_FILES_SHARING_API_USER1)
			->setSharedWith(self::TEST_FILES_SHARING_API_USER2)
			->setShareType(IShare::TYPE_USER)
			->setPermissions(19);

		$share = $this->shareManager->createShare($share);

		$ocs = $this->createOCS(self::TEST_FILES_SHARING_API_USER1);
		$result = $ocs->getShares();
		$ocs->cleanup();

		$this->assertTrue(count($result->getData()) === 1);

		$this->shareManager->deleteShare($share);
	}

	public function testGetAllSharesWithMe(): void {
		$this->loginAsUser(self::TEST_FILES_SHARING_API_USER2);
		$this->logout();

		$node1 = $this->userFolder->get($this->filename);
		$share1 = $this->shareManager->newShare();
		$share1->setNode($node1)
			->setSharedBy(self::TEST_FILES_SHARING_API_USER1)
			->setSharedWith(self::TEST_FILES_SHARING_API_USER2)
			->setShareType(IShare::TYPE_USER)
			->setPermissions(19);
		$share1 = $this->shareManager->createShare($share1);
		$share1->setStatus(IShare::STATUS_ACCEPTED);
		$this->shareManager->updateShare($share1);

		$node2 = $this->userFolder->get($this->folder);
		$share2 = $this->shareManager->newShare();
		$share2->setNode($node2)
			->setSharedBy(self::TEST_FILES_SHARING_API_USER1)
			->setSharedWith(self::TEST_FILES_SHARING_API_USER2)
			->setShareType(IShare::TYPE_USER)
			->setPermissions(31);
		$share2 = $this->shareManager->createShare($share2);
		$share2->setStatus(IShare::STATUS_ACCEPTED);
		$this->shareManager->updateShare($share2);

		$ocs = $this->createOCS(self::TEST_FILES_SHARING_API_USER2);
		$result = $ocs->getShares('true');
		$ocs->cleanup();

		$this->assertCount(2, $result->getData());

		$this->shareManager->deleteShare($share1);
		$this->shareManager->deleteShare($share2);
	}

	#[\PHPUnit\Framework\Attributes\Group('RoutingWeirdness')]
	public function testPublicLinkUrl(): void {
		$ocs = $this->createOCS(self::TEST_FILES_SHARING_API_USER1);
		$result = $ocs->createShare($this->folder, Constants::PERMISSION_ALL, IShare::TYPE_LINK);
		$ocs->cleanup();

		$data = $result->getData();

		// check if we have a token
		$this->assertTrue(is_string($data['token']));
		$id = $data['id'];

		// check for correct link
		$url = Server::get(IURLGenerator::class)->getAbsoluteURL('/index.php/s/' . $data['token']);
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

	#[\PHPUnit\Framework\Attributes\Depends(methodName: 'testCreateShareUserFile')]
	#[\PHPUnit\Framework\Attributes\Depends(methodName: 'testCreateShareLink')]
	public function testGetShareFromSource(): void {
		$node = $this->userFolder->get($this->filename);
		$share = $this->shareManager->newShare();
		$share->setNode($node)
			->setSharedBy(self::TEST_FILES_SHARING_API_USER1)
			->setSharedWith(self::TEST_FILES_SHARING_API_USER2)
			->setShareType(IShare::TYPE_USER)
			->setPermissions(19);
		$share1 = $this->shareManager->createShare($share);

		$share = $this->shareManager->newShare();
		$share->setNode($node)
			->setSharedBy(self::TEST_FILES_SHARING_API_USER1)
			->setShareType(IShare::TYPE_LINK)
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

	#[\PHPUnit\Framework\Attributes\Depends(methodName: 'testCreateShareUserFile')]
	#[\PHPUnit\Framework\Attributes\Depends(methodName: 'testCreateShareLink')]
	public function testGetShareFromSourceWithReshares(): void {
		$node = $this->userFolder->get($this->filename);
		$share1 = $this->shareManager->newShare();
		$share1->setNode($node)
			->setSharedBy(self::TEST_FILES_SHARING_API_USER1)
			->setSharedWith(self::TEST_FILES_SHARING_API_USER2)
			->setShareType(IShare::TYPE_USER)
			->setPermissions(19);
		$share1 = $this->shareManager->createShare($share1);

		$share2 = $this->shareManager->newShare();
		$share2->setNode($node)
			->setSharedBy(self::TEST_FILES_SHARING_API_USER2)
			->setSharedWith(self::TEST_FILES_SHARING_API_USER3)
			->setShareType(IShare::TYPE_USER)
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

	#[\PHPUnit\Framework\Attributes\Depends(methodName: 'testCreateShareUserFile')]
	public function testGetShareFromId(): void {
		$node = $this->userFolder->get($this->filename);
		$share1 = $this->shareManager->newShare();
		$share1->setNode($node)
			->setSharedBy(self::TEST_FILES_SHARING_API_USER1)
			->setSharedWith(self::TEST_FILES_SHARING_API_USER2)
			->setShareType(IShare::TYPE_USER)
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

	public function testGetShareFromFolder(): void {
		$node1 = $this->userFolder->get($this->filename);
		$share1 = $this->shareManager->newShare();
		$share1->setNode($node1)
			->setSharedBy(self::TEST_FILES_SHARING_API_USER1)
			->setSharedWith(self::TEST_FILES_SHARING_API_USER2)
			->setShareType(IShare::TYPE_USER)
			->setPermissions(19);
		$share1 = $this->shareManager->createShare($share1);

		$node2 = $this->userFolder->get($this->folder . '/' . $this->filename);
		$share2 = $this->shareManager->newShare();
		$share2->setNode($node2)
			->setSharedBy(self::TEST_FILES_SHARING_API_USER1)
			->setShareType(IShare::TYPE_LINK)
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

	public function testGetShareFromFolderWithFile(): void {
		$node1 = $this->userFolder->get($this->filename);
		$share1 = $this->shareManager->newShare();
		$share1->setNode($node1)
			->setSharedBy(self::TEST_FILES_SHARING_API_USER1)
			->setSharedWith(self::TEST_FILES_SHARING_API_USER2)
			->setShareType(IShare::TYPE_USER)
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
	 */
	public function testGetShareFromFolderReshares(): void {
		$node1 = $this->userFolder->get($this->folder);
		$share1 = $this->shareManager->newShare();
		$share1->setNode($node1)
			->setSharedBy(self::TEST_FILES_SHARING_API_USER1)
			->setSharedWith(self::TEST_FILES_SHARING_API_USER2)
			->setShareType(IShare::TYPE_USER)
			->setPermissions(31);
		$share1 = $this->shareManager->createShare($share1);
		$share1->setStatus(IShare::STATUS_ACCEPTED);
		$this->shareManager->updateShare($share1);

		$node2 = $this->userFolder->get($this->folder . '/' . $this->filename);
		$share2 = $this->shareManager->newShare();
		$share2->setNode($node2)
			->setSharedBy(self::TEST_FILES_SHARING_API_USER2)
			->setShareType(IShare::TYPE_LINK)
			->setPermissions(1);
		$share2 = $this->shareManager->createShare($share2);
		$share2->setStatus(IShare::STATUS_ACCEPTED);
		$this->shareManager->updateShare($share2);

		$node3 = $this->userFolder->get($this->folder . '/' . $this->subfolder . '/' . $this->filename);
		$share3 = $this->shareManager->newShare();
		$share3->setNode($node3)
			->setSharedBy(self::TEST_FILES_SHARING_API_USER2)
			->setShareType(IShare::TYPE_LINK)
			->setPermissions(1);
		$share3 = $this->shareManager->createShare($share3);
		$share3->setStatus(IShare::STATUS_ACCEPTED);
		$this->shareManager->updateShare($share3);

		$testValues = [
			['query' => $this->folder,
				'expectedResult' => $this->folder . $this->filename],
			['query' => $this->folder . $this->subfolder,
				'expectedResult' => $this->folder . $this->subfolder . $this->filename],
		];

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
	 */
	public function testGetShareFromSubFolderReShares(): void {
		$node1 = $this->userFolder->get($this->folder . $this->subfolder);
		$share1 = $this->shareManager->newShare();
		$share1->setNode($node1)
			->setSharedBy(self::TEST_FILES_SHARING_API_USER1)
			->setSharedWith(self::TEST_FILES_SHARING_API_USER2)
			->setShareType(IShare::TYPE_USER)
			->setPermissions(31);
		$share1 = $this->shareManager->createShare($share1);
		$share1->setStatus(IShare::STATUS_ACCEPTED);
		$this->shareManager->updateShare($share1);

		$node2 = Server::get(IRootFolder::class)->getUserFolder(self::TEST_FILES_SHARING_API_USER2)->get($this->subfolder);
		$share2 = $this->shareManager->newShare();
		$share2->setNode($node2)
			->setSharedBy(self::TEST_FILES_SHARING_API_USER2)
			->setShareType(IShare::TYPE_LINK)
			->setPermissions(1);
		$share2 = $this->shareManager->createShare($share2);
		$share2->setStatus(IShare::STATUS_ACCEPTED);
		$this->shareManager->updateShare($share2);

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
	 */
	public function XtestGetShareFromFolderReReShares() {
		$node1 = $this->userFolder->get($this->folder . $this->subfolder);
		$share1 = $this->shareManager->newShare();
		$share1->setNode($node1)
			->setSharedBy(self::TEST_FILES_SHARING_API_USER1)
			->setSharedWith(self::TEST_FILES_SHARING_API_USER2)
			->setShareType(IShare::TYPE_USER)
			->setPermissions(31);
		$share1 = $this->shareManager->createShare($share1);

		$node2 = $this->userFolder->get($this->folder . $this->subfolder . $this->subsubfolder);
		$share2 = $this->shareManager->newShare();
		$share2->setNode($node2)
			->setSharedBy(self::TEST_FILES_SHARING_API_USER2)
			->setSharedWith(self::TEST_FILES_SHARING_API_USER3)
			->setShareType(IShare::TYPE_USER)
			->setPermissions(31);
		$share2 = $this->shareManager->createShare($share2);

		$share3 = $this->shareManager->newShare();
		$share3->setNode($node2)
			->setSharedBy(self::TEST_FILES_SHARING_API_USER3)
			->setShareType(IShare::TYPE_LINK)
			->setPermissions(1);
		$share3 = $this->shareManager->createShare($share3);

		/*
		 * Test as recipient
		 */
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
	 */
	public function testGetShareMultipleSharedFolder(): void {
		$this->setUp();
		$node1 = $this->userFolder->get($this->folder . $this->subfolder);
		$share1 = $this->shareManager->newShare();
		$share1->setNode($node1)
			->setSharedBy(self::TEST_FILES_SHARING_API_USER1)
			->setSharedWith(self::TEST_FILES_SHARING_API_USER2)
			->setShareType(IShare::TYPE_USER)
			->setPermissions(31);
		$share1 = $this->shareManager->createShare($share1);
		$share1->setStatus(IShare::STATUS_ACCEPTED);
		$this->shareManager->updateShare($share1);

		$node2 = $this->userFolder->get($this->folder);
		$share2 = $this->shareManager->newShare();
		$share2->setNode($node2)
			->setSharedBy(self::TEST_FILES_SHARING_API_USER1)
			->setSharedWith(self::TEST_FILES_SHARING_API_USER2)
			->setShareType(IShare::TYPE_USER)
			->setPermissions(31);
		$share2 = $this->shareManager->createShare($share2);
		$share2->setStatus(IShare::STATUS_ACCEPTED);
		$this->shareManager->updateShare($share2);

		$share3 = $this->shareManager->newShare();
		$share3->setNode($node1)
			->setSharedBy(self::TEST_FILES_SHARING_API_USER2)
			->setShareType(IShare::TYPE_LINK)
			->setPermissions(1);
		$share3 = $this->shareManager->createShare($share3);
		$share3->setStatus(IShare::STATUS_ACCEPTED);
		$this->shareManager->updateShare($share3);

		// $request = $this->createRequest(['path' => $this->subfolder]);
		$ocs = $this->createOCS(self::TEST_FILES_SHARING_API_USER2);
		$result1 = $ocs->getShares('false', 'false', 'false', $this->subfolder);
		$ocs->cleanup();

		// test should return one share within $this->folder
		$data1 = $result1->getData();
		$this->assertCount(1, $data1);
		$s1 = reset($data1);

		//$request = $this->createRequest(['path' => $this->folder.$this->subfolder]);
		$ocs = $this->createOCS(self::TEST_FILES_SHARING_API_USER2);
		$result2 = $ocs->getShares('false', 'false', 'false', $this->folder . $this->subfolder);
		$ocs->cleanup();

		// test should return one share within $this->folder
		$data2 = $result2->getData();
		$this->assertCount(1, $data2);
		$s2 = reset($data2);

		$this->assertEquals($this->subfolder, $s1['path']);
		$this->assertEquals($this->folder . $this->subfolder, $s2['path']);

		$this->shareManager->deleteShare($share1);
		$this->shareManager->deleteShare($share2);
		$this->shareManager->deleteShare($share3);
	}

	/**
	 * test re-re-share of folder if the path gets constructed correctly
	 */
	public function testGetShareFromFileReReShares(): void {
		$node1 = $this->userFolder->get($this->folder . $this->subfolder);
		$share1 = $this->shareManager->newShare();
		$share1->setNode($node1)
			->setSharedBy(self::TEST_FILES_SHARING_API_USER1)
			->setSharedWith(self::TEST_FILES_SHARING_API_USER2)
			->setShareType(IShare::TYPE_USER)
			->setPermissions(31);
		$share1 = $this->shareManager->createShare($share1);
		$share1->setStatus(IShare::STATUS_ACCEPTED);
		$this->shareManager->updateShare($share1);

		$user2Folder = \OC::$server->getUserFolder(self::TEST_FILES_SHARING_API_USER2);
		$node2 = $user2Folder->get($this->subfolder . $this->filename);
		$share2 = $this->shareManager->newShare();
		$share2->setNode($node2)
			->setSharedBy(self::TEST_FILES_SHARING_API_USER2)
			->setSharedWith(self::TEST_FILES_SHARING_API_USER3)
			->setShareType(IShare::TYPE_USER)
			->setPermissions(19);
		$share2 = $this->shareManager->createShare($share2);
		$share2->setStatus(IShare::STATUS_ACCEPTED);
		$this->shareManager->updateShare($share2);

		$user3Folder = \OC::$server->getUserFolder(self::TEST_FILES_SHARING_API_USER3);
		$node3 = $user3Folder->get($this->filename);
		$share3 = $this->shareManager->newShare();
		$share3->setNode($node3)
			->setSharedBy(self::TEST_FILES_SHARING_API_USER3)
			->setShareType(IShare::TYPE_LINK)
			->setPermissions(1);
		$share3 = $this->shareManager->createShare($share3);
		$share3->setStatus(IShare::STATUS_ACCEPTED);
		$this->shareManager->updateShare($share3);

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

	public function testGetShareFromUnknownId(): void {
		$ocs = $this->createOCS(self::TEST_FILES_SHARING_API_USER3);
		try {
			$ocs->getShare(0);
			$this->fail();
		} catch (OCSNotFoundException $e) {
			$this->assertEquals('Wrong share ID, share does not exist', $e->getMessage());
		}
		$ocs->cleanup();
	}

	#[\PHPUnit\Framework\Attributes\Depends(methodName: 'testCreateShareUserFile')]
	#[\PHPUnit\Framework\Attributes\Depends(methodName: 'testCreateShareLink')]
	public function testUpdateShare(): void {
		$password = md5(time());

		$node1 = $this->userFolder->get($this->filename);
		$share1 = $this->shareManager->newShare();
		$share1->setNode($node1)
			->setSharedBy(self::TEST_FILES_SHARING_API_USER1)
			->setSharedWith(self::TEST_FILES_SHARING_API_USER2)
			->setShareType(IShare::TYPE_USER)
			->setPermissions(19)
			->setAttributes($this->shareManager->newShare()->newAttributes());

		$this->assertNotNull($share1->getAttributes());
		$share1 = $this->shareManager->createShare($share1);
		$this->assertEquals(19, $share1->getPermissions());

		$share2 = $this->shareManager->newShare();
		$share2->setNode($node1)
			->setSharedBy(self::TEST_FILES_SHARING_API_USER1)
			->setShareType(IShare::TYPE_LINK)
			->setPermissions(1);
		$share2 = $this->shareManager->createShare($share2);
		$this->assertEquals(1, $share2->getPermissions());

		// update permissions
		$ocs = $this->createOCS(self::TEST_FILES_SHARING_API_USER1);
		$ocs->updateShare(
			$share1->getId(), 1, null, null, null, null, null, null, null,
			'[{"scope": "app1", "key": "attr1", "value": true}]'
		);
		$ocs->cleanup();

		$share1 = $this->shareManager->getShareById('ocinternal:' . $share1->getId());
		$this->assertEquals(1, $share1->getPermissions());
		$this->assertEquals(true, $share1->getAttributes()->getAttribute('app1', 'attr1'));

		// update password for link share
		$this->assertNull($share2->getPassword());

		$ocs = $this->createOCS(self::TEST_FILES_SHARING_API_USER1);
		$ocs->updateShare($share2->getId(), null, $password);
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

	#[\PHPUnit\Framework\Attributes\DataProvider(methodName: 'dataAllowFederationOnPublicShares')]
	public function testUpdateShareUpload(array $appConfig, int $permissions): void {
		$this->appConfig->method('getValueBool')->willReturnMap([
			$appConfig,
		]);

		$node1 = $this->userFolder->get($this->folder);
		$share1 = $this->shareManager->newShare();
		$share1->setNode($node1)
			->setSharedBy(self::TEST_FILES_SHARING_API_USER1)
			->setShareType(IShare::TYPE_LINK)
			->setPermissions(1);
		$share1 = $this->shareManager->createShare($share1);

		// update public upload
		$ocs = $this->createOCS(self::TEST_FILES_SHARING_API_USER1);
		$ocs->updateShare($share1->getId(), null, null, null, 'true');
		$ocs->cleanup();

		$share1 = $this->shareManager->getShareById($share1->getFullId());
		$this->assertEquals(
			Constants::PERMISSION_READ
			| Constants::PERMISSION_CREATE
			| Constants::PERMISSION_UPDATE
			| Constants::PERMISSION_DELETE
			| $permissions,
			$share1->getPermissions()
		);

		// cleanup
		$this->shareManager->deleteShare($share1);
	}

	public static function dataAllowFederationOnPublicShares(): array {
		return [
			[['core', ConfigLexicon::SHAREAPI_ALLOW_FEDERATION_ON_PUBLIC_SHARES, false, false], 0],
			[['core', ConfigLexicon::SHAREAPI_ALLOW_FEDERATION_ON_PUBLIC_SHARES, false, true], Constants::PERMISSION_SHARE],
		];
	}

	public function testUpdateShareExpireDate(): void {
		$node1 = $this->userFolder->get($this->folder);
		$share1 = $this->shareManager->newShare();
		$share1->setNode($node1)
			->setSharedBy(self::TEST_FILES_SHARING_API_USER1)
			->setShareType(IShare::TYPE_LINK)
			->setPermissions(1);
		$share1 = $this->shareManager->createShare($share1);
		$share1->setStatus(IShare::STATUS_ACCEPTED);
		$this->shareManager->updateShare($share1);

		$config = Server::get(IConfig::class);

		// enforce expire date, by default 7 days after the file was shared
		$config->setAppValue('core', 'shareapi_default_expire_date', 'yes');
		$config->setAppValue('core', 'shareapi_enforce_expire_date', 'yes');

		$dateWithinRange = new \DateTime();
		$dateWithinRange->add(new \DateInterval('P6D'));

		$dateOutOfRange = new \DateTime();
		$dateOutOfRange->add(new \DateInterval('P8D'));

		// update expire date to a valid value
		$ocs = $this->createOCS(self::TEST_FILES_SHARING_API_USER1);
		$ocs->updateShare($share1->getId(), null, null, null, null, $dateWithinRange->format('Y-m-d'));
		$ocs->cleanup();

		$share1 = $this->shareManager->getShareById($share1->getFullId());

		// date should be changed
		$dateWithinRange->setTime(0, 0, 0);
		$dateWithinRange->setTimezone(new \DateTimeZone(date_default_timezone_get()));
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

	#[\PHPUnit\Framework\Attributes\Depends('testCreateShareUserFile')]
	public function testDeleteShare(): void {
		$node1 = $this->userFolder->get($this->filename);
		$share1 = $this->shareManager->newShare();
		$share1->setNode($node1)
			->setSharedBy(self::TEST_FILES_SHARING_API_USER1)
			->setSharedWith(self::TEST_FILES_SHARING_API_USER2)
			->setShareType(IShare::TYPE_USER)
			->setPermissions(19);
		$share1 = $this->shareManager->createShare($share1);

		$share2 = $this->shareManager->newShare();
		$share2->setNode($node1)
			->setSharedBy(self::TEST_FILES_SHARING_API_USER1)
			->setShareType(IShare::TYPE_LINK)
			->setPermissions(1);
		$share2 = $this->shareManager->createShare($share2);

		$ocs = $this->createOCS(self::TEST_FILES_SHARING_API_USER1);
		$ocs->deleteShare($share1->getId());
		$ocs->cleanup();

		$ocs = $this->createOCS(self::TEST_FILES_SHARING_API_USER1);
		$ocs->deleteShare($share2->getId());
		$ocs->cleanup();

		$this->assertEmpty($this->shareManager->getSharesBy(self::TEST_FILES_SHARING_API_USER2, IShare::TYPE_USER));
		$this->assertEmpty($this->shareManager->getSharesBy(self::TEST_FILES_SHARING_API_USER2, IShare::TYPE_LINK));
	}

	/**
	 * test unshare of a reshared file
	 */
	public function testDeleteReshare(): void {
		$node1 = $this->userFolder->get($this->folder);
		$share1 = $this->shareManager->newShare();
		$share1->setNode($node1)
			->setSharedBy(self::TEST_FILES_SHARING_API_USER1)
			->setSharedWith(self::TEST_FILES_SHARING_API_USER2)
			->setShareType(IShare::TYPE_USER)
			->setPermissions(31);
		$share1 = $this->shareManager->createShare($share1);
		$share1->setStatus(IShare::STATUS_ACCEPTED);
		$this->shareManager->updateShare($share1);

		$user2folder = \OC::$server->getUserFolder(self::TEST_FILES_SHARING_API_USER2);
		$node2 = $user2folder->get($this->folder . '/' . $this->filename);
		$share2 = $this->shareManager->newShare();
		$share2->setNode($node2)
			->setSharedBy(self::TEST_FILES_SHARING_API_USER2)
			->setShareType(IShare::TYPE_LINK)
			->setPermissions(1);
		$share2 = $this->shareManager->createShare($share2);
		$share2->setStatus(IShare::STATUS_ACCEPTED);
		$this->shareManager->updateShare($share2);

		// test if we can unshare the link again
		$ocs = $this->createOCS(self::TEST_FILES_SHARING_API_USER2);
		$ocs->deleteShare($share2->getId());
		$ocs->cleanup();

		$this->shareManager->deleteShare($share1);
		$this->addToAssertionCount(1);
	}

	/**
	 * share a folder which contains a share mount point, should be forbidden
	 */
	public function testShareFolderWithAMountPoint(): void {
		// user 1 shares a folder with user2
		self::loginHelper(self::TEST_FILES_SHARING_API_USER1);

		$share = $this->share(
			IShare::TYPE_USER,
			$this->folder,
			self::TEST_FILES_SHARING_API_USER1,
			self::TEST_FILES_SHARING_API_USER2,
			Constants::PERMISSION_ALL
		);
		$share->setStatus(IShare::STATUS_ACCEPTED);
		$this->shareManager->updateShare($share);

		// user2 shares a file from the folder as link
		self::loginHelper(self::TEST_FILES_SHARING_API_USER2);

		$view = new View('/' . self::TEST_FILES_SHARING_API_USER2 . '/files');
		$view->mkdir('localDir');

		// move mount point to the folder "localDir"
		$result = $view->rename($this->folder, 'localDir/' . $this->folder);
		$this->assertTrue($result !== false);

		// try to share "localDir"
		$fileInfo2 = $view->getFileInfo('localDir');

		$this->assertTrue($fileInfo2 instanceof FileInfo);

		$pass = true;
		try {
			$this->share(
				IShare::TYPE_USER,
				'localDir',
				self::TEST_FILES_SHARING_API_USER2,
				self::TEST_FILES_SHARING_API_USER3,
				Constants::PERMISSION_ALL
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
			Filesystem::mount(self::$tempStorage, [], '/' . self::TEST_FILES_SHARING_API_USER1 . '/files' . self::TEST_FOLDER_NAME);
		}
	}

	/**
	 * Tests mounting a folder that is an external storage mount point.
	 */
	public function testShareStorageMountPoint(): void {
		$tempStorage = new Temporary([]);
		$tempStorage->file_put_contents('test.txt', 'abcdef');
		$tempStorage->getScanner()->scan('');

		$this->registerMount(self::TEST_FILES_SHARING_API_USER1, $tempStorage, self::TEST_FILES_SHARING_API_USER1 . '/files' . self::TEST_FOLDER_NAME);

		// logging in will auto-mount the temp storage for user1 as well
		self::loginHelper(self::TEST_FILES_SHARING_API_USER1);

		// user 1 shares the mount point folder with user2
		$share = $this->share(
			IShare::TYPE_USER,
			$this->folder,
			self::TEST_FILES_SHARING_API_USER1,
			self::TEST_FILES_SHARING_API_USER2,
			Constants::PERMISSION_ALL
		);
		$share->setStatus(IShare::STATUS_ACCEPTED);
		$this->shareManager->updateShare($share);

		// user2: check that mount point name appears correctly
		self::loginHelper(self::TEST_FILES_SHARING_API_USER2);

		$view = new View('/' . self::TEST_FILES_SHARING_API_USER2 . '/files');

		$this->assertTrue($view->file_exists($this->folder));
		$this->assertTrue($view->file_exists($this->folder . '/test.txt'));

		self::loginHelper(self::TEST_FILES_SHARING_API_USER1);

		$this->shareManager->deleteShare($share);

		\OC_Hook::clear('OC_Filesystem', 'post_initMountPoints');
		\OC_Hook::clear('\OCA\Files_Sharing\Tests\ApiTest', 'initTestMountPointsHook');
	}

	public static function datesProvider() {
		$date = new \DateTime();
		$date->setTime(0, 0);
		$date->add(new \DateInterval('P5D'));
		$date->setTimezone(new \DateTimeZone(date_default_timezone_get()));

		return [
			[$date->format('Y-m-d H:i:s'), true],
			['abc', false],
			[$date->format('Y-m-d H:i:s') . 'xyz', false],
		];
	}

	/**
	 * Make sure only ISO 8601 dates are accepted
	 */
	#[\PHPUnit\Framework\Attributes\DataProvider(methodName: 'datesProvider')]
	#[\PHPUnit\Framework\Attributes\Group('RoutingWeirdness')]
	public function testPublicLinkExpireDate($date, $valid): void {
		$ocs = $this->createOCS(self::TEST_FILES_SHARING_API_USER1);

		try {
			$result = $ocs->createShare($this->folder, Constants::PERMISSION_ALL, IShare::TYPE_LINK, null, 'false', '', null, $date);
			$this->assertTrue($valid);
		} catch (OCSNotFoundException $e) {
			$this->assertFalse($valid);
			$this->assertEquals('Invalid date. Format must be YYYY-MM-DD', $e->getMessage());
			$ocs->cleanup();
			return;
		}
		$ocs->cleanup();

		$data = $result->getData();
		$this->assertTrue(is_string($data['token']));
		$this->assertEquals(substr($date, 0, 10), substr($data['expiration'], 0, 10));

		// check for correct link
		$url = Server::get(IURLGenerator::class)->getAbsoluteURL('/index.php/s/' . $data['token']);
		$this->assertEquals($url, $data['url']);

		$share = $this->shareManager->getShareById('ocinternal:' . $data['id']);

		$this->assertEquals($date, $share->getExpirationDate()->format('Y-m-d H:i:s'));

		$this->shareManager->deleteShare($share);
	}

	#[\PHPUnit\Framework\Attributes\Group('RoutingWeirdness')]
	public function testCreatePublicLinkExpireDateValid(): void {
		$config = Server::get(IConfig::class);

		// enforce expire date, by default 7 days after the file was shared
		$config->setAppValue('core', 'shareapi_default_expire_date', 'yes');
		$config->setAppValue('core', 'shareapi_enforce_expire_date', 'yes');

		$date = new \DateTime();
		$date->add(new \DateInterval('P5D'));

		$ocs = $this->createOCS(self::TEST_FILES_SHARING_API_USER1);
		$result = $ocs->createShare($this->filename, Constants::PERMISSION_ALL, IShare::TYPE_LINK, null, 'false', '', null, $date->format('Y-m-d'));
		$ocs->cleanup();

		$data = $result->getData();
		$this->assertTrue(is_string($data['token']));
		$this->assertEquals($date->format('Y-m-d 00:00:00'), $data['expiration']);

		// check for correct link
		$url = Server::get(IURLGenerator::class)->getAbsoluteURL('/index.php/s/' . $data['token']);
		$this->assertEquals($url, $data['url']);

		$share = $this->shareManager->getShareById('ocinternal:' . $data['id']);
		$date->setTime(0, 0, 0);
		$this->assertEquals($date, $share->getExpirationDate());

		$this->shareManager->deleteShare($share);

		$config->setAppValue('core', 'shareapi_default_expire_date', 'no');
		$config->setAppValue('core', 'shareapi_enforce_expire_date', 'no');
	}

	public function testCreatePublicLinkExpireDateInvalidFuture(): void {
		$config = Server::get(IConfig::class);

		// enforce expire date, by default 7 days after the file was shared
		$config->setAppValue('core', 'shareapi_default_expire_date', 'yes');
		$config->setAppValue('core', 'shareapi_enforce_expire_date', 'yes');

		$date = new \DateTime();
		$date->add(new \DateInterval('P8D'));

		$ocs = $this->createOCS(self::TEST_FILES_SHARING_API_USER1);

		try {
			$ocs->createShare($this->filename, Constants::PERMISSION_ALL, IShare::TYPE_LINK, null, 'false', '', null, $date->format('Y-m-d'));
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
		$config = Server::get(IConfig::class);

		$date = new \DateTime();
		$date->sub(new \DateInterval('P8D'));

		$ocs = $this->createOCS(self::TEST_FILES_SHARING_API_USER1);

		try {
			$ocs->createShare($this->filename, Constants::PERMISSION_ALL, IShare::TYPE_LINK, null, 'false', '', null, $date->format('Y-m-d'));
			$this->fail();
		} catch (OCSException $e) {
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
	public function testInvisibleSharesUser(): void {
		// simulate a post request
		$ocs = $this->createOCS(self::TEST_FILES_SHARING_API_USER1);
		$result = $ocs->createShare($this->folder, Constants::PERMISSION_ALL, IShare::TYPE_USER, self::TEST_FILES_SHARING_API_USER2);
		$ocs->cleanup();
		$data = $result->getData();

		$topId = $data['id'];

		$ocs = $this->createOCS(self::TEST_FILES_SHARING_API_USER2);
		$ocs->acceptShare($topId);
		$ocs->cleanup();

		$ocs = $this->createOCS(self::TEST_FILES_SHARING_API_USER2);
		$ocs->createShare($this->folder, Constants::PERMISSION_ALL, IShare::TYPE_LINK);
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
	public function testInvisibleSharesGroup(): void {
		// simulate a post request
		$ocs = $this->createOCS(self::TEST_FILES_SHARING_API_USER1);
		$result = $ocs->createShare($this->folder, Constants::PERMISSION_ALL, IShare::TYPE_GROUP, self::TEST_FILES_SHARING_API_GROUP1);
		$ocs->cleanup();
		$data = $result->getData();

		$topId = $data['id'];

		$ocs = $this->createOCS(self::TEST_FILES_SHARING_API_USER2);
		$ocs->acceptShare($topId);
		$ocs->cleanup();

		\OC_Util::tearDownFS();

		$ocs = $this->createOCS(self::TEST_FILES_SHARING_API_USER2);
		$ocs->createShare($this->folder, Constants::PERMISSION_ALL, IShare::TYPE_LINK);
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
