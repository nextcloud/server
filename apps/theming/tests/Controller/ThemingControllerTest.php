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

use OC\Files\AppData\Factory;
use OC\L10N\L10N;
use OC\Template\SCSSCacher;
use OCA\Theming\Controller\ThemingController;
use OCA\Theming\Util;
use OCP\App\IAppManager;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\Files\IAppData;
use OCP\Files\IRootFolder;
use OCP\Files\NotFoundException;
use OCP\Files\SimpleFS\ISimpleFile;
use OCP\Files\SimpleFS\ISimpleFolder;
use OCP\IConfig;
use OCP\IL10N;
use OCP\ILogger;
use OCP\IRequest;
use OCP\ITempManager;
use OCP\IURLGenerator;
use Test\TestCase;
use OCA\Theming\ThemingDefaults;

class ThemingControllerTest extends TestCase {
	/** @var IRequest|\PHPUnit_Framework_MockObject_MockObject */
	private $request;
	/** @var IConfig|\PHPUnit_Framework_MockObject_MockObject */
	private $config;
	/** @var ThemingDefaults|\PHPUnit_Framework_MockObject_MockObject */
	private $themingDefaults;
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
	/** @var IAppManager|\PHPUnit_Framework_MockObject_MockObject */
	private $appManager;
	/** @var IAppData|\PHPUnit_Framework_MockObject_MockObject */
	private $appData;
	/** @var SCSSCacher */
	private $scssCacher;

