<?php
/**
 * @copyright Copyright (c) 2016 Lukas Reschke <lukas@statuscode.ch>
 *
 * @author Bjoern Schiessle <bjoern@schiessle.org>
 * @author oparoz <owncloud@interfasys.ch>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */
namespace OCA\Theming\Tests\Controller;

use OCA\Theming\Controller\ThemingController;
use OCA\Theming\Template;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;
use OCP\Files\IRootFolder;
use OCP\IConfig;
use OCP\IL10N;
use OCP\IRequest;
use Test\TestCase;

class ThemingControllerTest extends TestCase {
	/** @var IRequest */
	private $request;
	/** @var IConfig */
	private $config;
	/** @var Template */
	private $template;
	/** @var IL10N */
	private $l10n;
	/** @var ThemingController */
	private $themingController;
	/** @var IRootFolder */
	private $rootFolder;

	public function setUp() {
		$this->request = $this->getMock('\\OCP\\IRequest');
		$this->config = $this->getMock('\\OCP\\IConfig');
		$this->template = $this->getMockBuilder('\\OCA\\Theming\\Template')
			->disableOriginalConstructor()->getMock();
		$this->l10n = $this->getMock('\\OCP\\IL10N');
		$this->rootFolder = $this->getMock('\\OCP\\Files\\IRootFolder');

		$this->themingController = new ThemingController(
			'theming',
			$this->request,
			$this->config,
			$this->template,
			$this->l10n,
			$this->rootFolder
		);

		return parent::setUp();
	}

	public function testUpdateStylesheet() {
		$this->template
			->expects($this->once())
			->method('set')
			->with('MySetting', 'MyValue');
		$this->l10n
			->expects($this->once())
			->method('t')
			->with('Saved')
			->willReturn('Saved');

		$expected = new DataResponse(
			[
				'data' =>
					[
						'message' => 'Saved',
					],
				'status' => 'success'
			]
		);
		$this->assertEquals($expected, $this->themingController->updateStylesheet('MySetting', 'MyValue'));
	}

	public function testUpdateLogoNoData() {
		$this->request
			->expects($this->at(0))
			->method('getUploadedFile')
			->with('uploadlogo')
			->willReturn(null);
		$this->request
			->expects($this->at(1))
			->method('getUploadedFile')
			->with('upload-login-background')
			->willReturn(null);
		$this->l10n
			->expects($this->once())
			->method('t')
			->with('No file uploaded')
			->willReturn('No file uploaded');

		$expected = new DataResponse(
			[
				'data' =>
					[
						'message' => 'No file uploaded',
					],
			],
			Http::STATUS_UNPROCESSABLE_ENTITY
		);

		$this->assertEquals($expected, $this->themingController->updateLogo());
	}

	public function testUpdateLogoNormalLogoUpload() {
		$tmpLogo = \OC::$server->getTempManager()->getTemporaryFolder() . '/logo.svg';
		$destination = \OC::$server->getTempManager()->getTemporaryFolder();

		touch($tmpLogo);
		$this->request
			->expects($this->at(0))
			->method('getUploadedFile')
			->with('uploadlogo')
			->willReturn([
				'tmp_name' => $tmpLogo,
				'type' => 'text/svg',
				'name' => 'logo.svg',
			]);
		$this->request
			->expects($this->at(1))
			->method('getUploadedFile')
			->with('upload-login-background')
			->willReturn(null);
		$this->l10n
			->expects($this->once())
			->method('t')
			->with('Saved')
			->willReturn('Saved');
		$file = $this->getMockBuilder('\\OCP\\Files\\File')
			->disableOriginalConstructor()
			->getMock();
		$this->rootFolder
			->expects($this->once())
			->method('newFile')
			->with('themedinstancelogo')
			->willReturn($file);
		$file
			->expects($this->once())
			->method('fopen')
			->with('w')
			->willReturn(fopen($destination . '/themedinstancelogo', 'w'));

		$expected = new DataResponse(
			[
				'data' =>
					[
						'name' => 'logo.svg',
						'message' => 'Saved',
					],
				'status' => 'success'
			]
		);

		$this->assertEquals($expected, $this->themingController->updateLogo());
	}

	public function testUpdateLogoLoginScreenUpload() {
		$tmpLogo = \OC::$server->getTempManager()->getTemporaryFolder() . '/logo.svg';
		$destination = \OC::$server->getTempManager()->getTemporaryFolder();

		touch($tmpLogo);
		$this->request
			->expects($this->at(0))
			->method('getUploadedFile')
			->with('uploadlogo')
			->willReturn(null);
		$this->request
			->expects($this->at(1))
			->method('getUploadedFile')
			->with('upload-login-background')
			->willReturn([
				'tmp_name' => $tmpLogo,
				'type' => 'text/svg',
				'name' => 'logo.svg',
			]);
		$this->l10n
			->expects($this->once())
			->method('t')
			->with('Saved')
			->willReturn('Saved');
		$file = $this->getMockBuilder('\\OCP\\Files\\File')
			->disableOriginalConstructor()
			->getMock();
		$this->rootFolder
			->expects($this->once())
			->method('newFile')
			->with('themedbackgroundlogo')
			->willReturn($file);
		$file
			->expects($this->once())
			->method('fopen')
			->with('w')
			->willReturn(fopen($destination . '/themedbackgroundlogo', 'w'));


		$expected = new DataResponse(
			[
				'data' =>
					[
						'name' => 'logo.svg',
						'message' => 'Saved',
					],
				'status' => 'success'
			]
		);
		$this->assertEquals($expected, $this->themingController->updateLogo());
	}

