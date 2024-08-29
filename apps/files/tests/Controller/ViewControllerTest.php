<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Daniel Kesselberg <mail@danielkesselberg.de>
 * @author Joas Schilling <coding@schilljs.com>
 * @author John Molakvoæ <skjnldsv@protonmail.com>
 * @author Julius Härtl <jus@bitgrid.net>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Michael Weimann <mail@michael-weimann.eu>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Nina Pypchenko <22447785+nina-py@users.noreply.github.com>
 * @author Robin Appelman <robin@icewind.nl>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Vincent Petry <vincent@nextcloud.com>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>
 *
 */
namespace OCA\Files\Tests\Controller;

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
use OCP\IRequest;
use OCP\IURLGenerator;
use OCP\IUser;
use OCP\IUserSession;
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
	/** @var IRequest|\PHPUnit\Framework\MockObject\MockObject */
	private $request;
	/** @var IURLGenerator */
	private $urlGenerator;
	/** @var IConfig|\PHPUnit\Framework\MockObject\MockObject */
	private $config;
	/** @var IEventDispatcher */
	private $eventDispatcher;
	/** @var ViewController|\PHPUnit\Framework\MockObject\MockObject */
	private $viewController;
	/** @var IUser|\PHPUnit\Framework\MockObject\MockObject */
	private $user;
	/** @var IUserSession|\PHPUnit\Framework\MockObject\MockObject */
	private $userSession;
	/** @var IAppManager|\PHPUnit\Framework\MockObject\MockObject */
	private $appManager;
	/** @var IRootFolder|\PHPUnit\Framework\MockObject\MockObject */
	private $rootFolder;
	/** @var IInitialState|\PHPUnit\Framework\MockObject\MockObject */
	private $initialState;
	/** @var ITemplateManager|\PHPUnit\Framework\MockObject\MockObject */
	private $templateManager;
	/** @var UserConfig|\PHPUnit\Framework\MockObject\MockObject */
	private $userConfig;
	/** @var ViewConfig|\PHPUnit\Framework\MockObject\MockObject */
	private $viewConfig;

	/** @var ICacheFactory|\PHPUnit\Framework\MockObject\MockObject */
	private $cacheFactory;
	/** @var LoggerInterface|\PHPUnit\Framework\MockObject\MockObject */
	private $logger;
	/** @var IEventLogger|\PHPUnit\Framework\MockObject\MockObject */
	private $eventLogger;
	/** @var ContainerInterface|\PHPUnit\Framework\MockObject\MockObject */
	private $container;
	/** @var Router */
	private $router;


	protected function setUp(): void {
		parent::setUp();
		$this->appManager = $this->createMock(IAppManager::class);
		$this->config = $this->createMock(IConfig::class);
		$this->eventDispatcher = $this->createMock(IEventDispatcher::class);
		$this->initialState = $this->createMock(IInitialState::class);
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
			->method('getAppPath')
			->willReturnCallback(fn (string $appid): string => \OC::$SERVERROOT . '/apps/' . $appid);

		$this->cacheFactory = $this->createMock(ICacheFactory::class);
		$this->logger = $this->createMock(LoggerInterface::class);
		$this->eventLogger = $this->createMock(IEventLogger::class);
		$this->container = $this->createMock(ContainerInterface::class);
		$this->router = new Router(
			$this->logger,
			$this->request,
			$this->config,
			$this->eventLogger,
			$this->container
		);

		// Create a real URLGenerator instance to generate URLs
		$this->urlGenerator = new URLGenerator(
			$this->config,
			$this->userSession,
			$this->cacheFactory,
			$this->request,
			$this->router
		);

		$this->viewController = $this->getMockBuilder(ViewController::class)
			->setConstructorArgs([
				'files',
				$this->request,
				$this->urlGenerator,
				$this->config,
				$this->eventDispatcher,
				$this->userSession,
				$this->appManager,
				$this->rootFolder,
				$this->initialState,
				$this->templateManager,
				$this->userConfig,
				$this->viewConfig,
			])
		->setMethods([
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
		->expects($this->any())
			->method('getSystemValue')
			->with('forbidden_chars', [])
			->willReturn([]);
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

	public function dataTestShortRedirect(): array {
		// openfile is true by default
		// and will be evaluated as truthy
		return [
			[null,		'/index.php/apps/files/files/123456?openfile=true'],
			['',		'/index.php/apps/files/files/123456?openfile=true'],
			['true',		'/index.php/apps/files/files/123456?openfile=true'],
			['false',	'/index.php/apps/files/files/123456?openfile=false'],
		];
	}

	/**
	 * @dataProvider dataTestShortRedirect
	 */
	public function testShortRedirect($openfile, $result) {
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

		$response = $this->viewController->showFile(123456, $openfile);
		$this->assertStringContainsString($result, $response->getHeaders()['Location']);
	}

	public function testShowFileRouteWithTrashedFile(): void {
		$this->appManager->expects($this->once())
			->method('isEnabledForUser')
			->willReturn(true);

		$parentNode = $this->getMockBuilder(Folder::class)->getMock();
		$parentNode->expects($this->once())
			->method('getPath')
			->willReturn('testuser1/files_trashbin/files/test.d1462861890/sub');

		$baseFolderFiles = $this->getMockBuilder(Folder::class)->getMock();
		$baseFolderTrash = $this->getMockBuilder(Folder::class)->getMock();

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

		$node = $this->getMockBuilder(File::class)->getMock();
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
