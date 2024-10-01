<?php
/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Theming\Controller;

use InvalidArgumentException;
use OCA\Theming\ImageManager;
use OCA\Theming\Service\ThemesService;
use OCA\Theming\Settings\Admin;
use OCA\Theming\ThemingDefaults;
use OCP\App\IAppManager;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\AuthorizedAdminSetting;
use OCP\AppFramework\Http\Attribute\BruteForceProtection;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\Attribute\PublicPage;
use OCP\AppFramework\Http\DataDisplayResponse;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Http\FileDisplayResponse;
use OCP\AppFramework\Http\JSONResponse;
use OCP\AppFramework\Http\NotFoundResponse;
use OCP\AppFramework\Services\IAppConfig;
use OCP\Files\NotFoundException;
use OCP\Files\NotPermittedException;
use OCP\IConfig;
use OCP\IL10N;
use OCP\INavigationManager;
use OCP\IRequest;
use OCP\IURLGenerator;
use ScssPhp\ScssPhp\Compiler;

/**
 * Class ThemingController
 *
 * handle ajax requests to update the theme
 *
 * @package OCA\Theming\Controller
 */
class ThemingController extends Controller {
	public const VALID_UPLOAD_KEYS = ['header', 'logo', 'logoheader', 'background', 'favicon'];

	public function __construct(
		$appName,
		IRequest $request,
		private IConfig $config,
		private IAppConfig $appConfig,
		private ThemingDefaults $themingDefaults,
		private IL10N $l10n,
		private IURLGenerator $urlGenerator,
		private IAppManager $appManager,
		private ImageManager $imageManager,
		private ThemesService $themesService,
		private INavigationManager $navigationManager,
	) {
		parent::__construct($appName, $request);
	}

	/**
	 * @param string $setting
	 * @param string $value
	 * @return DataResponse
	 * @throws NotPermittedException
	 */
	#[AuthorizedAdminSetting(settings: Admin::class)]
	public function updateStylesheet($setting, $value) {
		$value = trim($value);
		$error = null;
		$saved = false;
		switch ($setting) {
			case 'name':
				if (strlen($value) > 250) {
					$error = $this->l10n->t('The given name is too long');
				}
				break;
			case 'url':
				if (strlen($value) > 500) {
					$error = $this->l10n->t('The given web address is too long');
				}
				if (!$this->isValidUrl($value)) {
					$error = $this->l10n->t('The given web address is not a valid URL');
				}
				break;
			case 'imprintUrl':
				if (strlen($value) > 500) {
					$error = $this->l10n->t('The given legal notice address is too long');
				}
				if (!$this->isValidUrl($value)) {
					$error = $this->l10n->t('The given legal notice address is not a valid URL');
				}
				break;
			case 'privacyUrl':
				if (strlen($value) > 500) {
					$error = $this->l10n->t('The given privacy policy address is too long');
				}
				if (!$this->isValidUrl($value)) {
					$error = $this->l10n->t('The given privacy policy address is not a valid URL');
				}
				break;
			case 'slogan':
				if (strlen($value) > 500) {
					$error = $this->l10n->t('The given slogan is too long');
				}
				break;
			case 'primary_color':
				if (!preg_match('/^\#([0-9a-f]{3}|[0-9a-f]{6})$/i', $value)) {
					$error = $this->l10n->t('The given color is invalid');
				} else {
					$this->appConfig->setAppValueString('primary_color', $value);
					$saved = true;
				}
				break;
			case 'background_color':
				if (!preg_match('/^\#([0-9a-f]{3}|[0-9a-f]{6})$/i', $value)) {
					$error = $this->l10n->t('The given color is invalid');
				} else {
					$this->appConfig->setAppValueString('background_color', $value);
					$saved = true;
				}
				break;
			case 'disable-user-theming':
				if (!in_array($value, ['yes', 'true', 'no', 'false'])) {
					$error = $this->l10n->t('Disable-user-theming should be true or false');
				} else {
					$this->appConfig->setAppValueBool('disable-user-theming', $value === 'yes' || $value === 'true');
					$saved = true;
				}
				break;
		}
		if ($error !== null) {
			return new DataResponse([
				'data' => [
					'message' => $error,
				],
				'status' => 'error'
			], Http::STATUS_BAD_REQUEST);
		}

		if (!$saved) {
			$this->themingDefaults->set($setting, $value);
		}

		return new DataResponse([
			'data' => [
				'message' => $this->l10n->t('Saved'),
			],
			'status' => 'success'
		]);
	}

