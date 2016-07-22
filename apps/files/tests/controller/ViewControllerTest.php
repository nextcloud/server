<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Joas Schilling <coding@schilljs.com>
 * @author Lukas Reschke <lukas@statuscode.ch>
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
use OCP\Template;
use Test\TestCase;
use OCP\IRequest;
use OCP\IURLGenerator;
use OCP\AppFramework\Http\RedirectResponse;
use OCP\INavigationManager;
use OCP\IL10N;
use OCP\IConfig;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

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

	public function setUp() {
		parent::setUp();
		$this->request = $this->getMock('\OCP\IRequest');
		$this->urlGenerator = $this->getMock('\OCP\IURLGenerator');
		$this->navigationManager = $this->getMock('\OCP\INavigationManager');
		$this->l10n = $this->getMock('\OCP\IL10N');
		$this->config = $this->getMock('\OCP\IConfig');
		$this->eventDispatcher = $this->getMock('\Symfony\Component\EventDispatcher\EventDispatcherInterface');
		$this->viewController = $this->getMockBuilder('\OCA\Files\Controller\ViewController')
			->setConstructorArgs([
			'files',
			$this->request,
			$this->urlGenerator,
			$this->navigationManager,
			$this->l10n,
			$this->config,
			$this->eventDispatcher
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
}
