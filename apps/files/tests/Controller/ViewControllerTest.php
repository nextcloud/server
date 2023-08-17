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

use OCA\Files\Activity\Helper;
use OCA\Files\Controller\ViewController;
use OCA\Files\Service\UserConfig;
use OCA\Files\Service\ViewConfig;
use OCP\App\IAppManager;
use OCP\AppFramework\Http;
use OCP\AppFramework\Services\IInitialState;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\Files\File;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use OCP\Files\Template\ITemplateManager;
use OCP\IConfig;
use OCP\IL10N;
use OCP\IRequest;
use OCP\IURLGenerator;
use OCP\IUser;
use OCP\IUserSession;
use OCP\Share\IManager;
use OCP\Template;
use Test\TestCase;

/**
 * Class ViewControllerTest
 *
 * @package OCA\Files\Tests\Controller
 */
class ViewControllerTest extends TestCase {
	/** @var IRequest|\PHPUnit\Framework\MockObject\MockObject */
	private $request;
	/** @var IURLGenerator|\PHPUnit\Framework\MockObject\MockObject */
	private $urlGenerator;
	/** @var IL10N */
	private $l10n;
	/** @var IConfig|\PHPUnit\Framework\MockObject\MockObject */
	private $config;
	/** @var IEventDispatcher */
	private $eventDispatcher;
	/** @var ViewController|\PHPUnit\Framework\MockObject\MockObject */
	private $viewController;
	/** @var IUser */
	private $user;
	/** @var IUserSession */
	private $userSession;
	/** @var IAppManager|\PHPUnit\Framework\MockObject\MockObject */
	private $appManager;
	/** @var IRootFolder|\PHPUnit\Framework\MockObject\MockObject */
	private $rootFolder;
	/** @var Helper|\PHPUnit\Framework\MockObject\MockObject */
	private $activityHelper;
	/** @var IInitialState|\PHPUnit\Framework\MockObject\MockObject */
	private $initialState;
	/** @var ITemplateManager|\PHPUnit\Framework\MockObject\MockObject */
	private $templateManager;
	/** @var IManager|\PHPUnit\Framework\MockObject\MockObject */
	private $shareManager;
	/** @var UserConfig|\PHPUnit\Framework\MockObject\MockObject */
	private $userConfig;
	/** @var ViewConfig|\PHPUnit\Framework\MockObject\MockObject */
	private $viewConfig;

