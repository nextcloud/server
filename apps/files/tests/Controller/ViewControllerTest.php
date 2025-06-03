<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Files\Tests\Controller;

use OC\Files\FilenameValidator;
use OC\Route\Router;
use OC\URLGenerator;
use OCA\Files\Controller\ViewController;
use OCA\Files\Service\UserConfig;
use OCA\Files\Service\ViewConfig;
use OCP\App\IAppManager;
use OCP\AppFramework\Http\ContentSecurityPolicy;
use OCP\AppFramework\Http\RedirectResponse;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Services\IInitialState;
use OCP\Diagnostics\IEventLogger;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\Files\File;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use OCP\Files\Template\ITemplateManager;
use OCP\ICacheFactory;
use OCP\IConfig;
use OCP\IL10N;
use OCP\IRequest;
use OCP\IURLGenerator;
use OCP\IUser;
use OCP\IUserSession;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Test\TestCase;

/**
 * Class ViewControllerTest
 *
 * @group RoutingWeirdness
 *
 * @package OCA\Files\Tests\Controller
 */
class ViewControllerTest extends TestCase {
	private ContainerInterface&MockObject $container;
	private IAppManager&MockObject $appManager;
	private ICacheFactory&MockObject $cacheFactory;
	private IConfig&MockObject $config;
	private IEventDispatcher $eventDispatcher;
	private IEventLogger&MockObject $eventLogger;
	private IInitialState&MockObject $initialState;
	private IL10N&MockObject $l10n;
	private IRequest&MockObject $request;
	private IRootFolder&MockObject $rootFolder;
	private ITemplateManager&MockObject $templateManager;
	private IURLGenerator $urlGenerator;
	private IUser&MockObject $user;
	private IUserSession&MockObject $userSession;
	private LoggerInterface&MockObject $logger;
	private UserConfig&MockObject $userConfig;
	private ViewConfig&MockObject $viewConfig;
	private Router $router;

	private ViewController&MockObject $viewController;

	protected function setUp(): void {
		parent::setUp();
		$this->appManager = $this->createMock(IAppManager::class);
		$this->config = $this->createMock(IConfig::class);
		$this->eventDispatcher = $this->createMock(IEventDispatcher::class);
		$this->initialState = $this->createMock(IInitialState::class);
		$this->l10n = $this->createMock(IL10N::class);
		$this->request = $this->createMock(IRequest::class);
		$this->rootFolder = $this->createMock(IRootFolder::class);
		$this->templateManager = $this->createMock(ITemplateManager::class);
		$this->userConfig = $this->createMock(UserConfig::class);
		$this->userSession = $this->createMock(IUserSession::class);
		$this->viewConfig = $this->createMock(ViewConfig::class);

		$this->user = $this->getMockBuilder(IUser::class)->getMock();
		$this->user->expects($this->any())
			->method('getUID')
			->willReturn('testuser1');
		$this->userSession->expects($this->any())
			->method('getUser')
			->willReturn($this->user);

		// Make sure we know the app is enabled
		$this->appManager->expects($this->any())
			->method('cleanAppId')
			->willReturnArgument(0);
		$this->appManager->expects($this->any())
			->method('getAppPath')
			->willReturnCallback(fn (string $appid): string => \OC::$SERVERROOT . '/apps/' . $appid);
		$this->appManager->expects($this->any())
			->method('isAppLoaded')
			->willReturn(true);

		$this->cacheFactory = $this->createMock(ICacheFactory::class);
		$this->logger = $this->createMock(LoggerInterface::class);
		$this->eventLogger = $this->createMock(IEventLogger::class);
		$this->container = $this->createMock(ContainerInterface::class);
		$this->router = new Router(
			$this->logger,
			$this->request,
			$this->config,
			$this->eventLogger,
			$this->container,
			$this->appManager,
		);

		// Create a real URLGenerator instance to generate URLs
		$this->urlGenerator = new URLGenerator(
			$this->config,
			$this->userSession,
			$this->cacheFactory,
			$this->request,
			$this->router
		);

		$filenameValidator = $this->createMock(FilenameValidator::class);
		$this->viewController = $this->getMockBuilder(ViewController::class)
			->setConstructorArgs([
				'files',
				$this->request,
				$this->urlGenerator,
				$this->l10n,
				$this->config,
				$this->eventDispatcher,
				$this->userSession,
				$this->appManager,
				$this->rootFolder,
				$this->initialState,
				$this->templateManager,
				$this->userConfig,
				$this->viewConfig,
				$filenameValidator,
			])
			->onlyMethods([
				'getStorageInfo',
			])
			->getMock();
	}

