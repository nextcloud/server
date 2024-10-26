<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Files\Tests\Controller;

use OC\Files\FilenameValidator;
use OCA\Files\Controller\ViewController;
use OCA\Files\Service\UserConfig;
use OCA\Files\Service\ViewConfig;
use OCP\App\IAppManager;
use OCP\AppFramework\Http\ContentSecurityPolicy;
use OCP\AppFramework\Http\RedirectResponse;
use OCP\AppFramework\Http\TemplateResponse;
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
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

/**
 * Class ViewControllerTest
 *
 * @package OCA\Files\Tests\Controller
 */
class ViewControllerTest extends TestCase {
	private IRequest&MockObject $request;
	private IURLGenerator&MockObject $urlGenerator;
	private IL10N&MockObject $l10n;
	private IConfig&MockObject $config;
	private IEventDispatcher $eventDispatcher;
	private IUser&MockObject $user;
	private IUserSession&MockObject $userSession;
	private IAppManager&MockObject $appManager;
	private IRootFolder&MockObject $rootFolder;
	private IInitialState&MockObject $initialState;
	private ITemplateManager&MockObject $templateManager;
	private UserConfig&MockObject $userConfig;
	private ViewConfig&MockObject $viewConfig;

	private ViewController&MockObject $viewController;

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
		$this->initialState = $this->createMock(IInitialState::class);
		$this->templateManager = $this->createMock(ITemplateManager::class);
		$this->userConfig = $this->createMock(UserConfig::class);
		$this->viewConfig = $this->createMock(ViewConfig::class);

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

	public function testShowFileRouteWithTrashedFile(): void {
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

		$expected = new RedirectResponse('/apps/files/trashbin/123?dir=/test.d1462861890/sub');
		$this->assertEquals($expected, $this->viewController->index('', '', '123'));
	}
}
