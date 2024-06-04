<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
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
	/** @var IUser|\PHPUnit\Framework\MockObject\MockObject */
	private $user;
	/** @var IUserSession|\PHPUnit\Framework\MockObject\MockObject */
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

		$baseFolderFiles = $this->getMockBuilder(Folder::class)->getMock();

		$this->rootFolder->expects($this->any())
			->method('getUserFolder')
			->with('testuser1')
			->willReturn($baseFolderFiles);

		$this->config
			->expects($this->any())
			->method('getAppValue')
			->willReturnArgument(2);

		$expected = new Http\TemplateResponse(
			'files',
			'index',
		);
		$policy = new Http\ContentSecurityPolicy();
		$policy->addAllowedWorkerSrcDomain('\'self\'');
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

		$this->urlGenerator
			->expects($this->once())
			->method('linkToRoute')
			->with('files.view.indexViewFileid', ['view' => 'trashbin', 'dir' => '/test.d1462861890/sub', 'fileid' => '123'])
			->willReturn('/apps/files/trashbin/123?dir=/test.d1462861890/sub');

		$expected = new Http\RedirectResponse('/apps/files/trashbin/123?dir=/test.d1462861890/sub');
		$this->assertEquals($expected, $this->viewController->index('', '', '123'));
	}
}