	/**
	 * @param string $setting
	 * @param mixed $value
	 * @return DataResponse
	 * @throws NotPermittedException
	 */
	#[AuthorizedAdminSetting(settings: Admin::class)]
	public function updateAppMenu($setting, $value) {
		$error = null;
		switch ($setting) {
			case 'defaultApps':
				if (is_array($value)) {
					try {
						$this->navigationManager->setDefaultEntryIds($value);
					} catch (InvalidArgumentException $e) {
						$error = $this->l10n->t('Invalid app given');
					}
				} else {
					$error = $this->l10n->t('Invalid type for setting "defaultApp" given');
				}
				break;
			default:
				$error = $this->l10n->t('Invalid setting key');
		}
		if ($error !== null) {
			return new DataResponse([
				'data' => [
					'message' => $error,
				],
				'status' => 'error'
			], Http::STATUS_BAD_REQUEST);
		}

		return new DataResponse([
			'data' => [
				'message' => $this->l10n->t('Saved'),
			],
			'status' => 'success'
		]);
	}

	/**
	 * Check that a string is a valid http/https url
	 */
	private function isValidUrl(string $url): bool {
		return ((str_starts_with($url, 'http://') || str_starts_with($url, 'https://')) &&
			filter_var($url, FILTER_VALIDATE_URL) !== false);
	}

	/**
	 * @return DataResponse
	 * @throws NotPermittedException
	 */
	#[AuthorizedAdminSetting(settings: Admin::class)]
	public function uploadImage(): DataResponse {
		$key = $this->request->getParam('key');
		if (!in_array($key, self::VALID_UPLOAD_KEYS, true)) {
			return new DataResponse(
				[
					'data' => [
						'message' => 'Invalid key'
					],
					'status' => 'failure',
				],
				Http::STATUS_BAD_REQUEST
			);
		}
		$image = $this->request->getUploadedFile('image');
		$error = null;
		$phpFileUploadErrors = [
			UPLOAD_ERR_OK => $this->l10n->t('The file was uploaded'),
			UPLOAD_ERR_INI_SIZE => $this->l10n->t('The uploaded file exceeds the upload_max_filesize directive in php.ini'),
			UPLOAD_ERR_FORM_SIZE => $this->l10n->t('The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form'),
			UPLOAD_ERR_PARTIAL => $this->l10n->t('The file was only partially uploaded'),
			UPLOAD_ERR_NO_FILE => $this->l10n->t('No file was uploaded'),
			UPLOAD_ERR_NO_TMP_DIR => $this->l10n->t('Missing a temporary folder'),
			UPLOAD_ERR_CANT_WRITE => $this->l10n->t('Could not write file to disk'),
			UPLOAD_ERR_EXTENSION => $this->l10n->t('A PHP extension stopped the file upload'),
		];
		if (empty($image)) {
			$error = $this->l10n->t('No file uploaded');
		}
		if (!empty($image) && array_key_exists('error', $image) && $image['error'] !== UPLOAD_ERR_OK) {
			$error = $phpFileUploadErrors[$image['error']];
		}

		if ($error !== null) {
			return new DataResponse(
				[
					'data' => [
						'message' => $error
					],
					'status' => 'failure',
				],
				Http::STATUS_UNPROCESSABLE_ENTITY
			);
		}

		try {
			$mime = $this->imageManager->updateImage($key, $image['tmp_name']);
			$this->themingDefaults->set($key . 'Mime', $mime);
		} catch (\Exception $e) {
			return new DataResponse(
				[
					'data' => [
						'message' => $e->getMessage()
					],
					'status' => 'failure',
				],
				Http::STATUS_UNPROCESSABLE_ENTITY
			);
		}

		$name = $image['name'];

		return new DataResponse(
			[
				'data' =>
					[
						'name' => $name,
						'url' => $this->imageManager->getImageUrl($key),
						'message' => $this->l10n->t('Saved'),
					],
				'status' => 'success'
			]
		);
	}

