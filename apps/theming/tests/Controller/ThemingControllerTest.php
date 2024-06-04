<?php
/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Theming\Tests\Controller;

use OC\L10N\L10N;
use OCA\Theming\Controller\ThemingController;
use OCA\Theming\ImageManager;
use OCA\Theming\Service\ThemesService;
use OCA\Theming\ThemingDefaults;
use OCP\App\IAppManager;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\Files\NotFoundException;
use OCP\Files\SimpleFS\ISimpleFile;
use OCP\IConfig;
use OCP\IL10N;
use OCP\IRequest;
use OCP\IURLGenerator;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class ThemingControllerTest extends TestCase {
	/** @var IRequest|MockObject */
	private $request;
	/** @var IConfig|MockObject */
	private $config;
	/** @var ThemingDefaults|MockObject */
	private $themingDefaults;
	/** @var IL10N|MockObject */
	private $l10n;
	/** @var ThemingController */
	private $themingController;
	/** @var IAppManager|MockObject */
	private $appManager;
	/** @var ImageManager|MockObject */
	private $imageManager;
	/** @var IURLGenerator|MockObject */
	private $urlGenerator;
	/** @var ThemesService|MockObject */
	private $themesService;

	protected function setUp(): void {
		$this->request = $this->createMock(IRequest::class);
		$this->config = $this->createMock(IConfig::class);
		$this->themingDefaults = $this->createMock(ThemingDefaults::class);
		$this->l10n = $this->createMock(L10N::class);
		$this->appManager = $this->createMock(IAppManager::class);
		$this->urlGenerator = $this->createMock(IURLGenerator::class);
		$this->imageManager = $this->createMock(ImageManager::class);
		$this->themesService = $this->createMock(ThemesService::class);

		$timeFactory = $this->createMock(ITimeFactory::class);
		$timeFactory->expects($this->any())
			->method('getTime')
			->willReturn(123);

		$this->overwriteService(ITimeFactory::class, $timeFactory);

		$this->themingController = new ThemingController(
			'theming',
			$this->request,
			$this->config,
			$this->themingDefaults,
			$this->l10n,
			$this->urlGenerator,
			$this->appManager,
			$this->imageManager,
			$this->themesService,
		);

		parent::setUp();
	}

	public function dataUpdateStylesheetSuccess() {
		return [
			['name', str_repeat('a', 250), 'Saved'],
			['url', 'https://nextcloud.com/' . str_repeat('a', 478), 'Saved'],
			['slogan', str_repeat('a', 500), 'Saved'],
			['color', '#0082c9', 'Saved'],
			['color', '#0082C9', 'Saved'],
			['color', '#0082C9', 'Saved'],
			['imprintUrl', 'https://nextcloud.com/' . str_repeat('a', 478), 'Saved'],
			['privacyUrl', 'https://nextcloud.com/' . str_repeat('a', 478), 'Saved'],
		];
	}

	/**
	 * @dataProvider dataUpdateStylesheetSuccess
	 *
	 * @param string $setting
	 * @param string $value
	 * @param string $message
	 */
	public function testUpdateStylesheetSuccess($setting, $value, $message) {
		$this->themingDefaults
			->expects($this->once())
			->method('set')
			->with($setting, $value);
		$this->l10n
			->expects($this->once())
			->method('t')
			->willReturnCallback(function ($str) {
				return $str;
			});

		$expected = new DataResponse(
			[
				'data' =>
					[
						'message' => $message,
					],
				'status' => 'success',
			]
		);
		$this->assertEquals($expected, $this->themingController->updateStylesheet($setting, $value));
	}

	public function dataUpdateStylesheetError() {
		return [
			['name', str_repeat('a', 251), 'The given name is too long'],
			['url', 'http://example.com/' . str_repeat('a', 501), 'The given web address is too long'],
			['url', str_repeat('a', 501), 'The given web address is not a valid URL'],
			['url', 'javascript:alert(1)', 'The given web address is not a valid URL'],
			['slogan', str_repeat('a', 501), 'The given slogan is too long'],
			['primary_color', '0082C9', 'The given color is invalid'],
			['primary_color', '#0082Z9', 'The given color is invalid'],
			['primary_color', 'Nextcloud', 'The given color is invalid'],
			['background_color', '0082C9', 'The given color is invalid'],
			['background_color', '#0082Z9', 'The given color is invalid'],
			['background_color', 'Nextcloud', 'The given color is invalid'],
			['imprintUrl', '0082C9', 'The given legal notice address is not a valid URL'],
			['imprintUrl', '0082C9', 'The given legal notice address is not a valid URL'],
			['imprintUrl', 'javascript:foo', 'The given legal notice address is not a valid URL'],
			['privacyUrl', '#0082Z9', 'The given privacy policy address is not a valid URL'],
		];
	}

	/**
	 * @dataProvider dataUpdateStylesheetError
	 *
	 * @param string $setting
	 * @param string $value
	 * @param string $message
	 */
	public function testUpdateStylesheetError($setting, $value, $message) {
		$this->themingDefaults
			->expects($this->never())
			->method('set')
			->with($setting, $value);
		$this->l10n
			->expects($this->any())
			->method('t')
			->willReturnCallback(function ($str) {
				return $str;
			});

		$expected = new DataResponse(
			[
				'data' =>
					[
						'message' => $message,
					],
				'status' => 'error',
			],
			Http::STATUS_BAD_REQUEST
		);
		$this->assertEquals($expected, $this->themingController->updateStylesheet($setting, $value));
	}

	public function testUpdateLogoNoData() {
		$this->request
			->expects($this->once())
			->method('getParam')
			->with('key')
			->willReturn('logo');
		$this->request
			->expects($this->once())
			->method('getUploadedFile')
			->with('image')
			->willReturn(null);
		$this->l10n
			->expects($this->any())
			->method('t')
			->willReturnCallback(function ($str) {
				return $str;
			});

		$expected = new DataResponse(
			[
				'data' =>
					[
						'message' => 'No file uploaded',
					],
				'status' => 'failure',
			],
			Http::STATUS_UNPROCESSABLE_ENTITY
		);

		$this->assertEquals($expected, $this->themingController->uploadImage());
	}

	public function testUploadInvalidUploadKey() {
		$this->request
			->expects($this->once())
			->method('getParam')
			->with('key')
			->willReturn('invalid');
		$this->request
			->expects($this->never())
			->method('getUploadedFile');
		$this->l10n
			->expects($this->any())
			->method('t')
			->willReturnCallback(function ($str) {
				return $str;
			});

		$expected = new DataResponse(
			[
				'data' =>
					[
						'message' => 'Invalid key',
					],
				'status' => 'failure',
			],
			Http::STATUS_BAD_REQUEST
		);

		$this->assertEquals($expected, $this->themingController->uploadImage());
	}

	/**
	 * Checks that trying to upload an SVG favicon without imagemagick
	 * results in an unsupported media type response.
	 *
	 * @test
	 * @return void
	 */
	public function testUploadSVGFaviconWithoutImagemagick() {
		$this->imageManager
			->method('shouldReplaceIcons')
			->willReturn(false);

		$this->request
			->expects($this->once())
			->method('getParam')
			->with('key')
			->willReturn('favicon');
		$this->request
			->expects($this->once())
			->method('getUploadedFile')
			->with('image')
			->willReturn([
				'tmp_name' => __DIR__  . '/../../../../tests/data/testimagelarge.svg',
				'type' => 'image/svg',
				'name' => 'testimagelarge.svg',
				'error' => 0,
			]);
		$this->l10n
			->expects($this->any())
			->method('t')
			->willReturnCallback(function ($str) {
				return $str;
			});

		$this->imageManager->expects($this->once())
			->method('updateImage')
			->willThrowException(new \Exception('Unsupported image type'));

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

		$this->assertEquals($expected, $this->themingController->uploadImage());
	}

	public function testUpdateLogoInvalidMimeType() {
		$this->request
			->expects($this->once())
			->method('getParam')
			->with('key')
			->willReturn('logo');
		$this->request
			->expects($this->once())
			->method('getUploadedFile')
			->with('image')
			->willReturn([
				'tmp_name' => __DIR__  . '/../../../../tests/data/lorem.txt',
				'type' => 'application/pdf',
				'name' => 'logo.pdf',
				'error' => 0,
			]);
		$this->l10n
			->expects($this->any())
			->method('t')
			->willReturnCallback(function ($str) {
				return $str;
			});

		$this->imageManager->expects($this->once())
			->method('updateImage')
			->willThrowException(new \Exception('Unsupported image type'));

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

		$this->assertEquals($expected, $this->themingController->uploadImage());
	}

	public function dataUpdateImages() {
		return [
			['image/jpeg', false],
			['image/jpeg', true],
			['image/gif'],
			['image/png'],
			['image/svg+xml'],
			['image/svg']
		];
	}

	/** @dataProvider dataUpdateImages */
	public function testUpdateLogoNormalLogoUpload($mimeType, $folderExists = true) {
		$tmpLogo = \OC::$server->getTempManager()->getTemporaryFolder() . '/logo.svg';
		$destination = \OC::$server->getTempManager()->getTemporaryFolder();

		touch($tmpLogo);
		copy(__DIR__ . '/../../../../tests/data/testimage.png', $tmpLogo);
		$this->request
			->expects($this->once())
			->method('getParam')
			->with('key')
			->willReturn('logo');
		$this->request
			->expects($this->once())
			->method('getUploadedFile')
			->with('image')
			->willReturn([
				'tmp_name' => $tmpLogo,
				'type' => $mimeType,
				'name' => 'logo.svg',
				'error' => 0,
			]);
		$this->l10n
			->expects($this->any())
			->method('t')
			->willReturnCallback(function ($str) {
				return $str;
			});

		$this->imageManager->expects($this->once())
			->method('getImageUrl')
			->with('logo')
			->willReturn('imageUrl');

		$this->imageManager->expects($this->once())
			->method('updateImage');

		$expected = new DataResponse(
			[
				'data' =>
					[
						'name' => 'logo.svg',
						'message' => 'Saved',
						'url' => 'imageUrl',
					],
				'status' => 'success'
			]
		);

		$this->assertEquals($expected, $this->themingController->uploadImage());
	}

	/** @dataProvider dataUpdateImages */
	public function testUpdateLogoLoginScreenUpload($folderExists) {
		$tmpLogo = \OC::$server->getTempManager()->getTemporaryFolder() . 'logo.png';

		touch($tmpLogo);
		copy(__DIR__ . '/../../../../tests/data/desktopapp.png', $tmpLogo);
		$this->request
			->expects($this->once())
			->method('getParam')
			->with('key')
			->willReturn('background');
		$this->request
			->expects($this->once())
			->method('getUploadedFile')
			->with('image')
			->willReturn([
				'tmp_name' => $tmpLogo,
				'type' => 'image/svg+xml',
				'name' => 'logo.svg',
				'error' => 0,
			]);
		$this->l10n
			->expects($this->any())
			->method('t')
			->willReturnCallback(function ($str) {
				return $str;
			});

		$this->imageManager->expects($this->once())
			->method('updateImage');

		$this->imageManager->expects($this->once())
			->method('getImageUrl')
			->with('background')
			->willReturn('imageUrl');
		$expected = new DataResponse(
			[
				'data' =>
					[
						'name' => 'logo.svg',
						'message' => 'Saved',
						'url' => 'imageUrl',
					],
				'status' => 'success'
			]
		);
		$this->assertEquals($expected, $this->themingController->uploadImage());
	}

	public function testUpdateLogoLoginScreenUploadWithInvalidImage() {
		$tmpLogo = \OC::$server->getTempManager()->getTemporaryFolder() . '/logo.svg';

		touch($tmpLogo);
		file_put_contents($tmpLogo, file_get_contents(__DIR__  . '/../../../../tests/data/data.zip'));
		$this->request
			->expects($this->once())
			->method('getParam')
			->with('key')
			->willReturn('logo');
		$this->request
			->expects($this->once())
			->method('getUploadedFile')
			->with('image')
			->willReturn([
				'tmp_name' => $tmpLogo,
				'type' => 'foobar',
				'name' => 'logo.svg',
				'error' => 0,
			]);
		$this->l10n
			->expects($this->any())
			->method('t')
			->willReturnCallback(function ($str) {
				return $str;
			});

		$this->imageManager->expects($this->once())
			->method('updateImage')
			->willThrowException(new \Exception('Unsupported image type'));

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
		$this->assertEquals($expected, $this->themingController->uploadImage());
	}

	public function dataPhpUploadErrors() {
		return [
			[UPLOAD_ERR_INI_SIZE, 'The uploaded file exceeds the upload_max_filesize directive in php.ini'],
			[UPLOAD_ERR_FORM_SIZE, 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form'],
			[UPLOAD_ERR_PARTIAL, 'The file was only partially uploaded'],
			[UPLOAD_ERR_NO_FILE, 'No file was uploaded'],
			[UPLOAD_ERR_NO_TMP_DIR, 'Missing a temporary folder'],
			[UPLOAD_ERR_CANT_WRITE, 'Could not write file to disk'],
			[UPLOAD_ERR_EXTENSION, 'A PHP extension stopped the file upload'],
		];
	}

	/**
	 * @dataProvider dataPhpUploadErrors
	 */
	public function testUpdateLogoLoginScreenUploadWithInvalidImageUpload($error, $expectedErrorMessage) {
		$this->request
			->expects($this->once())
			->method('getParam')
			->with('key')
			->willReturn('background');
		$this->request
			->expects($this->once())
			->method('getUploadedFile')
			->with('image')
			->willReturn([
				'tmp_name' => '',
				'type' => 'image/svg+xml',
				'name' => 'logo.svg',
				'error' => $error,
			]);
		$this->l10n
			->expects($this->any())
			->method('t')
			->willReturnCallback(function ($str) {
				return $str;
			});

		$expected = new DataResponse(
			[
				'data' =>
					[
						'message' => $expectedErrorMessage,
					],
				'status' => 'failure'
			],
			Http::STATUS_UNPROCESSABLE_ENTITY
		);
		$this->assertEquals($expected, $this->themingController->uploadImage());
	}

	/**
	 * @dataProvider dataPhpUploadErrors
	 */
	public function testUpdateLogoUploadWithInvalidImageUpload($error, $expectedErrorMessage) {
		$this->request
			->expects($this->once())
			->method('getParam')
			->with('key')
			->willReturn('background');
		$this->request
			->expects($this->once())
			->method('getUploadedFile')
			->with('image')
			->willReturn([
				'tmp_name' => '',
				'type' => 'text/svg',
				'name' => 'logo.svg',
				'error' => $error,
			]);
		$this->l10n
			->expects($this->any())
			->method('t')
			->willReturnCallback(function ($str) {
				return $str;
			});

		$expected = new DataResponse(
			[
				'data' =>
					[
						'message' => $expectedErrorMessage
					],
				'status' => 'failure'
			],
			Http::STATUS_UNPROCESSABLE_ENTITY
		);
		$this->assertEquals($expected, $this->themingController->uploadImage());
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
						'message' => 'Saved'
					],
				'status' => 'success'
			]
		);
		$this->assertEquals($expected, $this->themingController->undo('MySetting'));
	}

	public function dataUndoDelete() {
		return [
			[ 'backgroundMime', 'background' ],
			[ 'logoMime', 'logo' ]
		];
	}

	/** @dataProvider dataUndoDelete */
	public function testUndoDelete($value, $filename) {
		$this->l10n
			->expects($this->once())
			->method('t')
			->with('Saved')
			->willReturn('Saved');
		$this->themingDefaults
			->expects($this->once())
			->method('undo')
			->with($value)
			->willReturn($value);

		$expected = new DataResponse(
			[
				'data' =>
					[
						'value' => $value,
						'message' => 'Saved',
					],
				'status' => 'success'
			]
		);
		$this->assertEquals($expected, $this->themingController->undo($value));
	}



	public function testGetLogoNotExistent() {
		$this->imageManager->method('getImage')
			->with($this->equalTo('logo'))
			->willThrowException(new NotFoundException());

		$expected = new Http\NotFoundResponse();
		$this->assertEquals($expected, $this->themingController->getImage('logo'));
	}

	public function testGetLogo() {
		$file = $this->createMock(ISimpleFile::class);
		$file->method('getName')->willReturn('logo.svg');
		$file->method('getMTime')->willReturn(42);
		$this->imageManager->expects($this->once())
			->method('getImage')
			->willReturn($file);
		$this->config
			->expects($this->any())
			->method('getAppValue')
			->with('theming', 'logoMime', '')
			->willReturn('text/svg');

		@$expected = new Http\FileDisplayResponse($file);
		$expected->cacheFor(3600);
		$expected->addHeader('Content-Type', 'text/svg');
		$expected->addHeader('Content-Disposition', 'attachment; filename="logo"');
		$csp = new Http\ContentSecurityPolicy();
		$csp->allowInlineStyle();
		$expected->setContentSecurityPolicy($csp);
		@$this->assertEquals($expected, $this->themingController->getImage('logo'));
	}


	public function testGetLoginBackgroundNotExistent() {
		$this->imageManager->method('getImage')
			->with($this->equalTo('background'))
			->willThrowException(new NotFoundException());
		$expected = new Http\NotFoundResponse();
		$this->assertEquals($expected, $this->themingController->getImage('background'));
	}

	public function testGetLoginBackground() {
		$file = $this->createMock(ISimpleFile::class);
		$file->method('getName')->willReturn('background.png');
		$file->method('getMTime')->willReturn(42);
		$this->imageManager->expects($this->once())
			->method('getImage')
			->willReturn($file);

		$this->config
			->expects($this->any())
			->method('getAppValue')
			->with('theming', 'backgroundMime', '')
			->willReturn('image/png');

		@$expected = new Http\FileDisplayResponse($file);
		$expected->cacheFor(3600);
		$expected->addHeader('Content-Type', 'image/png');
		$expected->addHeader('Content-Disposition', 'attachment; filename="background"');
		$csp = new Http\ContentSecurityPolicy();
		$csp->allowInlineStyle();
		$expected->setContentSecurityPolicy($csp);
		@$this->assertEquals($expected, $this->themingController->getImage('background'));
	}

	public function testGetManifest() {
		$this->config
			->expects($this->once())
			->method('getAppValue')
			->with('theming', 'cachebuster', '0')
			->willReturn('0');
		$this->themingDefaults
			->expects($this->any())
			->method('getName')
			->willReturn('Nextcloud');
		$this->urlGenerator
			->expects($this->once())
			->method('getBaseUrl')
			->willReturn('localhost');
		$this->urlGenerator
			->expects($this->exactly(2))
			->method('linkToRoute')
			->withConsecutive(
				['theming.Icon.getTouchIcon', ['app' => 'core']],
				['theming.Icon.getFavicon', ['app' => 'core']],
			)->willReturnOnConsecutiveCalls(
				'touchicon',
				'favicon',
			);
		$response = new Http\JSONResponse([
			'name' => 'Nextcloud',
			'start_url' => 'localhost',
			'icons' =>
				[
					[
						'src' => 'touchicon?v=0',
						'type' => 'image/png',
						'sizes' => '512x512'
					],
					[
						'src' => 'favicon?v=0',
						'type' => 'image/svg+xml',
						'sizes' => '16x16'
					]
				],
			'display' => 'standalone',
			'short_name' => 'Nextcloud',
			'theme_color' => null,
			'background_color' => null,
			'description' => null
		]);
		$response->cacheFor(3600);
		$this->assertEquals($response, $this->themingController->getManifest('core'));
	}
}
