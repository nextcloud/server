<?php
/**
 * @copyright Copyright (c) 2016 Bjoern Schiessle <bjoern@schiessle.org>
 * @copyright Copyright (c) 2016 Lukas Reschke <lukas@statuscode.ch>
 *
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
 * @author Bjoern Schiessle <bjoern@schiessle.org>
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Daniel Calviño Sánchez <danxuliu@gmail.com>
 * @author Jan-Christoph Borchardt <hey@jancborchardt.net>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Julius Haertl <jus@bitgrid.net>
 * @author Julius Härtl <jus@bitgrid.net>
 * @author Kyle Fazzari <kyrofa@ubuntu.com>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author nhirokinet <nhirokinet@nhiroki.net>
 * @author rakekniven <mark.ziegler@rakekniven.de>
 * @author Robin Appelman <robin@icewind.nl>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Thomas Citharel <nextcloud@tcit.fr>
 * @author Kate Döen <kate.doeen@nextcloud.com>
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */
namespace OCA\Theming\Controller;

use InvalidArgumentException;
use OCA\Theming\ImageManager;
use OCA\Theming\Service\ThemesService;
use OCA\Theming\ThemingDefaults;
use OCP\App\IAppManager;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataDisplayResponse;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Http\FileDisplayResponse;
use OCP\AppFramework\Http\JSONResponse;
use OCP\AppFramework\Http\NotFoundResponse;
use OCP\Files\IAppData;
use OCP\Files\NotFoundException;
use OCP\Files\NotPermittedException;
use OCP\IConfig;
use OCP\IL10N;
use OCP\IRequest;
use OCP\ITempManager;
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

	private ThemingDefaults $themingDefaults;
	private IL10N $l10n;
	private IConfig $config;
	private ITempManager $tempManager;
	private IAppData $appData;
	private IURLGenerator $urlGenerator;
	private IAppManager $appManager;
	private ImageManager $imageManager;
	private ThemesService $themesService;

	public function __construct(
		$appName,
		IRequest $request,
		IConfig $config,
		ThemingDefaults $themingDefaults,
		IL10N $l,
		ITempManager $tempManager,
		IAppData $appData,
		IURLGenerator $urlGenerator,
		IAppManager $appManager,
		ImageManager $imageManager,
		ThemesService $themesService
	) {
		parent::__construct($appName, $request);

		$this->themingDefaults = $themingDefaults;
		$this->l10n = $l;
		$this->config = $config;
		$this->tempManager = $tempManager;
		$this->appData = $appData;
		$this->urlGenerator = $urlGenerator;
		$this->appManager = $appManager;
		$this->imageManager = $imageManager;
		$this->themesService = $themesService;
	}

	/**
	 * @AuthorizedAdminSetting(settings=OCA\Theming\Settings\Admin)
	 * @param string $setting
	 * @param string $value
	 * @return DataResponse
	 * @throws NotPermittedException
	 */
	public function updateStylesheet($setting, $value) {
		$value = trim($value);
		$error = null;
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
			case 'color':
				if (!preg_match('/^\#([0-9a-f]{3}|[0-9a-f]{6})$/i', $value)) {
					$error = $this->l10n->t('The given color is invalid');
				}
				break;
			case 'disable-user-theming':
				if ($value !== 'yes' && $value !== 'no') {
					$error = $this->l10n->t('Disable-user-theming should be true or false');
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

		$this->themingDefaults->set($setting, $value);

		return new DataResponse([
			'data' => [
				'message' => $this->l10n->t('Saved'),
			],
			'status' => 'success'
		]);
	}

	/**
	 * @AuthorizedAdminSetting(settings=OCA\Theming\Settings\Admin)
	 * @param string $setting
	 * @param mixed $value
	 * @return DataResponse
	 * @throws NotPermittedException
	 */
	public function updateAppMenu($setting, $value) {
		$error = null;
		switch ($setting) {
			case 'defaultApps':
				if (is_array($value)) {
					try {
						$this->appManager->setDefaultApps($value);
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
	 * @AuthorizedAdminSetting(settings=OCA\Theming\Settings\Admin)
	 * @return DataResponse
	 * @throws NotPermittedException
	 */
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
	 * @AuthorizedAdminSetting(settings=OCA\Theming\Settings\Admin)
	 *
	 * @param string $setting setting which should be reverted
	 * @return DataResponse
	 * @throws NotPermittedException
	 */
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
	 * @AuthorizedAdminSetting(settings=OCA\Theming\Settings\Admin)
	 *
	 * @return DataResponse
	 * @throws NotPermittedException
	 */
	public function undoAll(): DataResponse {
		$this->themingDefaults->undoAll();
		$this->appManager->setDefaultApps([]);

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
	 * @PublicPage
	 * @NoCSRFRequired
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
	 * @NoCSRFRequired
	 * @PublicPage
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
	 * @NoCSRFRequired
	 * @PublicPage
	 * @BruteForceProtection(action=manifest)
	 *
	 * Get the manifest for an app
	 *
	 * @param string $app ID of the app
	 * @psalm-suppress LessSpecificReturnStatement The content of the Manifest doesn't need to be described in the return type
	 * @return JSONResponse<Http::STATUS_OK, array{name: string, short_name: string, start_url: string, theme_color: string, background_color: string, description: string, icons: array{src: non-empty-string, type: string, sizes: string}[], display: string}, array{}>|JSONResponse<Http::STATUS_NOT_FOUND, array{}, array{}>
	 *
	 * 200: Manifest returned
	 * 404: App not found
	 */
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