	public function setUp() {
		$this->request = $this->createMock(IRequest::class);
		$this->config = $this->createMock(IConfig::class);
		$this->themingDefaults = $this->createMock(ThemingDefaults::class);
		$this->timeFactory = $this->createMock(ITimeFactory::class);
		$this->l10n = $this->createMock(L10N::class);
		$this->rootFolder = $this->createMock(IRootFolder::class);
		$this->appManager = $this->createMock(IAppManager::class);
		$this->util = new Util($this->config, $this->rootFolder, $this->appManager);
		$this->timeFactory->expects($this->any())
			->method('getTime')
			->willReturn(123);
		$this->tempManager = \OC::$server->getTempManager();
		$this->appData = $this->createMock(IAppData::class);
		$this->scssCacher = $this->createMock(SCSSCacher::class);

		$this->themingController = new ThemingController(
			'theming',
			$this->request,
			$this->config,
			$this->themingDefaults,
			$this->util,
			$this->timeFactory,
			$this->l10n,
			$this->tempManager,
			$this->appData,
			$this->scssCacher
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
		$this->themingDefaults
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

	public function dataUpdateImages() {
		return [
			[false],
			[true]
		];
	}

	/** @dataProvider dataUpdateImages */
	public function testUpdateLogoNormalLogoUpload($folderExists) {
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


		$file = $this->createMock(ISimpleFile::class);
		$folder = $this->createMock(ISimpleFolder::class);
		if($folderExists) {
			$this->appData
				->expects($this->once())
				->method('getFolder')
				->with('images')
				->willReturn($folder);
		} else {
			$this->appData
				->expects($this->at(0))
				->method('getFolder')
				->with('images')
				->willThrowException(new NotFoundException());
			$this->appData
				->expects($this->at(1))
				->method('newFolder')
				->with('images')
				->willReturn($folder);
		}
		$folder->expects($this->once())
			->method('newFile')
			->with('logo')
			->willReturn($file);
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

	/** @dataProvider dataUpdateImages */
	public function testUpdateLogoLoginScreenUpload($folderExists) {
		$tmpLogo = \OC::$server->getTempManager()->getTemporaryFolder() . '/logo.svg';

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

		$file = $this->createMock(ISimpleFile::class);
		$folder = $this->createMock(ISimpleFolder::class);
		if($folderExists) {
			$this->appData
				->expects($this->once())
				->method('getFolder')
				->with('images')
				->willReturn($folder);
		} else {
			$this->appData
				->expects($this->at(0))
				->method('getFolder')
				->with('images')
				->willThrowException(new NotFoundException());
			$this->appData
				->expects($this->at(1))
				->method('newFolder')
				->with('images')
				->willReturn($folder);
		}
		$folder->expects($this->once())
			->method('newFile')
			->with('background')
			->willReturn($file);

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

		$folder = $this->createMock(ISimpleFolder::class);
		$this->appData
			->expects($this->once())
			->method('getFolder')
			->with('images')
			->willReturn($folder);

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
		$this->themingDefaults
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
		$this->appData->method('getFolder')
			->with($this->equalTo('images'))
			->willThrowException(new NotFoundException());

		$expected = new Http\NotFoundResponse();
		$this->assertEquals($expected, $this->themingController->getLogo());
	}

	public function testGetLogo() {
		$file = $this->createMock(ISimpleFile::class);
		$folder = $this->createMock(ISimpleFolder::class);
		$this->appData
			->expects($this->once())
			->method('getFolder')
			->with('images')
			->willReturn($folder);
		$folder->expects($this->once())
			->method('getFile')
			->with('logo')
			->willReturn($file);

		$this->config
			->expects($this->once())
			->method('getAppValue')
			->with('theming', 'logoMime', '')
			->willReturn('text/svg');

		@$expected = new Http\FileDisplayResponse($file);
		$expected->cacheFor(3600);
		$expires = new \DateTime();
		$expires->setTimestamp($this->timeFactory->getTime());
		$expires->add(new \DateInterval('PT24H'));
		$expected->addHeader('Expires', $expires->format(\DateTime::RFC2822));
		$expected->addHeader('Pragma', 'cache');
		$expected->addHeader('Content-Type', 'text/svg');
		@$this->assertEquals($expected, $this->themingController->getLogo());
	}


	public function testGetLoginBackgroundNotExistent() {
		$this->appData->method('getFolder')
			->with($this->equalTo('images'))
			->willThrowException(new NotFoundException());
		$expected = new Http\NotFoundResponse();
		$this->assertEquals($expected, $this->themingController->getLoginBackground());
	}

	public function testGetLoginBackground() {
		$file = $this->createMock(ISimpleFile::class);
		$folder = $this->createMock(ISimpleFolder::class);
		$this->appData
			->expects($this->once())
			->method('getFolder')
			->with('images')
			->willReturn($folder);
		$folder->expects($this->once())
			->method('getFile')
			->with('background')
			->willReturn($file);

		$this->config
			->expects($this->once())
			->method('getAppValue')
			->with('theming', 'backgroundMime', '')
			->willReturn('image/png');

		@$expected = new Http\FileDisplayResponse($file);
		$expected->cacheFor(3600);
		$expires = new \DateTime();
		$expires->setTimestamp($this->timeFactory->getTime());
		$expires->add(new \DateInterval('PT24H'));
		$expected->addHeader('Expires', $expires->format(\DateTime::RFC2822));
		$expected->addHeader('Pragma', 'cache');
		$expected->addHeader('Content-Type', 'image/png');
		@$this->assertEquals($expected, $this->themingController->getLoginBackground());
	}


	public function testGetStylesheet() {

		$file = $this->createMock(ISimpleFile::class);
		$file->expects($this->any())->method('getName')->willReturn('theming.css');
		$file->expects($this->any())->method('getContent')->willReturn('compiled');
		$this->scssCacher->expects($this->once())->method('process')->willReturn(true);
		$this->scssCacher->expects($this->once())->method('getCachedCSS')->willReturn($file);

		$response = new Http\FileDisplayResponse($file, Http::STATUS_OK, ['Content-Type' => 'text/css']);
		$response->cacheFor(86400);
		$expires = new \DateTime();
		$expires->setTimestamp($this->timeFactory->getTime());
		$expires->add(new \DateInterval('PT24H'));
		$response->addHeader('Expires', $expires->format(\DateTime::RFC1123));
		$response->addHeader('Pragma', 'cache');

		$actual = $this->themingController->getStylesheet();
		$this->assertEquals($response, $actual);
	}

	public function testGetStylesheetFails() {
		$file = $this->createMock(ISimpleFile::class);
		$file->expects($this->any())->method('getName')->willReturn('theming.css');
		$file->expects($this->any())->method('getContent')->willReturn('compiled');
		$this->scssCacher->expects($this->once())->method('process')->willReturn(true);
		$this->scssCacher->expects($this->once())->method('getCachedCSS')->willThrowException(new NotFoundException());
		$response = new Http\NotFoundResponse();

		$actual = $this->themingController->getStylesheet();
		$this->assertEquals($response, $actual);
	}

	public function testGetJavascript() {
		$this->themingDefaults
			->expects($this->at(0))
			->method('getName')
			->willReturn("");
		$this->themingDefaults
			->expects($this->at(1))
			->method('getBaseUrl')
			->willReturn("");
		$this->themingDefaults
			->expects($this->at(2))
			->method('getSlogan')
			->willReturn("");
		$this->themingDefaults
			->expects($this->at(3))
			->method('getColorPrimary')
			->willReturn("#000");


		$expectedResponse = '(function() {
	OCA.Theming = {
		name: "",
		url: "",
		slogan: "",
		color: "#000",
		inverted: false,
		cacheBuster: null
	};
})();';
		$expected = new Http\DataDownloadResponse($expectedResponse, 'javascript', 'text/javascript');
		$expected->addHeader('Expires', date(\DateTime::RFC2822, $this->timeFactory->getTime()));
		$expected->addHeader('Pragma', 'cache');
		$expected->cacheFor(3600);
		@$this->assertEquals($expected, $this->themingController->getJavascript());
	}
	public function testGetJavascriptInverted() {
		$this->themingDefaults
			->expects($this->at(0))
			->method('getName')
			->willReturn("Nextcloud");
		$this->themingDefaults
			->expects($this->at(1))
			->method('getBaseUrl')
			->willReturn("nextcloudurl");
		$this->themingDefaults
			->expects($this->at(2))
			->method('getSlogan')
			->willReturn("awesome");
		$this->themingDefaults
			->expects($this->any())
			->method('getColorPrimary')
			->willReturn("#ffffff");

		$expectedResponse = '(function() {
	OCA.Theming = {
		name: "Nextcloud",
		url: "nextcloudurl",
		slogan: "awesome",
		color: "#ffffff",
		inverted: true,
		cacheBuster: null
	};
})();';
		$expected = new Http\DataDownloadResponse($expectedResponse, 'javascript', 'text/javascript');
		$expected->addHeader('Expires', date(\DateTime::RFC2822, $this->timeFactory->getTime()));
		$expected->addHeader('Pragma', 'cache');
		$expected->cacheFor(3600);
		@$this->assertEquals($expected, $this->themingController->getJavascript());
	}
}