	public function testIndexWithRegularBrowser(): void {
		$this->viewController
			->expects($this->any())
			->method('getStorageInfo')
			->willReturn([
				'used' => 123,
				'quota' => 100,
				'total' => 100,
				'relative' => 123,
				'owner' => 'MyName',
				'ownerDisplayName' => 'MyDisplayName',
			]);

		$this->config
			->method('getUserValue')
			->willReturnMap([
				[$this->user->getUID(), 'files', 'file_sorting', 'name', 'name'],
				[$this->user->getUID(), 'files', 'file_sorting_direction', 'asc', 'asc'],
				[$this->user->getUID(), 'files', 'files_sorting_configs', '{}', '{}'],
				[$this->user->getUID(), 'files', 'show_hidden', false, false],
				[$this->user->getUID(), 'files', 'crop_image_previews', true, true],
				[$this->user->getUID(), 'files', 'show_grid', true],
			]);

		$baseFolderFiles = $this->getMockBuilder(Folder::class)->getMock();

		$this->rootFolder->expects($this->any())
			->method('getUserFolder')
			->with('testuser1')
			->willReturn($baseFolderFiles);

		$this->config
			->expects($this->any())
			->method('getAppValue')
			->willReturnArgument(2);

		$expected = new TemplateResponse(
			'files',
			'index',
		);
		$policy = new ContentSecurityPolicy();
		$policy->addAllowedWorkerSrcDomain('\'self\'');
		$policy->addAllowedFrameDomain('\'self\'');
		$expected->setContentSecurityPolicy($policy);

		$this->assertEquals($expected, $this->viewController->index('MyDir', 'MyView'));
	}

	public static function dataTestShortRedirect(): array {
		// openfile is true by default
		// opendetails is undefined by default
		// both will be evaluated as truthy
		return [
			[null,		null,		'/index.php/apps/files/files/123456?openfile=true'],
			['',		null,		'/index.php/apps/files/files/123456?openfile=true'],
			[null,		'',			'/index.php/apps/files/files/123456?openfile=true&opendetails=true'],
			['',		'', 		'/index.php/apps/files/files/123456?openfile=true&opendetails=true'],
			['false',	'',			'/index.php/apps/files/files/123456?openfile=false'],
			[null,		'false',	'/index.php/apps/files/files/123456?openfile=true&opendetails=false'],
			['true',	'false',	'/index.php/apps/files/files/123456?openfile=true&opendetails=false'],
			['false',	'true',		'/index.php/apps/files/files/123456?openfile=false&opendetails=true'],
			['false',	'false',	'/index.php/apps/files/files/123456?openfile=false&opendetails=false'],
		];
	}

	/**
	 * @dataProvider dataTestShortRedirect
	 */
	public function testShortRedirect(?string $openfile, ?string $opendetails, string $result): void {
		$this->appManager->expects($this->any())
			->method('isEnabledForUser')
			->with('files')
			->willReturn(true);

		$baseFolderFiles = $this->getMockBuilder(Folder::class)->getMock();
		$this->rootFolder->expects($this->any())
			->method('getUserFolder')
			->with('testuser1')
			->willReturn($baseFolderFiles);

		$parentNode = $this->getMockBuilder(Folder::class)->getMock();
		$parentNode->expects($this->once())
			->method('getPath')
			->willReturn('testuser1/files/Folder');

		$node = $this->getMockBuilder(File::class)->getMock();
		$node->expects($this->once())
			->method('getParent')
			->willReturn($parentNode);

		$baseFolderFiles->expects($this->any())
			->method('getFirstNodeById')
			->with(123456)
			->willReturn($node);

		$response = $this->viewController->showFile('123456', $opendetails, $openfile);
		$this->assertStringContainsString($result, $response->getHeaders()['Location']);
	}

	public function testShowFileRouteWithTrashedFile(): void {
		$this->appManager->expects($this->exactly(2))
			->method('isEnabledForUser')
			->willReturn(true);

		$parentNode = $this->createMock(Folder::class);
		$parentNode->expects($this->once())
			->method('getPath')
			->willReturn('testuser1/files_trashbin/files/test.d1462861890/sub');

		$baseFolderFiles = $this->createMock(Folder::class);
		$baseFolderTrash = $this->createMock(Folder::class);

		$this->rootFolder->expects($this->any())
			->method('getUserFolder')
			->with('testuser1')
			->willReturn($baseFolderFiles);
		$this->rootFolder->expects($this->once())
			->method('get')
			->with('testuser1/files_trashbin/files/')
			->willReturn($baseFolderTrash);

		$baseFolderFiles->expects($this->any())
			->method('getFirstNodeById')
			->with(123)
			->willReturn(null);

		$node = $this->createMock(File::class);
		$node->expects($this->once())
			->method('getParent')
			->willReturn($parentNode);

		$baseFolderTrash->expects($this->once())
			->method('getFirstNodeById')
			->with(123)
			->willReturn($node);
		$baseFolderTrash->expects($this->once())
			->method('getRelativePath')
			->with('testuser1/files_trashbin/files/test.d1462861890/sub')
			->willReturn('/test.d1462861890/sub');

		$expected = new RedirectResponse('/index.php/apps/files/trashbin/123?dir=/test.d1462861890/sub');
		$this->assertEquals($expected, $this->viewController->index('', '', '123'));
	}
}
