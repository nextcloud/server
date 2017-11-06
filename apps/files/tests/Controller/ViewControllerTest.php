<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Christoph Wurst <christoph@owncloud.com>
 * @author Jan-Christoph Borchardt <hey@jancborchardt.net>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin Appelman <robin@icewind.nl>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Vincent Petry <pvince81@owncloud.com>
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

namespace OCA\Files\Tests\Controller;

use OCA\Files\Controller\ViewController;
use OCP\AppFramework\Http;
use OCP\Files\File;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use OCP\IUser;
use OCP\Template;
use Test\TestCase;
use OCP\IRequest;
use OCP\IURLGenerator;
use OCP\IL10N;
use OCP\IConfig;
use OCP\IUserSession;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use OCP\App\IAppManager;

/**
 * Class ViewControllerTest
 *
 * @package OCA\Files\Tests\Controller
 */
class ViewControllerTest extends TestCase {
	/** @var IRequest|\PHPUnit_Framework_MockObject_MockObject */
	private $request;
	/** @var IURLGenerator|\PHPUnit_Framework_MockObject_MockObject */
	private $urlGenerator;
	/** @var IL10N */
	private $l10n;
	/** @var IConfig|\PHPUnit_Framework_MockObject_MockObject */
	private $config;
	/** @var EventDispatcherInterface */
	private $eventDispatcher;
	/** @var ViewController|\PHPUnit_Framework_MockObject_MockObject */
	private $viewController;
	/** @var IUser */
	private $user;
	/** @var IUserSession */
	private $userSession;
	/** @var IAppManager|\PHPUnit_Framework_MockObject_MockObject */
	private $appManager;
	/** @var IRootFolder|\PHPUnit_Framework_MockObject_MockObject */
	private $rootFolder;