	/**
	 * Revert setting to default value
	 *
	 * @param string $setting setting which should be reverted
	 * @return DataResponse
	 * @throws NotPermittedException
	 */
	#[AuthorizedAdminSetting(settings: Admin::class)]
	public function undo(string $setting): DataResponse {
		$value = $this->themingDefaults->undo($setting);

		return new DataResponse(
			[
				'data' =>
					[
						'value' => $value,
						'message' => $this->l10n->t('Saved'),
					],
				'status' => 'success'
			]
		);
	}

	/**
	 * Revert all theming settings to their default values
	 *
	 * @return DataResponse
	 * @throws NotPermittedException
	 */
	#[AuthorizedAdminSetting(settings: Admin::class)]
	public function undoAll(): DataResponse {
		$this->themingDefaults->undoAll();
		$this->navigationManager->setDefaultEntryIds([]);

		return new DataResponse(
			[
				'data' =>
					[
						'message' => $this->l10n->t('Saved'),
					],
				'status' => 'success'
			]
		);
	}

	/**
	 * @NoSameSiteCookieRequired
	 *
	 * Get an image
	 *
	 * @param string $key Key of the image
	 * @param bool $useSvg Return image as SVG
	 * @return FileDisplayResponse<Http::STATUS_OK, array{}>|NotFoundResponse<Http::STATUS_NOT_FOUND, array{}>
	 * @throws NotPermittedException
	 *
	 * 200: Image returned
	 * 404: Image not found
	 */
	#[PublicPage]
	#[NoCSRFRequired]
	public function getImage(string $key, bool $useSvg = true) {
		try {
			$file = $this->imageManager->getImage($key, $useSvg);
		} catch (NotFoundException $e) {
			return new NotFoundResponse();
		}

		$response = new FileDisplayResponse($file);
		$csp = new Http\ContentSecurityPolicy();
		$csp->allowInlineStyle();
		$response->setContentSecurityPolicy($csp);
		$response->cacheFor(3600);
		$response->addHeader('Content-Type', $this->config->getAppValue($this->appName, $key . 'Mime', ''));
		$response->addHeader('Content-Disposition', 'attachment; filename="' . $key . '"');
		if (!$useSvg) {
			$response->addHeader('Content-Type', 'image/png');
		} else {
			$response->addHeader('Content-Type', $this->config->getAppValue($this->appName, $key . 'Mime', ''));
		}
		return $response;
	}