	protected function setUp(): void {
		parent::setUp();
		$this->request = $this->getMockBuilder(IRequest::class)->getMock();
		$this->urlGenerator = $this->getMockBuilder(IURLGenerator::class)->getMock();
		$this->l10n = $this->getMockBuilder(IL10N::class)->getMock();
		$this->config = $this->getMockBuilder(IConfig::class)->getMock();
		$this->eventDispatcher = $this->createMock(IEventDispatcher::class);
		$this->userSession = $this->getMockBuilder(IUserSession::class)->getMock();
		$this->appManager = $this->getMockBuilder('\OCP\App\IAppManager')->getMock();
		$this->user = $this->getMockBuilder(IUser::class)->getMock();
		$this->user->expects($this->any())
			->method('getUID')
			->willReturn('testuser1');
		$this->userSession->expects($this->any())
			->method('getUser')
			->willReturn($this->user);
		$this->rootFolder = $this->getMockBuilder('\OCP\Files\IRootFolder')->getMock();
		$this->activityHelper = $this->createMock(Helper::class);
		$this->initialState = $this->createMock(IInitialState::class);
		$this->templateManager = $this->createMock(ITemplateManager::class);
		$this->shareManager = $this->createMock(IManager::class);
		$this->userConfig = $this->createMock(UserConfig::class);
		$this->viewConfig = $this->createMock(ViewConfig::class);
		$this->viewController = $this->getMockBuilder('\OCA\Files\Controller\ViewController')
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
				$this->activityHelper,
				$this->initialState,
				$this->templateManager,
				$this->shareManager,
				$this->userConfig,
				$this->viewConfig,
			])
		->setMethods([
			'getStorageInfo',
			'renderScript'
		])
		->getMock();
	}

	public function testIndexWithRegularBrowser() {
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

		$this->config
			->expects($this->any())
			->method('getAppValue')
			->willReturnArgument(2);
		$this->shareManager->method('shareApiAllowLinks')
			->willReturn(true);

		$nav = new Template('files', 'appnavigation');
		$nav->assign('navigationItems', [
			'files' => [
				'id' => 'files',
				'appname' => 'files',
				'script' => 'list.php',
				'order' => 0,
				'name' => \OC::$server->getL10N('files')->t('All files'),
				'active' => false,
				'icon' => '',
				'type' => 'link',
				'classes' => '',
				'expanded' => false,
				'unread' => 0,
			],
			'systemtagsfilter' => [
				'id' => 'systemtagsfilter',
				'appname' => 'systemtags',
				'script' => 'list.php',
				'order' => 25,
				'name' => \OC::$server->getL10N('systemtags')->t('Tags'),
				'active' => false,
				'icon' => '',
				'type' => 'link',
				'classes' => '',
				'expanded' => false,
				'unread' => 0,
			],
		]);

		$expected = new Http\TemplateResponse(
			'files',
			'index',
			[
				'usedSpacePercent' => 123,
				'owner' => 'MyName',
				'ownerDisplayName' => 'MyDisplayName',
				'isPublic' => false,
				'defaultFileSorting' => 'basename',
				'defaultFileSortingDirection' => 'asc',
				'showHiddenFiles' => 0,
				'cropImagePreviews' => 1,
				'fileNotFound' => 0,
				'allowShareWithLink' => 'yes',
				'appNavigation' => $nav,
				'appContents' => [
					'files' => [
						'id' => 'files',
						'content' => null,
					],
					'systemtagsfilter' => [
						'id' => 'systemtagsfilter',
						'content' => null,
					],
				],
				'hiddenFields' => [],
				'showgridview' => null
			]
		);
		$policy = new Http\ContentSecurityPolicy();
		$policy->addAllowedFrameDomain('\'self\'');
		$expected->setContentSecurityPolicy($policy);

		$this->activityHelper->method('getFavoriteFilePaths')
			->with($this->user->getUID())
			->willReturn([
				'item' => [],
				'folders' => [
					'/test1',
					'/test2/',
					'/test3/sub4',
					'/test5/sub6/',
				],
			]);

		$this->assertEquals($expected, $this->viewController->index('MyDir', 'MyView'));
	}

	public function testShowFileRouteWithFolder() {
		$node = $this->getMockBuilder(Folder::class)->getMock();
		$node->expects($this->once())
			->method('getPath')
			->willReturn('/testuser1/files/test/sub');

		$baseFolder = $this->getMockBuilder(Folder::class)->getMock();

		$this->rootFolder->expects($this->once())
			->method('getUserFolder')
			->with('testuser1')
			->willReturn($baseFolder);

		$baseFolder->expects($this->once())
			->method('getById')
			->with(123)
			->willReturn([$node]);
		$baseFolder->expects($this->once())
			->method('getRelativePath')
			->with('/testuser1/files/test/sub')
			->willReturn('/test/sub');

		$this->urlGenerator
			->expects($this->once())
			->method('linkToRoute')
			->with('files.view.index', ['dir' => '/test/sub'])
			->willReturn('/apps/files/?dir=/test/sub');

		$expected = new Http\RedirectResponse('/apps/files/?dir=/test/sub');
		$this->assertEquals($expected, $this->viewController->index('', '', '123'));
	}

	public function testShowFileRouteWithFile() {
		$parentNode = $this->getMockBuilder(Folder::class)->getMock();
		$parentNode->expects($this->once())
			->method('getPath')
			->willReturn('testuser1/files/test');

		$baseFolder = $this->getMockBuilder(Folder::class)->getMock();

		$this->rootFolder->expects($this->once())
			->method('getUserFolder')
			->with('testuser1')
			->willReturn($baseFolder);

		$node = $this->getMockBuilder(File::class)->getMock();
		$node->expects($this->once())
			->method('getParent')
			->willReturn($parentNode);
		$node->expects($this->once())
			->method('getName')
			->willReturn('somefile.txt');

		$baseFolder->expects($this->once())
			->method('getById')
			->with(123)
			->willReturn([$node]);
		$baseFolder->expects($this->once())
			->method('getRelativePath')
			->with('testuser1/files/test')
			->willReturn('/test');

		$this->urlGenerator
			->expects($this->once())
			->method('linkToRoute')
			->with('files.view.index', ['dir' => '/test', 'scrollto' => 'somefile.txt'])
			->willReturn('/apps/files/?dir=/test/sub&scrollto=somefile.txt');

		$expected = new Http\RedirectResponse('/apps/files/?dir=/test/sub&scrollto=somefile.txt');
		$this->assertEquals($expected, $this->viewController->index('', '', '123'));
	}

	public function testShowFileRouteWithInvalidFileId() {
		$baseFolder = $this->getMockBuilder(Folder::class)->getMock();
		$this->rootFolder->expects($this->once())
			->method('getUserFolder')
			->with('testuser1')
			->willReturn($baseFolder);

		$baseFolder->expects($this->once())
			->method('getById')
			->with(123)
			->willReturn([]);

		$this->urlGenerator->expects($this->once())
			->method('linkToRoute')
			->with('files.view.index', ['fileNotFound' => true])
			->willReturn('redirect.url');

		$response = $this->viewController->index('', 'MyView', '123');
		$this->assertInstanceOf('OCP\AppFramework\Http\RedirectResponse', $response);
		$this->assertEquals('redirect.url', $response->getRedirectURL());
	}

	public function testShowFileRouteWithTrashedFile() {
		$this->appManager->expects($this->once())
			->method('isEnabledForUser')
			->with('files_trashbin')
			->willReturn(true);

		$parentNode = $this->getMockBuilder(Folder::class)->getMock();
		$parentNode->expects($this->once())
			->method('getPath')
			->willReturn('testuser1/files_trashbin/files/test.d1462861890/sub');

		$baseFolderFiles = $this->getMockBuilder(Folder::class)->getMock();
		$baseFolderTrash = $this->getMockBuilder(Folder::class)->getMock();

		$this->rootFolder->expects($this->once())
			->method('getUserFolder')
			->with('testuser1')
			->willReturn($baseFolderFiles);
		$this->rootFolder->expects($this->once())
			->method('get')
			->with('testuser1/files_trashbin/files/')
			->willReturn($baseFolderTrash);

		$baseFolderFiles->expects($this->once())
			->method('getById')
			->with(123)
			->willReturn([]);

		$node = $this->getMockBuilder(File::class)->getMock();
		$node->expects($this->once())
			->method('getParent')
			->willReturn($parentNode);
		$node->expects($this->once())
			->method('getName')
			->willReturn('somefile.txt');

		$baseFolderTrash->expects($this->once())
			->method('getById')
			->with(123)
			->willReturn([$node]);
		$baseFolderTrash->expects($this->once())
			->method('getRelativePath')
			->with('testuser1/files_trashbin/files/test.d1462861890/sub')
			->willReturn('/test.d1462861890/sub');

		$this->urlGenerator
			->expects($this->once())
			->method('linkToRoute')
			->with('files.view.index', ['view' => 'trashbin', 'dir' => '/test.d1462861890/sub', 'scrollto' => 'somefile.txt'])
			->willReturn('/apps/files/?view=trashbin&dir=/test.d1462861890/sub&scrollto=somefile.txt');

		$expected = new Http\RedirectResponse('/apps/files/?view=trashbin&dir=/test.d1462861890/sub&scrollto=somefile.txt');
		$this->assertEquals($expected, $this->viewController->index('', '', '123'));
	}
}
