<?php

declare(strict_types=1);
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
use OCP\AppFramework\Http\ContentSecurityPolicy;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Http\FileDisplayResponse;
use OCP\AppFramework\Http\JSONResponse;
use OCP\AppFramework\Http\NotFoundResponse;
use OCP\AppFramework\Services\IAppConfig;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\Files\NotFoundException;
use OCP\Files\SimpleFS\ISimpleFile;
use OCP\IConfig;
use OCP\IL10N;
use OCP\INavigationManager;
use OCP\IRequest;
use OCP\ITempManager;
use OCP\IURLGenerator;
use OCP\Server;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class ThemingControllerTest extends TestCase {

	private IRequest&MockObject $request;
	private IConfig&MockObject $config;
	private IAppConfig&MockObject $appConfig;
	private ThemingDefaults&MockObject $themingDefaults;
	private IL10N&MockObject $l10n;
	private IAppManager&MockObject $appManager;
	private ImageManager&MockObject $imageManager;
	private IURLGenerator&MockObject $urlGenerator;
	private ThemesService&MockObject $themesService;
	private INavigationManager&MockObject $navigationManager;

	private ThemingController $themingController;

	protected function setUp(): void {
		$this->request = $this->createMock(IRequest::class);
		$this->config = $this->createMock(IConfig::class);
		$this->appConfig = $this->createMock(IAppConfig::class);
		$this->themingDefaults = $this->createMock(ThemingDefaults::class);
		$this->l10n = $this->createMock(L10N::class);
		$this->appManager = $this->createMock(IAppManager::class);
		$this->urlGenerator = $this->createMock(IURLGenerator::class);
		$this->imageManager = $this->createMock(ImageManager::class);
		$this->themesService = $this->createMock(ThemesService::class);
		$this->navigationManager = $this->createMock(INavigationManager::class);

		$timeFactory = $this->createMock(ITimeFactory::class);
		$timeFactory->expects($this->any())
			->method('getTime')
			->willReturn(123);

		$this->overwriteService(ITimeFactory::class, $timeFactory);

		$this->themingController = new ThemingController(
			'theming',
			$this->request,
			$this->config,
			$this->appConfig,
			$this->themingDefaults,
			$this->l10n,
			$this->urlGenerator,
			$this->appManager,
			$this->imageManager,
			$this->themesService,
			$this->navigationManager,
		);

		parent::setUp();
	}

	public static function dataUpdateStylesheetSuccess(): array {
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

	#[\PHPUnit\Framework\Attributes\DataProvider('dataUpdateStylesheetSuccess')]
	public function testUpdateStylesheetSuccess(string $setting, string $value, string $message): void {
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
				'data'
					=> [
						'message' => $message,
					],
				'status' => 'success',
			]
		);
		$this->assertEquals($expected, $this->themingController->updateStylesheet($setting, $value));
	}

	public static function dataUpdateStylesheetError(): array {
		$urls = [
			'url' => 'web address',
			'imprintUrl' => 'legal notice address',
			'privacyUrl' => 'privacy policy address',
		];

		$urlTests = [];
		foreach ($urls as $urlKey => $urlName) {
			// Check length limit
			$urlTests[] = [$urlKey, 'http://example.com/' . str_repeat('a', 501), "The given {$urlName} is too long"];
			// Check potential evil javascript
			$urlTests[] = [$urlKey, 'javascript:alert(1)', "The given {$urlName} is not a valid URL"];
			// Check XSS
			$urlTests[] = [$urlKey, 'https://example.com/"><script/src="alert(\'1\')"><a/href/="', "The given {$urlName} is not a valid URL"];
		}

		return [
			['name', str_repeat('a', 251), 'The given name is too long'],
			['slogan', str_repeat('a', 501), 'The given slogan is too long'],
			['primary_color', '0082C9', 'The given color is invalid'],
			['primary_color', '#0082Z9', 'The given color is invalid'],
			['primary_color', 'Nextcloud', 'The given color is invalid'],
			['background_color', '0082C9', 'The given color is invalid'],
			['background_color', '#0082Z9', 'The given color is invalid'],
			['background_color', 'Nextcloud', 'The given color is invalid'],

			...$urlTests,
		];
	}

	#[\PHPUnit\Framework\Attributes\DataProvider('dataUpdateStylesheetError')]
	public function testUpdateStylesheetError(string $setting, string $value, string $message): void {
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
				'data'
					=> [
						'message' => $message,
					],
				'status' => 'error',
			],
			Http::STATUS_BAD_REQUEST
		);
		$this->assertEquals($expected, $this->themingController->updateStylesheet($setting, $value));
	}

	public function testUpdateLogoNoData(): void {
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
				'data'
					=> [
						'message' => 'No file uploaded',
					],
				'status' => 'failure',
			],
			Http::STATUS_UNPROCESSABLE_ENTITY
		);

		$this->assertEquals($expected, $this->themingController->uploadImage());
	}

	public function testUploadInvalidUploadKey(): void {
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
				'data'
					=> [
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
	 */
	public function testUploadSVGFaviconWithoutImagemagick(): void {
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
				'tmp_name' => __DIR__ . '/../../../../tests/data/testimagelarge.svg',
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
				'data'
					=> [
						'message' => 'Unsupported image type',
					],
				'status' => 'failure'
			],
			Http::STATUS_UNPROCESSABLE_ENTITY
		);

		$this->assertEquals($expected, $this->themingController->uploadImage());
	}

	public function testUpdateLogoInvalidMimeType(): void {
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
				'tmp_name' => __DIR__ . '/../../../../tests/data/lorem.txt',
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
				'data'
					=> [
						'message' => 'Unsupported image type',
					],
				'status' => 'failure'
			],
			Http::STATUS_UNPROCESSABLE_ENTITY
		);

		$this->assertEquals($expected, $this->themingController->uploadImage());
	}

	public static function dataUpdateImages(): array {
		return [
			['image/jpeg', false],
			['image/jpeg', true],
			['image/gif'],
			['image/png'],
			['image/svg+xml'],
			['image/svg']
		];
	}

	#[\PHPUnit\Framework\Attributes\DataProvider('dataUpdateImages')]
	public function testUpdateLogoNormalLogoUpload(string $mimeType, bool $folderExists = true): void {
		$tmpLogo = Server::get(ITempManager::class)->getTemporaryFolder() . '/logo.svg';
		$destination = Server::get(ITempManager::class)->getTemporaryFolder();

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
				'data'
					=> [
						'name' => 'logo.svg',
						'message' => 'Saved',
						'url' => 'imageUrl',
					],
				'status' => 'success'
			]
		);

		$this->assertEquals($expected, $this->themingController->uploadImage());
	}

	public function testUpdateLogoLoginScreenUpload(): void {
		$tmpLogo = Server::get(ITempManager::class)->getTemporaryFolder() . 'logo.png';

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
				'data'
					=> [
						'name' => 'logo.svg',
						'message' => 'Saved',
						'url' => 'imageUrl',
					],
				'status' => 'success'
			]
		);
		$this->assertEquals($expected, $this->themingController->uploadImage());
	}

	public function testUpdateLogoLoginScreenUploadWithInvalidImage(): void {
		$tmpLogo = Server::get(ITempManager::class)->getTemporaryFolder() . '/logo.svg';

		touch($tmpLogo);
		file_put_contents($tmpLogo, file_get_contents(__DIR__ . '/../../../../tests/data/data.zip'));
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
				'data'
					=> [
						'message' => 'Unsupported image type',
					],
				'status' => 'failure'
			],
			Http::STATUS_UNPROCESSABLE_ENTITY
		);
		$this->assertEquals($expected, $this->themingController->uploadImage());
	}

	public static function dataPhpUploadErrors(): array {
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

	#[\PHPUnit\Framework\Attributes\DataProvider('dataPhpUploadErrors')]
	public function testUpdateLogoLoginScreenUploadWithInvalidImageUpload(int $error, string $expectedErrorMessage): void {
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
				'data'
					=> [
						'message' => $expectedErrorMessage,
					],
				'status' => 'failure'
			],
			Http::STATUS_UNPROCESSABLE_ENTITY
		);
		$this->assertEquals($expected, $this->themingController->uploadImage());
	}

	#[\PHPUnit\Framework\Attributes\DataProvider('dataPhpUploadErrors')]
	public function testUpdateLogoUploadWithInvalidImageUpload($error, $expectedErrorMessage): void {
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
				'data'
					=> [
						'message' => $expectedErrorMessage
					],
				'status' => 'failure'
			],
			Http::STATUS_UNPROCESSABLE_ENTITY
		);
		$this->assertEquals($expected, $this->themingController->uploadImage());
	}

	public function testUndo(): void {
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
				'data'
					=> [
						'value' => 'MyValue',
						'message' => 'Saved'
					],
				'status' => 'success'
			]
		);
		$this->assertEquals($expected, $this->themingController->undo('MySetting'));
	}

	public static function dataUndoDelete(): array {
		return [
			[ 'backgroundMime', 'background' ],
			[ 'logoMime', 'logo' ]
		];
	}

	#[\PHPUnit\Framework\Attributes\DataProvider('dataUndoDelete')]
	public function testUndoDelete(string $value, string $filename): void {
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
				'data'
					=> [
						'value' => $value,
						'message' => 'Saved',
					],
				'status' => 'success'
			]
		);
		$this->assertEquals($expected, $this->themingController->undo($value));
	}



	public function testGetLogoNotExistent(): void {
		$this->imageManager->method('getImage')
			->with($this->equalTo('logo'))
			->willThrowException(new NotFoundException());

		$expected = new NotFoundResponse();
		$this->assertEquals($expected, $this->themingController->getImage('logo'));
	}

	public function testGetLogo(): void {
		$file = $this->createMock(ISimpleFile::class);
		$file->method('getName')->willReturn('logo.svg');
		$file->method('getMTime')->willReturn(42);
		$file->method('getMimeType')->willReturn('text/svg');
		$this->imageManager->expects($this->once())
			->method('getImage')
			->willReturn($file);
		$this->config
			->expects($this->any())
			->method('getAppValue')
			->with('theming', 'logoMime', '')
			->willReturn('text/svg');

		@$expected = new FileDisplayResponse($file);
		$expected->cacheFor(3600);
		$expected->addHeader('Content-Type', 'text/svg');
		$expected->addHeader('Content-Disposition', 'attachment; filename="logo"');
		$csp = new ContentSecurityPolicy();
		$csp->allowInlineStyle();
		$expected->setContentSecurityPolicy($csp);
		@$this->assertEquals($expected, $this->themingController->getImage('logo', true));
	}


	public function testGetLoginBackgroundNotExistent(): void {
		$this->imageManager->method('getImage')
			->with($this->equalTo('background'))
			->willThrowException(new NotFoundException());
		$expected = new NotFoundResponse();
		$this->assertEquals($expected, $this->themingController->getImage('background'));
	}

	public function testGetLoginBackground(): void {
		$file = $this->createMock(ISimpleFile::class);
		$file->method('getName')->willReturn('background.png');
		$file->method('getMTime')->willReturn(42);
		$file->method('getMimeType')->willReturn('image/png');
		$this->imageManager->expects($this->once())
			->method('getImage')
			->willReturn($file);

		$this->config
			->expects($this->any())
			->method('getAppValue')
			->with('theming', 'backgroundMime', '')
			->willReturn('image/png');

		@$expected = new FileDisplayResponse($file);
		$expected->cacheFor(3600);
		$expected->addHeader('Content-Type', 'image/png');
		$expected->addHeader('Content-Disposition', 'attachment; filename="background"');
		$csp = new ContentSecurityPolicy();
		$csp->allowInlineStyle();
		$expected->setContentSecurityPolicy($csp);
		@$this->assertEquals($expected, $this->themingController->getImage('background'));
	}

	public static function dataGetManifest(): array {
		return [
			[true],
			[false],
		];
	}

	#[\PHPUnit\Framework\Attributes\DataProvider('dataGetManifest')]
	public function testGetManifest(bool $standalone): void {
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
			->willReturnMap([
				['theming.Icon.getTouchIcon', ['app' => 'core'], 'touchicon'],
				['theming.Icon.getFavicon', ['app' => 'core'], 'favicon'],
			]);
		$this->config
			->expects($this->exactly(2))
			->method('getSystemValueBool')
			->with('theming.standalone_window.enabled', true)
			->willReturn($standalone);
		$response = new JSONResponse([
			'name' => 'Nextcloud',
			'start_url' => 'localhost',
			'icons'
				=> [
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
			'display_override' => [$standalone ? 'minimal-ui' : ''],
			'display' => $standalone ? 'standalone' : 'browser',
			'short_name' => 'Nextcloud',
			'theme_color' => null,
			'background_color' => null,
			'description' => null
		]);
		$response->cacheFor(3600);
		$this->assertEquals($response, $this->themingController->getManifest('core'));
	}
}