	public function setUp() {
		parent::setUp();
		$this->request = $this->getMockBuilder(IRequest::class)->getMock();
		$this->urlGenerator = $this->getMockBuilder(IURLGenerator::class)->getMock();
		$this->l10n = $this->getMockBuilder(IL10N::class)->getMock();
		$this->config = $this->getMockBuilder(IConfig::class)->getMock();
		$this->eventDispatcher = $this->getMockBuilder('\Symfony\Component\EventDispatcher\EventDispatcherInterface')->getMock();
		$this->userSession = $this->getMockBuilder(IUserSession::class)->getMock();
		$this->appManager = $this->getMockBuilder('\OCP\App\IAppManager')->getMock();
		$this->user = $this->getMockBuilder(IUser::class)->getMock();
		$this->user->expects($this->any())
			->method('getUID')
			->will($this->returnValue('testuser1'));
		$this->userSession->expects($this->any())
			->method('getUser')
			->will($this->returnValue($this->user));
		$this->rootFolder = $this->getMockBuilder('\OCP\Files\IRootFolder')->getMock();
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
			$this->rootFolder
		])
		->setMethods([
			'getStorageInfo',
			'renderScript'
		])
		->getMock();
	}

	public function testIndexWithRegularBrowser() {
		$this->viewController
			->expects($this->once())
			->method('getStorageInfo')
			->will($this->returnValue([
				'used' => 123,
				'quota' => 100,
				'total' => 100,
				'relative' => 123,
				'owner' => 'MyName',
				'ownerDisplayName' => 'MyDisplayName',
			]));
		$this->config->expects($this->exactly(3))
			->method('getUserValue')
			->will($this->returnValueMap([
				[$this->user->getUID(), 'files', 'file_sorting', 'name', 'name'],
				[$this->user->getUID(), 'files', 'file_sorting_direction', 'asc', 'asc'],
				[$this->user->getUID(), 'files', 'show_hidden', false, false],
			]));

		$this->config
			->expects($this->any())
			->method('getAppValue')
			->will($this->returnArgument(2));

		$nav = new Template('files', 'appnavigation');
		$nav->assign('usage_relative', 123);
		$nav->assign('usage', '123 B');
		$nav->assign('quota', 100);
		$nav->assign('total_space', '100 B');
		$nav->assign('navigationItems', [
			[
				'id' => 'files',
				'appname' => 'files',
				'script' => 'list.php',
				'order' => 0,
				'name' => \OC::$server->getL10N('files')->t('All files'),
				'active' => false,
				'icon' => '',
				'type' => 'link',
				'classes' => '',
			],
			[
				'id' => 'recent',
				'appname' => 'files',
				'script' => 'recentlist.php',
				'order' => 2,
				'name' => \OC::$server->getL10N('files')->t('Recent'),
				'active' => false,
				'icon' => '',
				'type' => 'link',
				'classes' => '',
			],
			[
				'id' => 'favorites',
				'appname' => 'files',
				'script' => 'simplelist.php',
				'order' => 5,
				'name' => null,
				'active' => false,
				'icon' => '',
				'type' => 'link',
				'classes' => '',
			],
			[
			'id' => 'sharingin',
				'appname' => 'files_sharing',
				'script' => 'list.php',
				'order' => 15,
				'name' => \OC::$server->getL10N('files_sharing')->t('Shared with you'),
				'active' => false,
				'icon' => '',
				'type' => 'link',
				'classes' => '',
			],
			[
			'id' => 'sharingout',
				'appname' => 'files_sharing',
				'script' => 'list.php',
				'order' => 16,
				'name' => \OC::$server->getL10N('files_sharing')->t('Shared with others'),
				'active' => false,
				'icon' => '',
				'type' => 'link',
				'classes' => '',
			],
			[
				'id' => 'sharinglinks',
				'appname' => 'files_sharing',
				'script' => 'list.php',
				'order' => 17,
				'name' => \OC::$server->getL10N('files_sharing')->t('Shared by link', []),
				'active' => false,
				'icon' => '',
				'type' => 'link',
				'classes' => '',
			],
			[
				'id' => 'systemtagsfilter',
				'appname' => 'systemtags',
				'script' => 'list.php',
				'order' => 25,
				'name' => \OC::$server->getL10N('systemtags')->t('Tags'),
				'active' => false,
				'icon' => '',
				'type' => 'link',
				'classes' => '',
			],
			[
				'id' => 'trashbin',
				'appname' => 'files_trashbin',
				'script' => 'list.php',
				'order' => 50,
				'name' => \OC::$server->getL10N('files_trashbin')->t('Deleted files'),
				'active' => false,
				'icon' => '',
				'type' => 'link',
				'classes' => 'pinned',
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
				'defaultFileSorting' => 'name',
				'defaultFileSortingDirection' => 'asc',
				'showHiddenFiles' => 0,
				'fileNotFound' => 0,
				'allowShareWithLink' => 'yes',
				'appNavigation' => $nav,
				'appContents' => [
					[
						'id' => 'files',
						'content' => null,
					],
					[
						'id' => 'recent',
						'content' => null,
					],
					[
						'id' => 'favorites',
						'content' => null,
					],
					[
						'id' => 'sharingin',
						'content' => null,
					],
					[
						'id' => 'sharingout',
						'content' => null,
					],
					[
						'id' => 'sharinglinks',
						'content' => null,
					],
					[
						'id' => 'systemtagsfilter',
						'content' => null,
					],
					[
						'id' => 'trashbin',
						'content' => null,
					],
				],
				'hiddenFields' => [],
			]
		);
		$policy = new Http\ContentSecurityPolicy();
		$policy->addAllowedFrameDomain('\'self\'');
		$expected->setContentSecurityPolicy($policy);
		$this->assertEquals($expected, $this->viewController->index('MyDir', 'MyView'));
	}

	public function testShowFileRouteWithFolder() {
		$node = $this->getMockBuilder(Folder::class)->getMock();
		$node->expects($this->once())
			->method('getPath')
			->will($this->returnValue('/testuser1/files/test/sub'));

		$baseFolder = $this->getMockBuilder(Folder::class)->getMock();

		$this->rootFolder->expects($this->once())
			->method('getUserFolder')
			->with('testuser1')
			->will($this->returnValue($baseFolder));

		$baseFolder->expects($this->at(0))
			->method('getById')
			->with(123)
			->will($this->returnValue([$node]));
		$baseFolder->expects($this->at(1))
			->method('getRelativePath')
			->with('/testuser1/files/test/sub')
			->will($this->returnValue('/test/sub'));

		$this->urlGenerator
			->expects($this->once())
			->method('linkToRoute')
			->with('files.view.index', ['dir' => '/test/sub'])
			->will($this->returnValue('/apps/files/?dir=/test/sub'));

		$expected = new Http\RedirectResponse('/apps/files/?dir=/test/sub');
		$this->assertEquals($expected, $this->viewController->index('/whatever', '', '123'));
	}

	public function testShowFileRouteWithFile() {
		$parentNode = $this->getMockBuilder(Folder::class)->getMock();
		$parentNode->expects($this->once())
			->method('getPath')
			->will($this->returnValue('testuser1/files/test'));

		$baseFolder = $this->getMockBuilder(Folder::class)->getMock();

		$this->rootFolder->expects($this->once())
			->method('getUserFolder')
			->with('testuser1')
			->will($this->returnValue($baseFolder));

		$node = $this->getMockBuilder(File::class)->getMock();
		$node->expects($this->once())
			->method('getParent')
			->will($this->returnValue($parentNode));
		$node->expects($this->once())
			->method('getName')
			->will($this->returnValue('somefile.txt'));

		$baseFolder->expects($this->at(0))
			->method('getById')
			->with(123)
			->will($this->returnValue([$node]));
		$baseFolder->expects($this->at(1))
			->method('getRelativePath')
			->with('testuser1/files/test')
			->will($this->returnValue('/test'));

		$this->urlGenerator
			->expects($this->once())
			->method('linkToRoute')
			->with('files.view.index', ['dir' => '/test', 'scrollto' => 'somefile.txt'])
			->will($this->returnValue('/apps/files/?dir=/test/sub&scrollto=somefile.txt'));

		$expected = new Http\RedirectResponse('/apps/files/?dir=/test/sub&scrollto=somefile.txt');
		$this->assertEquals($expected, $this->viewController->index('/whatever', '', '123'));
	}

	public function testShowFileRouteWithInvalidFileId() {
		$baseFolder = $this->getMockBuilder(Folder::class)->getMock();
		$this->rootFolder->expects($this->once())
			->method('getUserFolder')
			->with('testuser1')
			->will($this->returnValue($baseFolder));

		$baseFolder->expects($this->at(0))
			->method('getById')
			->with(123)
			->will($this->returnValue([]));

		$this->urlGenerator->expects($this->once())
			->method('linkToRoute')
			->with('files.view.index', ['fileNotFound' => true])
			->willReturn('redirect.url');

		$response = $this->viewController->index('MyDir', 'MyView', '123');
		$this->assertInstanceOf('OCP\AppFramework\Http\RedirectResponse', $response);
		$this->assertEquals('redirect.url', $response->getRedirectURL());
	}

	public function testShowFileRouteWithTrashedFile() {
		$this->appManager->expects($this->once())
			->method('isEnabledForUser')
			->with('files_trashbin')
			->will($this->returnValue(true));

		$parentNode = $this->getMockBuilder(Folder::class)->getMock();
		$parentNode->expects($this->once())
			->method('getPath')
			->will($this->returnValue('testuser1/files_trashbin/files/test.d1462861890/sub'));

		$baseFolderFiles = $this->getMockBuilder(Folder::class)->getMock();
		$baseFolderTrash = $this->getMockBuilder(Folder::class)->getMock();

		$this->rootFolder->expects($this->at(0))
			->method('getUserFolder')
			->with('testuser1')
			->will($this->returnValue($baseFolderFiles));
		$this->rootFolder->expects($this->at(1))
			->method('get')
			->with('testuser1/files_trashbin/files/')
			->will($this->returnValue($baseFolderTrash));

		$baseFolderFiles->expects($this->once())
			->method('getById')
			->with(123)
			->will($this->returnValue([]));

		$node = $this->getMockBuilder(File::class)->getMock();
		$node->expects($this->once())
			->method('getParent')
			->will($this->returnValue($parentNode));
		$node->expects($this->once())
			->method('getName')
			->will($this->returnValue('somefile.txt'));

		$baseFolderTrash->expects($this->at(0))
			->method('getById')
			->with(123)
			->will($this->returnValue([$node]));
		$baseFolderTrash->expects($this->at(1))
			->method('getRelativePath')
			->with('testuser1/files_trashbin/files/test.d1462861890/sub')
			->will($this->returnValue('/test.d1462861890/sub'));

		$this->urlGenerator
			->expects($this->once())
			->method('linkToRoute')
			->with('files.view.index', ['view' => 'trashbin', 'dir' => '/test.d1462861890/sub', 'scrollto' => 'somefile.txt'])
			->will($this->returnValue('/apps/files/?view=trashbin&dir=/test.d1462861890/sub&scrollto=somefile.txt'));

		$expected = new Http\RedirectResponse('/apps/files/?view=trashbin&dir=/test.d1462861890/sub&scrollto=somefile.txt');
		$this->assertEquals($expected, $this->viewController->index('/whatever', '', '123'));
	}
}