	public function testUndo() {
		$this->l10n
			->expects($this->once())
			->method('t')
			->with('Saved')
			->willReturn('Saved');
		$this->template
			->expects($this->once())
			->method('undo')
			->with('MySetting')
			->willReturn('MyValue');

		$expected = new DataResponse(
			[
				'data' =>
					[
						'value' => 'MyValue',
						'message' => 'Saved',
					],
				'status' => 'success'
			]
		);
		$this->assertEquals($expected, $this->themingController->undo('MySetting'));
	}

	public function testGetLogoNotExistent() {
		$expected = new DataResponse();
		$this->assertEquals($expected, $this->themingController->getLogo());
	}

	public function testGetLogo() {
		$dataFolder = \OC::$server->getTempManager()->getTemporaryFolder();
		$tmpLogo = $dataFolder . '/themedinstancelogo';
		touch($tmpLogo);
		$this->config
			->expects($this->once())
			->method('getSystemValue')
			->with('datadirectory', \OC::$SERVERROOT . '/data/')
			->willReturn($dataFolder);
		$this->config
			->expects($this->once())
			->method('getAppValue')
			->with('theming', 'logoMime', '')
			->willReturn('text/svg');

		@$expected = new Http\StreamResponse($tmpLogo);
		$expected->cacheFor(3600);
		$expected->addHeader('Content-Disposition', 'attachment');
		$expected->addHeader('Content-Type', 'text/svg');
		@$this->assertEquals($expected, $this->themingController->getLogo());
	}


	public function testGetLoginBackgroundNotExistent() {
		$expected = new DataResponse();
		$this->assertEquals($expected, $this->themingController->getLoginBackground());
	}

	public function testGetLoginBackground() {
		$dataFolder = \OC::$server->getTempManager()->getTemporaryFolder();
		$tmpLogo = $dataFolder . '/themedbackgroundlogo';
		touch($tmpLogo);
		$this->config
			->expects($this->once())
			->method('getSystemValue')
			->with('datadirectory', \OC::$SERVERROOT . '/data/')
			->willReturn($dataFolder);
		$this->config
			->expects($this->once())
			->method('getAppValue')
			->with('theming', 'backgroundMime', '')
			->willReturn('image/png');

		@$expected = new Http\StreamResponse($tmpLogo);
		$expected->cacheFor(3600);
		$expected->addHeader('Content-Disposition', 'attachment');
		$expected->addHeader('Content-Type', 'image/png');
		@$this->assertEquals($expected, $this->themingController->getLoginBackground());
	}

	public function testGetStylesheetWithOnlyColor() {
		$this->config
			->expects($this->at(0))
			->method('getAppValue')
			->with('theming', 'cachebuster', '0')
			->willReturn('0');
		$this->config
			->expects($this->at(1))
			->method('getAppValue')
			->with('theming', 'color', '')
			->willReturn('#000');
		$this->config
			->expects($this->at(2))
			->method('getAppValue')
			->with('theming', 'logoMime', '')
			->willReturn('');
		$this->config
			->expects($this->at(3))
			->method('getAppValue')
			->with('theming', 'backgroundMime', '')
			->willReturn('');

		$expected = new Http\DataDownloadResponse('#body-user #header,#body-settings #header,#body-public #header,#body-login,.searchbox input[type="search"]:focus,.searchbox input[type="search"]:active,.searchbox input[type="search"]:valid {background-color: #000}', 'style', 'text/css');
		$expected->cacheFor(3600);
		@$this->assertEquals($expected, $this->themingController->getStylesheet());
	}

	public function testGetStylesheetWithOnlyColorInvert() {
		$this->config
			->expects($this->at(0))
			->method('getAppValue')
			->with('theming', 'cachebuster', '0')
			->willReturn('0');
		$this->config
			->expects($this->at(1))
			->method('getAppValue')
			->with('theming', 'color', '')
			->willReturn('#fff');
		$this->config
			->expects($this->at(2))
			->method('getAppValue')
			->with('theming', 'logoMime', '')
			->willReturn('');
		$this->config
			->expects($this->at(3))
			->method('getAppValue')
			->with('theming', 'backgroundMime', '')
			->willReturn('');

		$expected = new Http\DataDownloadResponse('#body-user #header,#body-settings #header,#body-public #header,#body-login,.searchbox input[type="search"]:focus,.searchbox input[type="search"]:active,.searchbox input[type="search"]:valid {background-color: #fff}', 'style', 'text/css');
		$expected->cacheFor(3600);
		@$this->assertEquals($expected, $this->themingController->getStylesheet());
	}

