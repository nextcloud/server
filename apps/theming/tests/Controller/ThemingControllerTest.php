<?php
/**
 * @copyright Copyright (c) 2016 Lukas Reschke <lukas@statuscode.ch>
 *
 * @author Julius Haertl <jus@bitgrid.net>
 * @author Lukas Reschke <lukas@statuscode.ch>
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
use OCA\Theming\Util;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;
use OCP\Files\IRootFolder;
use OCP\IConfig;
use OCP\IL10N;
use OCP\IRequest;
use OCP\ITempManager;
use Test\TestCase;
use OCA\Theming\ThemingDefaults;

class ThemingControllerTest extends TestCase {
	/** @var IRequest|\PHPUnit_Framework_MockObject_MockObject */
	private $request;
	/** @var IConfig|\PHPUnit_Framework_MockObject_MockObject */
	private $config;
	/** @var ThemingDefaults|\PHPUnit_Framework_MockObject_MockObject */
	private $template;
	/** @var Util */
	private $util;
	/** @var \OCP\AppFramework\Utility\ITimeFactory */
	private $timeFactory;
	/** @var IL10N|\PHPUnit_Framework_MockObject_MockObject */
	private $l10n;
	/** @var ThemingController */
	private $themingController;
	/** @var IRootFolder|\PHPUnit_Framework_MockObject_MockObject */
	private $rootFolder;
	/** @var ITempManager */
	private $tempManager;

	public function setUp() {
		$this->request = $this->getMockBuilder('OCP\IRequest')->getMock();
		$this->config = $this->getMockBuilder('OCP\IConfig')->getMock();
		$this->template = $this->getMockBuilder('OCA\Theming\ThemingDefaults')
			->disableOriginalConstructor()->getMock();
		$this->util = new Util();
		$this->timeFactory = $this->getMockBuilder('OCP\AppFramework\Utility\ITimeFactory')
			->disableOriginalConstructor()
			->getMock();
		$this->l10n = $this->getMockBuilder('OCP\IL10N')->getMock();
		$this->rootFolder = $this->getMockBuilder('OCP\Files\IRootFolder')->getMock();
		$this->timeFactory->expects($this->any())
			->method('getTime')
			->willReturn(123);
		$this->tempManager = \OC::$server->getTempManager();

		$this->themingController = new ThemingController(
			'theming',
			$this->request,
			$this->config,
			$this->template,
			$this->util,
			$this->timeFactory,
			$this->l10n,
			$this->rootFolder,
			$this->tempManager
		);

		return parent::setUp();
	}

	public function dataUpdateStylesheet() {
		return [
			['name', str_repeat('a', 250), 'success', 'Saved'],
			['name', str_repeat('a', 251), 'error', 'The given name is too long'],
			['url', str_repeat('a', 500), 'success', 'Saved'],
			['url', str_repeat('a', 501), 'error', 'The given web address is too long'],
			['slogan', str_repeat('a', 500), 'success', 'Saved'],
			['slogan', str_repeat('a', 501), 'error', 'The given slogan is too long'],
			['color', '#0082c9', 'success', 'Saved'],
			['color', '#0082C9', 'success', 'Saved'],
			['color', '0082C9', 'error', 'The given color is invalid'],
			['color', '#0082Z9', 'error', 'The given color is invalid'],
			['color', 'Nextcloud', 'error', 'The given color is invalid'],
		];
	}

	/**
	 * @dataProvider dataUpdateStylesheet
	 *
	 * @param string $setting
	 * @param string $value
	 * @param string $status
	 * @param string $message
	 */
	public function testUpdateStylesheet($setting, $value, $status, $message) {
		$this->template
			->expects($status === 'success' ? $this->once() : $this->never())
			->method('set')
			->with($setting, $value);
		$this->l10n
			->expects($this->once())
			->method('t')
			->with($message)
			->willReturn($message);

		$expected = new DataResponse([
			'data' => [
				'message' => $message,
			],
			'status' => $status,
		]);
		$this->assertEquals($expected, $this->themingController->updateStylesheet($setting, $value));
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
		file_put_contents($tmpLogo, file_get_contents(__DIR__  . '/../../../../tests/data/desktopapp.png'));
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

	public function testUpdateLogoLoginScreenUploadWithInvalidImage() {
		$tmpLogo = \OC::$server->getTempManager()->getTemporaryFolder() . '/logo.svg';
		$destination = \OC::$server->getTempManager()->getTemporaryFolder();

		touch($tmpLogo);
		file_put_contents($tmpLogo, file_get_contents(__DIR__  . '/../../../../tests/data/data.zip'));
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
			->with('Unsupported image type')
			->willReturn('Unsupported image type');
		$file = $this->getMockBuilder('\\OCP\\Files\\File')
			->disableOriginalConstructor()
			->getMock();
		$this->rootFolder
			->expects($this->once())
			->method('newFile')
			->with('themedbackgroundlogo')
			->willReturn($file);
		$expected = new DataResponse(
			[
				'data' =>
					[
						'message' => 'Unsupported image type',
					],
				'status' => 'failure'
			],
			Http::STATUS_UNPROCESSABLE_ENTITY
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
		$expected->addHeader('Expires', date(\DateTime::RFC2822, 123));
		$expected->addHeader('Content-Disposition', 'attachment');
		$expected->addHeader('Content-Type', 'text/svg');
		$expected->addHeader('Pragma', 'cache');
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
		$expected->addHeader('Expires', date(\DateTime::RFC2822, 123));
		$expected->addHeader('Content-Disposition', 'attachment');
		$expected->addHeader('Content-Type', 'image/png');
		$expected->addHeader('Pragma', 'cache');
		@$this->assertEquals($expected, $this->themingController->getLoginBackground());
	}

	public function testGetStylesheetWithOnlyColor() {

		$color = '#000';

		$this->config
			->expects($this->at(0))
			->method('getAppValue')
			->with('theming', 'cachebuster', '0')
			->willReturn('0');
		$this->config
			->expects($this->at(1))
			->method('getAppValue')
			->with('theming', 'color', '')
			->willReturn($color);
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

		$expectedData = sprintf(
			'#body-user #header,#body-settings #header,#body-public #header,#body-login,.searchbox input[type="search"]:focus,.searchbox input[type="search"]:active,.searchbox input[type="search"]:valid {background-color: %s}' . "\n",
			$color
		);
		$expectedData .= sprintf('input[type="checkbox"].checkbox:checked:enabled:not(.checkbox--white) + label:before {' .
			'background-image:url(\'%s/core/img/actions/checkmark-white.svg\');' .
			'background-color: %s; background-position: center center; background-size:contain;' .
			'width:12px; height:12px; padding:0; margin:2px 6px 6px 2px; border-radius:1px;' .
			"}\n",
			\OC::$WEBROOT,
			$color
		);
		$expectedData .= 'input[type="radio"].radio:checked:not(.radio--white):not(:disabled) + label:before {' .
			'background-image: url(\'data:image/svg+xml;base64,'.$this->util->generateRadioButton($color).'\');' .
			"}\n";

		$expectedData .= '
				#firstrunwizard .firstrunwizard-header {
					background-color: ' . $color . ';
				}
				#firstrunwizard p a {
					color: ' . $color . ';
				}
				';
		$expectedData .= sprintf('.nc-theming-main-background {background-color: %s}' . "\n", $color);
		$expectedData .= sprintf('.nc-theming-main-text {color: %s}' . "\n", $color);
		$expectedData .= '.nc-theming-contrast {color: #ffffff}' . "\n";

		$expected = new Http\DataDownloadResponse($expectedData, 'style', 'text/css');

		$expected->cacheFor(3600);
		$expected->addHeader('Expires', date(\DateTime::RFC2822, 123));
		$expected->addHeader('Pragma', 'cache');
		@$this->assertEquals($expected, $this->themingController->getStylesheet());
	}

	public function testGetStylesheetWithOnlyColorInvert() {

		$color = '#fff';

		$this->config
			->expects($this->at(0))
			->method('getAppValue')
			->with('theming', 'cachebuster', '0')
			->willReturn('0');
		$this->config
			->expects($this->at(1))
			->method('getAppValue')
			->with('theming', 'color', '')
			->willReturn($color);
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

		$expectedData = sprintf(
			'#body-user #header,#body-settings #header,#body-public #header,#body-login,.searchbox input[type="search"]:focus,.searchbox input[type="search"]:active,.searchbox input[type="search"]:valid {background-color: %s}' . "\n",
			$color
		);
		$expectedData .= sprintf('input[type="checkbox"].checkbox:checked:enabled:not(.checkbox--white) + label:before {' .
			'background-image:url(\'%s/core/img/actions/checkmark-white.svg\');' .
			'background-color: #555555; background-position: center center; background-size:contain;' .
			'width:12px; height:12px; padding:0; margin:2px 6px 6px 2px; border-radius:1px;' .
			"}\n",
			\OC::$WEBROOT
		);
		$expectedData .= 'input[type="radio"].radio:checked:not(.radio--white):not(:disabled) + label:before {' .
			'background-image: url(\'data:image/svg+xml;base64,'.$this->util->generateRadioButton('#555555').'\');' .
			"}\n";

		$expectedData .= '
				#firstrunwizard .firstrunwizard-header {
					background-color: ' . $color . ';
				}
				#firstrunwizard p a {
					color: ' . $color . ';
				}
				';
		$expectedData .= sprintf('.nc-theming-main-background {background-color: %s}' . "\n", $color);
		$expectedData .= sprintf('.nc-theming-main-text {color: %s}' . "\n", $color);
		$expectedData .= '#header .header-appname, #expandDisplayName { color: #000000; }' . "\n";
		$expectedData .= '#header .icon-caret { background-image: url(\'' . \OC::$WEBROOT . '/core/img/actions/caret-dark.svg\'); }' . "\n";
		$expectedData .= '.searchbox input[type="search"] { background: transparent url(\'' . \OC::$WEBROOT . '/core/img/actions/search.svg\') no-repeat 6px center; color: #000; }' . "\n";
		$expectedData .= '.searchbox input[type="search"]:focus,.searchbox input[type="search"]:active,.searchbox input[type="search"]:valid { color: #000; border: 1px solid rgba(0, 0, 0, .5); }' . "\n";
		$expectedData .= '.nc-theming-contrast {color: #000000}' . "\n";


		$expected = new Http\DataDownloadResponse($expectedData, 'style', 'text/css');

		$expected->cacheFor(3600);
		$expected->addHeader('Expires', date(\DateTime::RFC2822, 123));
		$expected->addHeader('Pragma', 'cache');
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

		$expectedData = '#header .logo {' .
			'background-image: url(\'./logo?v=0\');' .
			'background-size: contain;' .
			'}' . "\n" .
			'#header .logo-icon {' .
			'background-image: url(\'./logo?v=0\');' .
			'background-size: contain;' .
			'}' . "\n" .
			'#firstrunwizard .firstrunwizard-header .logo {' .
			'background-image: url(\'./logo?v=0\');' .
			'background-size: contain;' .
			'}' . "\n";
		$expectedData .= '.nc-theming-contrast {color: #ffffff}' . "\n";

		$expected = new Http\DataDownloadResponse($expectedData, 'style', 'text/css');

		$expected->cacheFor(3600);
		$expected->addHeader('Expires', date(\DateTime::RFC2822, 123));
		$expected->addHeader('Pragma', 'cache');
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

		$expectedData = '#body-login {background-image: url(\'./loginbackground?v=0\');}' . "\n";
		$expectedData .= '#firstrunwizard .firstrunwizard-header {' .
			'background-image: url(\'./loginbackground?v=0\');' .
			'}' . "\n";
		$expectedData .= '.nc-theming-contrast {color: #ffffff}' . "\n";

		$expected = new Http\DataDownloadResponse($expectedData, 'style', 'text/css');

		$expected->cacheFor(3600);
		$expected->addHeader('Expires', date(\DateTime::RFC2822, 123));
		$expected->addHeader('Pragma', 'cache');
		@$this->assertEquals($expected, $this->themingController->getStylesheet());
	}

	public function testGetStylesheetWithAllCombined() {

		$color = '#000';

		$this->config
			->expects($this->at(0))
			->method('getAppValue')
			->with('theming', 'cachebuster', '0')
			->willReturn('0');
		$this->config
			->expects($this->at(1))
			->method('getAppValue')
			->with('theming', 'color', '')
			->willReturn($color);
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

		$expectedData = sprintf(
			'#body-user #header,#body-settings #header,#body-public #header,#body-login,.searchbox input[type="search"]:focus,.searchbox input[type="search"]:active,.searchbox input[type="search"]:valid {background-color: %s}' . "\n",
			$color);

		$expectedData .= sprintf('input[type="checkbox"].checkbox:checked:enabled:not(.checkbox--white) + label:before {' .
			'background-image:url(\'%s/core/img/actions/checkmark-white.svg\');' .
			'background-color: %s; background-position: center center; background-size:contain;' .
			'width:12px; height:12px; padding:0; margin:2px 6px 6px 2px; border-radius:1px;' .
			"}\n",
			\OC::$WEBROOT,
			$color
		);
		$expectedData .= 'input[type="radio"].radio:checked:not(.radio--white):not(:disabled) + label:before {' .
			'background-image: url(\'data:image/svg+xml;base64,'.$this->util->generateRadioButton($color).'\');' .
			"}\n";
		$expectedData .= '
				#firstrunwizard .firstrunwizard-header {
					background-color: ' . $color . ';
				}
				#firstrunwizard p a {
					color: ' . $color . ';
				}
				';
		$expectedData .= sprintf('.nc-theming-main-background {background-color: %s}' . "\n", $color);
		$expectedData .= sprintf('.nc-theming-main-text {color: %s}' . "\n", $color);
		$expectedData .= sprintf(
			'#header .logo {' .
			'background-image: url(\'./logo?v=0\');' .
			'background-size: contain;' .
			'}' . "\n" .
			'#header .logo-icon {' .
			'background-image: url(\'./logo?v=0\');' .
			'background-size: contain;' .
			'}' . "\n" .
			'#firstrunwizard .firstrunwizard-header .logo {' .
			'background-image: url(\'./logo?v=0\');' .
			'background-size: contain;' .
			'}' . "\n"
		);
		$expectedData .= '#body-login {background-image: url(\'./loginbackground?v=0\');}' . "\n";
		$expectedData .= '#firstrunwizard .firstrunwizard-header {' .
			'background-image: url(\'./loginbackground?v=0\');' .
			'}' . "\n";
		$expectedData .= '.nc-theming-contrast {color: #ffffff}' . "\n";
		$expected = new Http\DataDownloadResponse($expectedData, 'style', 'text/css');

		$expected->cacheFor(3600);
		$expected->addHeader('Expires', date(\DateTime::RFC2822, 123));
		$expected->addHeader('Pragma', 'cache');
		@$this->assertEquals($expected, $this->themingController->getStylesheet());
	}

	public function testGetStylesheetWithAllCombinedInverted() {

		$color = '#fff';

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


		$expectedData = sprintf(
			'#body-user #header,#body-settings #header,#body-public #header,#body-login,.searchbox input[type="search"]:focus,.searchbox input[type="search"]:active,.searchbox input[type="search"]:valid {background-color: %s}' . "\n",
			$color);

		$expectedData .= sprintf('input[type="checkbox"].checkbox:checked:enabled:not(.checkbox--white) + label:before {' .
			'background-image:url(\'%s/core/img/actions/checkmark-white.svg\');' .
			'background-color: #555555; background-position: center center; background-size:contain;' .
			'width:12px; height:12px; padding:0; margin:2px 6px 6px 2px; border-radius:1px;' .
			"}\n",
			\OC::$WEBROOT
		);
		$expectedData .= 'input[type="radio"].radio:checked:not(.radio--white):not(:disabled) + label:before {' .
			'background-image: url(\'data:image/svg+xml;base64,'.$this->util->generateRadioButton('#555555').'\');' .
			"}\n";
		$expectedData .= '
				#firstrunwizard .firstrunwizard-header {
					background-color: ' . $color . ';
				}
				#firstrunwizard p a {
					color: ' . $color . ';
				}
				';
		$expectedData .= sprintf('.nc-theming-main-background {background-color: %s}' . "\n", $color);
		$expectedData .= sprintf('.nc-theming-main-text {color: %s}' . "\n", $color);
		$expectedData .= sprintf(
			'#header .logo {' .
			'background-image: url(\'./logo?v=0\');' .
			'background-size: contain;' .
			'}' . "\n" .
			'#header .logo-icon {' .
			'background-image: url(\'./logo?v=0\');' .
			'background-size: contain;' .
			'}' . "\n" .
			'#firstrunwizard .firstrunwizard-header .logo {' .
			'background-image: url(\'./logo?v=0\');' .
			'background-size: contain;' .
			'}' . "\n"
		);
		$expectedData .= '#body-login {background-image: url(\'./loginbackground?v=0\');}' . "\n";
		$expectedData .= '#firstrunwizard .firstrunwizard-header {' .
			'background-image: url(\'./loginbackground?v=0\');' .
			'}' . "\n";
		$expectedData .= '#header .header-appname, #expandDisplayName { color: #000000; }' . "\n";
		$expectedData .= '#header .icon-caret { background-image: url(\'' . \OC::$WEBROOT . '/core/img/actions/caret-dark.svg\'); }' . "\n";
		$expectedData .= '.searchbox input[type="search"] { background: transparent url(\'' . \OC::$WEBROOT . '/core/img/actions/search.svg\') no-repeat 6px center; color: #000; }' . "\n";
		$expectedData .= '.searchbox input[type="search"]:focus,.searchbox input[type="search"]:active,.searchbox input[type="search"]:valid { color: #000; border: 1px solid rgba(0, 0, 0, .5); }' . "\n";
		$expectedData .= '.nc-theming-contrast {color: #000000}' . "\n";
		$expected = new Http\DataDownloadResponse($expectedData, 'style', 'text/css');

		$expected->cacheFor(3600);
		$expected->addHeader('Expires', date(\DateTime::RFC2822, 123));
		$expected->addHeader('Pragma', 'cache');
		@$this->assertEquals($expected, $this->themingController->getStylesheet());
	}

	public function testGetJavascript() {
		$this->template
			->expects($this->at(0))
			->method('getName')
			->willReturn("");
		$this->template
			->expects($this->at(1))
			->method('getBaseUrl')
			->willReturn("");
		$this->template
			->expects($this->at(2))
			->method('getSlogan')
			->willReturn("");
		$this->template
			->expects($this->at(3))
			->method('getMailHeaderColor')
			->willReturn("#000");


		$expectedResponse = '(function() {
	OCA.Theming = {
		name: "",
		url: "",
		slogan: "",
		color: "#000",
		inverted: false,
	};
})();';
		$expected = new Http\DataDisplayResponse($expectedResponse);
		$expected->addHeader("Content-type","text/javascript");
		$expected->addHeader('Expires', date(\DateTime::RFC2822, $this->timeFactory->getTime()));
		$expected->addHeader('Pragma', 'cache');
		$expected->cacheFor(3600);
		@$this->assertEquals($expected, $this->themingController->getJavascript());
	}
	public function testGetJavascriptInverted() {
		$this->template
			->expects($this->at(0))
			->method('getName')
			->willReturn("Nextcloud");
		$this->template
			->expects($this->at(1))
			->method('getBaseUrl')
			->willReturn("nextcloudurl");
		$this->template
			->expects($this->at(2))
			->method('getSlogan')
			->willReturn("awesome");
		$this->template
			->expects($this->any())
			->method('getMailHeaderColor')
			->willReturn("#ffffff");

		$expectedResponse = '(function() {
	OCA.Theming = {
		name: "Nextcloud",
		url: "nextcloudurl",
		slogan: "awesome",
		color: "#ffffff",
		inverted: true,
	};
})();';
		$expected = new Http\DataDisplayResponse($expectedResponse);
		$expected->addHeader("Content-type","text/javascript");
		$expected->addHeader('Expires', date(\DateTime::RFC2822, $this->timeFactory->getTime()));
		$expected->addHeader('Pragma', 'cache');
		$expected->cacheFor(3600);
		@$this->assertEquals($expected, $this->themingController->getJavascript());
	}
}
