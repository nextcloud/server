<?php
/**
 * @author Christoph Wurst <christoph@owncloud.com>
 * @author Joas Schilling <nickvergessen@owncloud.com>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Vincent Petry <pvince81@owncloud.com>
 *
 * @copyright Copyright (c) 2016, ownCloud, Inc.
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
use OCP\IUser;
use OCP\Template;
use Test\TestCase;
use OCP\IRequest;
use OCP\IURLGenerator;
use OCP\AppFramework\Http\RedirectResponse;
use OCP\INavigationManager;
use OCP\IL10N;
use OCP\IConfig;
use OCP\IUserSession;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use OCP\Files\Folder;
use OCP\App\IAppManager;

/**
 * Class ViewControllerTest
 *
 * @package OCA\Files\Tests\Controller
 */
class ViewControllerTest extends TestCase {
	/** @var IRequest */
	private $request;
	/** @var IURLGenerator */
	private $urlGenerator;
	/** @var INavigationManager */
	private $navigationManager;
	/** @var IL10N */
	private $l10n;
	/** @var IConfig */
	private $config;
	/** @var EventDispatcherInterface */
	private $eventDispatcher;
	/** @var ViewController */
	private $viewController;
	/** @var IUser */
	private $user;
	/** @var IUserSession */
	private $userSession;
	/** @var IAppManager */
	private $appManager;
	/** @var Folder */
	private $rootFolder;