	public function testGetStylesheetWithOnlyHeaderLogo() {
		$this->config
			->expects($this->at(0))
			->method('getAppValue')
			->with('theming', 'cachebuster', '0')
			->willReturn('0');
		$this->config
			->expects($this->at(1))
			->method('getAppValue')
			->with('theming', 'color', '')
			->willReturn('');
		$this->config
			->expects($this->at(2))
			->method('getAppValue')
			->with('theming', 'logoMime', '')
			->willReturn('image/png');
		$this->config
			->expects($this->at(3))
			->method('getAppValue')
			->with('theming', 'backgroundMime', '')
			->willReturn('');

		$expected = new Http\DataDownloadResponse('#header .logo {
				background-image: url(\'./logo?v=0\');
				background-size: contain;
			}
			#header .logo-icon {
				background-image: url(\'./logo?v=0\');
				background-size: contain;
			}', 'style', 'text/css');
		$expected->cacheFor(3600);
		@$this->assertEquals($expected, $this->themingController->getStylesheet());
	}

	public function testGetStylesheetWithOnlyBackgroundLogin() {
		$this->config
			->expects($this->at(0))
			->method('getAppValue')
			->with('theming', 'cachebuster', '0')
			->willReturn('0');
		$this->config
			->expects($this->at(1))
			->method('getAppValue')
			->with('theming', 'color', '')
			->willReturn('');
		$this->config
			->expects($this->at(2))
			->method('getAppValue')
			->with('theming', 'logoMime', '')
			->willReturn('');
		$this->config
			->expects($this->at(3))
			->method('getAppValue')
			->with('theming', 'backgroundMime', '')
			->willReturn('text/svg');

		$expected = new Http\DataDownloadResponse('#body-login {
				background-image: url(\'./loginbackground?v=0\');
			}', 'style', 'text/css');
		$expected->cacheFor(3600);
		@$this->assertEquals($expected, $this->themingController->getStylesheet());
	}

	public function testGetStylesheetWithAllCombined() {
		$this->config
			->expects($this->at(0))
			->method('getAppValue')
			->with('theming', 'cachebuster', '0')
			->willReturn('0');
		$this->config
			->expects($this->at(1))
			->method('getAppValue')
			->with('theming', 'color', '')
			->willReturn('#abc');
		$this->config
			->expects($this->at(2))
			->method('getAppValue')
			->with('theming', 'logoMime', '')
			->willReturn('text/svg');
		$this->config
			->expects($this->at(3))
			->method('getAppValue')
			->with('theming', 'backgroundMime', '')
			->willReturn('image/png');

		$expected = new Http\DataDownloadResponse('#body-user #header,#body-settings #header,#body-public #header,#body-login,.searchbox input[type="search"]:focus,.searchbox input[type="search"]:active,.searchbox input[type="search"]:valid {background-color: #abc}#header .logo {
				background-image: url(\'./logo?v=0\');
				background-size: contain;
			}
			#header .logo-icon {
				background-image: url(\'./logo?v=0\');
				background-size: contain;
			}#body-login {
				background-image: url(\'./loginbackground?v=0\');
			}', 'style', 'text/css');
		$expected->cacheFor(3600);
		@$this->assertEquals($expected, $this->themingController->getStylesheet());
	}

	public function testGetStylesheetWithAllCombinedInverted() {
		$this->config
			->expects($this->at(0))
			->method('getAppValue')
			->with('theming', 'cachebuster', '0')
			->willReturn('0');
		$this->config
			->expects($this->at(1))
			->method('getAppValue')
			->with('theming', 'color', '')
			->willReturn('#fff');
		$this->config
			->expects($this->at(2))
			->method('getAppValue')
			->with('theming', 'logoMime', '')
			->willReturn('text/svg');
		$this->config
			->expects($this->at(3))
			->method('getAppValue')
			->with('theming', 'backgroundMime', '')
			->willReturn('image/png');

		$expected = new Http\DataDownloadResponse('#body-user #header,#body-settings #header,#body-public #header,#body-login,.searchbox input[type="search"]:focus,.searchbox input[type="search"]:active,.searchbox input[type="search"]:valid {background-color: #fff}#header .logo {
				background-image: url(\'./logo?v=0\');
				background-size: contain;
			}
			#header .logo-icon {
				background-image: url(\'./logo?v=0\');
				background-size: contain;
			}#body-login {
				background-image: url(\'./loginbackground?v=0\');
			}', 'style', 'text/css');
		$expected->cacheFor(3600);
		@$this->assertEquals($expected, $this->themingController->getStylesheet());
	}

}