	/**
	 * @NoSameSiteCookieRequired
	 * @NoTwoFactorRequired
	 *
	 * Get the CSS stylesheet for a theme
	 *
	 * @param string $themeId ID of the theme
	 * @param bool $plain Let the browser decide the CSS priority
	 * @param bool $withCustomCss Include custom CSS
	 * @return DataDisplayResponse<Http::STATUS_OK, array{Content-Type: 'text/css'}>|NotFoundResponse<Http::STATUS_NOT_FOUND, array{}>
	 *
	 * 200: Stylesheet returned
	 * 404: Theme not found
	 */
	#[PublicPage]
	#[NoCSRFRequired]
	public function getThemeStylesheet(string $themeId, bool $plain = false, bool $withCustomCss = false) {
		$themes = $this->themesService->getThemes();
		if (!in_array($themeId, array_keys($themes))) {
			return new NotFoundResponse();
		}

		$theme = $themes[$themeId];
		$customCss = $theme->getCustomCss();

		// Generate variables
		$variables = '';
		foreach ($theme->getCSSVariables() as $variable => $value) {
			$variables .= "$variable:$value; ";
		};

		// If plain is set, the browser decides of the css priority
		if ($plain) {
			$css = ":root { $variables } " . $customCss;
		} else {
			// If not set, we'll rely on the body class
			$compiler = new Compiler();
			$compiledCss = $compiler->compileString("[data-theme-$themeId] { $variables $customCss }");
			$css = $compiledCss->getCss();
			;
		}

		try {
			$response = new DataDisplayResponse($css, Http::STATUS_OK, ['Content-Type' => 'text/css']);
			$response->cacheFor(86400);
			return $response;
		} catch (NotFoundException $e) {
			return new NotFoundResponse();
		}
	}

	/**
	 * Get the manifest for an app
	 *
	 * @param string $app ID of the app
	 * @psalm-suppress LessSpecificReturnStatement The content of the Manifest doesn't need to be described in the return type
	 * @return JSONResponse<Http::STATUS_OK, array{name: string, short_name: string, start_url: string, theme_color: string, background_color: string, description: string, icons: array{src: non-empty-string, type: string, sizes: string}[], display: string}, array{}>|JSONResponse<Http::STATUS_NOT_FOUND, array{}, array{}>
	 *
	 * 200: Manifest returned
	 * 404: App not found
	 */
	#[PublicPage]
	#[NoCSRFRequired]
	#[BruteForceProtection(action: 'manifest')]
	public function getManifest(string $app): JSONResponse {
		$cacheBusterValue = $this->config->getAppValue('theming', 'cachebuster', '0');
		if ($app === 'core' || $app === 'settings') {
			$name = $this->themingDefaults->getName();
			$shortName = $this->themingDefaults->getName();
			$startUrl = $this->urlGenerator->getBaseUrl();
			$description = $this->themingDefaults->getSlogan();
		} else {
			if (!$this->appManager->isEnabledForUser($app)) {
				$response = new JSONResponse([], Http::STATUS_NOT_FOUND);
				$response->throttle(['action' => 'manifest', 'app' => $app]);
				return $response;
			}

			$info = $this->appManager->getAppInfo($app, false, $this->l10n->getLanguageCode());
			$name = $info['name'] . ' - ' . $this->themingDefaults->getName();
			$shortName = $info['name'];
			if (str_contains($this->request->getRequestUri(), '/index.php/')) {
				$startUrl = $this->urlGenerator->getBaseUrl() . '/index.php/apps/' . $app . '/';
			} else {
				$startUrl = $this->urlGenerator->getBaseUrl() . '/apps/' . $app . '/';
			}
			$description = $info['summary'] ?? '';
		}
		/**
		 * @var string $description
		 * @var string $shortName
		 */
		$responseJS = [
			'name' => $name,
			'short_name' => $shortName,
			'start_url' => $startUrl,
			'theme_color' => $this->themingDefaults->getColorPrimary(),
			'background_color' => $this->themingDefaults->getColorPrimary(),
			'description' => $description,
			'icons' =>
				[
					[
						'src' => $this->urlGenerator->linkToRoute('theming.Icon.getTouchIcon',
							['app' => $app]) . '?v=' . $cacheBusterValue,
						'type' => 'image/png',
						'sizes' => '512x512'
					],
					[
						'src' => $this->urlGenerator->linkToRoute('theming.Icon.getFavicon',
							['app' => $app]) . '?v=' . $cacheBusterValue,
						'type' => 'image/svg+xml',
						'sizes' => '16x16'
					]
				],
			'display' => 'standalone'
		];
		$response = new JSONResponse($responseJS);
		$response->cacheFor(3600);
		return $response;
	}
}