	public function setUp() {
		parent::setUp();
		$this->request = $this->getMock('\OCP\IRequest');
		$this->urlGenerator = $this->getMock('\OCP\IURLGenerator');
		$this->navigationManager = $this->getMock('\OCP\INavigationManager');
		$this->l10n = $this->getMock('\OCP\IL10N');
		$this->config = $this->getMock('\OCP\IConfig');
		$this->eventDispatcher = $this->getMock('\Symfony\Component\EventDispatcher\EventDispatcherInterface');
		$this->userSession = $this->getMock('\OCP\IUserSession');
		$this->appManager = $this->getMock('\OCP\App\IAppManager');
		$this->user = $this->getMock('\OCP\IUser');
		$this->user->expects($this->any())
			->method('getUID')
			->will($this->returnValue('testuser1'));
		$this->userSession->expects($this->any())
			->method('getUser')
			->will($this->returnValue($this->user));
		$this->rootFolder = $this->getMock('\OCP\Files\Folder');
		$this->viewController = $this->getMockBuilder('\OCA\Files\Controller\ViewController')
			->setConstructorArgs([
			'files',
			$this->request,
			$this->urlGenerator,
			$this->navigationManager,
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

	public function testIndexWithIE8RedirectAndDirDefined() {
		$this->request
			->expects($this->once())
			->method('isUserAgent')
			->with(['/MSIE 8.0/'])
			->will($this->returnValue(true));
		$this->urlGenerator
			->expects($this->once())
			->method('linkToRoute')
			->with('files.view.index')
			->will($this->returnValue('/apps/files/'));

		$expected = new Http\RedirectResponse('/apps/files/#?dir=MyDir');
		$this->assertEquals($expected, $this->viewController->index('MyDir'));
	}

	public function testIndexWithIE8RedirectAndViewDefined() {
		$this->request
			->expects($this->once())
			->method('isUserAgent')
			->with(['/MSIE 8.0/'])
			->will($this->returnValue(true));
		$this->urlGenerator
			->expects($this->once())
			->method('linkToRoute')
			->with('files.view.index')
			->will($this->returnValue('/apps/files/'));

		$expected = new Http\RedirectResponse('/apps/files/#?dir=/&view=MyView');
		$this->assertEquals($expected, $this->viewController->index('', 'MyView'));
	}

	public function testIndexWithIE8RedirectAndViewAndDirDefined() {
		$this->request
			->expects($this->once())
			->method('isUserAgent')
			->with(['/MSIE 8.0/'])
			->will($this->returnValue(true));
		$this->urlGenerator
			->expects($this->once())
			->method('linkToRoute')
			->with('files.view.index')
			->will($this->returnValue('/apps/files/'));

		$expected = new RedirectResponse('/apps/files/#?dir=MyDir&view=MyView');
		$this->assertEquals($expected, $this->viewController->index('MyDir', 'MyView'));
	}

	public function testIndexWithRegularBrowser() {
		$this->request
			->expects($this->once())
			->method('isUserAgent')
			->with(['/MSIE 8.0/'])
			->will($this->returnValue(false));
		$this->viewController
			->expects($this->once())
			->method('getStorageInfo')
			->will($this->returnValue([
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
		$nav->assign('navigationItems', [
			[
				'id' => 'files',
				'appname' => 'files',
				'script' => 'list.php',
				'order' => 0,
				'name' => new \OC_L10N_String(new \OC_L10N('files'), 'All files', []),
				'active' => false,
				'icon' => '',
			],
			[
				'id' => 'favorites',
				'appname' => 'files',
				'script' => 'simplelist.php',
				'order' => 5,
				'name' => null,
				'active' => false,
				'icon' => '',
			],
			[
			'id' => 'sharingin',
				'appname' => 'files_sharing',
				'script' => 'list.php',
				'order' => 10,
				'name' => new \OC_L10N_String(new \OC_L10N('files_sharing'), 'Shared with you', []),
				'active' => false,
				'icon' => '',
			],
			[
			'id' => 'sharingout',
				'appname' => 'files_sharing',
				'script' => 'list.php',
				'order' => 15,
				'name' => new \OC_L10N_String(new \OC_L10N('files_sharing'), 'Shared with others', []),
				'active' => false,
				'icon' => '',
			],
			[
				'id' => 'sharinglinks',
				'appname' => 'files_sharing',
				'script' => 'list.php',
				'order' => 20,
				'name' => new \OC_L10N_String(new \OC_L10N('files_sharing'), 'Shared by link', []),
				'active' => false,
				'icon' => '',
			],
			[
				'id' => 'systemtagsfilter',
				'appname' => 'systemtags',
				'script' => 'list.php',
				'order' => 25,
				'name' => new \OC_L10N_String(new \OC_L10N('systemtags'), 'Tags', []),
				'active' => false,
				'icon' => '',
			],
			[
				'id' => 'trashbin',
				'appname' => 'files_trashbin',
				'script' => 'list.php',
				'order' => 50,
				'name' => new \OC_L10N_String(new \OC_L10N('files_trashbin'), 'Deleted files', []),
				'active' => false,
				'icon' => '',
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
				'showHiddenFiles' => false,
				'mailNotificationEnabled' => 'no',
				'mailPublicNotificationEnabled' => 'no',
				'allowShareWithLink' => 'yes',
				'appNavigation' => $nav,
				'appContents' => [
					[
						'id' => 'files',
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
			]
		);
		$policy = new Http\ContentSecurityPolicy();
		$policy->addAllowedFrameDomain('\'self\'');
		$expected->setContentSecurityPolicy($policy);
		$this->assertEquals($expected, $this->viewController->index('MyDir', 'MyView'));
	}

	public function showFileMethodProvider() {
		return [
			[true],
			[false],
		];
	}

	/**
	 * @dataProvider showFileMethodProvider
	 */
	public function testShowFileRouteWithFolder($useShowFile) {
		$node = $this->getMock('\OCP\Files\Folder');
		$node->expects($this->once())
			->method('getPath')
			->will($this->returnValue('/testuser1/files/test/sub'));

		$baseFolder = $this->getMock('\OCP\Files\Folder');

		$this->rootFolder->expects($this->once())
			->method('get')
			->with('testuser1/files/')
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
		if ($useShowFile) {
			$this->assertEquals($expected, $this->viewController->showFile(123));
		} else {
			$this->assertEquals($expected, $this->viewController->index('/whatever', '', '123'));
		}
	}

	/**
	 * @dataProvider showFileMethodProvider
	 */
	public function testShowFileRouteWithFile($useShowFile) {
		$parentNode = $this->getMock('\OCP\Files\Folder');
		$parentNode->expects($this->once())
			->method('getPath')
			->will($this->returnValue('testuser1/files/test'));

		$baseFolder = $this->getMock('\OCP\Files\Folder');

		$this->rootFolder->expects($this->once())
			->method('get')
			->with('testuser1/files/')
			->will($this->returnValue($baseFolder));

		$node = $this->getMock('\OCP\Files\File');
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
		if ($useShowFile) {
			$this->assertEquals($expected, $this->viewController->showFile(123));
		} else {
			$this->assertEquals($expected, $this->viewController->index('/whatever', '', '123'));
		}
	}

	/**
	 * @dataProvider showFileMethodProvider
	 */
	public function testShowFileRouteWithInvalidFileId($useShowFile) {
		$baseFolder = $this->getMock('\OCP\Files\Folder');
		$this->rootFolder->expects($this->once())
			->method('get')
			->with('testuser1/files/')
			->will($this->returnValue($baseFolder));

		$baseFolder->expects($this->at(0))
			->method('getById')
			->with(123)
			->will($this->returnValue([]));

		$expected = new Http\NotFoundResponse();
		if ($useShowFile) {
			$this->assertEquals($expected, $this->viewController->showFile(123));
		} else {
			$this->assertEquals($expected, $this->viewController->index('/whatever', '', '123'));
		}
	}

	/**
	 * @dataProvider showFileMethodProvider
	 */
	public function testShowFileRouteWithTrashedFile($useShowFile) {
		$this->appManager->expects($this->once())
			->method('isEnabledForUser')
			->with('files_trashbin')
			->will($this->returnValue(true));

		$parentNode = $this->getMock('\OCP\Files\Folder');
		$parentNode->expects($this->once())
			->method('getPath')
			->will($this->returnValue('testuser1/files_trashbin/files/test.d1462861890/sub'));

		$baseFolderFiles = $this->getMock('\OCP\Files\Folder');
		$baseFolderTrash = $this->getMock('\OCP\Files\Folder');

		$this->rootFolder->expects($this->at(0))
			->method('get')
			->with('testuser1/files/')
			->will($this->returnValue($baseFolderFiles));
		$this->rootFolder->expects($this->at(1))
			->method('get')
			->with('testuser1/files_trashbin/files/')
			->will($this->returnValue($baseFolderTrash));

		$baseFolderFiles->expects($this->once())
			->method('getById')
			->with(123)
			->will($this->returnValue([]));

		$node = $this->getMock('\OCP\Files\File');
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
		if ($useShowFile) {
			$this->assertEquals($expected, $this->viewController->showFile(123));
		} else {
			$this->assertEquals($expected, $this->viewController->index('/whatever', '', '123'));
		}
	}
}
