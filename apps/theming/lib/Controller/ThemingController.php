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

use OC\Template\SCSSCacher;
use OCA\Theming\ImageManager;
use OCA\Theming\ThemingDefaults;
use OCP\App\IAppManager;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Http\FileDisplayResponse;
use OCP\AppFramework\Http\NotFoundResponse;
use OCP\Files\IAppData;
use OCP\Files\NotFoundException;
use OCP\Files\NotPermittedException;
use OCP\IConfig;
use OCP\IL10N;
use OCP\IRequest;
use OCP\ITempManager;
use OCP\IURLGenerator;
use OCP\Validator\Constraints\CssColor;
use OCP\Validator\Constraints\Length;
use OCP\Validator\Constraints\Url;
use OCP\Validator\IValidator;
use OCP\Validator\Violation;

/**
 * Class ThemingController
 *
 * handle ajax requests to update the theme
 *
 * @package OCA\Theming\Controller
 */
class ThemingController extends Controller {
	private ThemingDefaults $themingDefaults;
	private IL10N $l10n;
	private IConfig $config;
	private ITempManager $tempManager;
	private IAppData $appData;
	private SCSSCacher $scssCacher;
	private IURLGenerator $urlGenerator;
	private IAppManager $appManager;
	private ImageManager $imageManager;
	private IValidator $validator;

	/**
	 * ThemingController constructor.
	 * @param string $appName
	 */
	public function __construct(
		$appName,
		IRequest $request,
		IConfig $config,
		ThemingDefaults $themingDefaults,
		IL10N $l,
		ITempManager $tempManager,
		IAppData $appData,
		SCSSCacher $scssCacher,
		IURLGenerator $urlGenerator,
		IAppManager $appManager,
		ImageManager $imageManager,
		IValidator $validator
	) {
		parent::__construct($appName, $request);

		$this->themingDefaults = $themingDefaults;
		$this->l10n = $l;
		$this->config = $config;
		$this->tempManager = $tempManager;
		$this->appData = $appData;
		$this->scssCacher = $scssCacher;
		$this->urlGenerator = $urlGenerator;
		$this->appManager = $appManager;
		$this->imageManager = $imageManager;
		$this->validator = $validator;
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
		$violations = [];
		switch ($setting) {
			case 'name':
				$violations = $this->validator->validate($value, [
					new Length([
						'max' => 250,
						'maxMessage' => $this->l10n->t('The given name is too long'),
					])
				]);
				break;
			case 'url':
				$violations = $this->validator->validate($value, [
					new Length([
						'max' => 500,
						'maxMessage' => $this->l10n->t('The given web address is too long'),
					]),
					new Url(false, ['http', 'https'], $this->l10n->t('The given web address is not a valid URL')),
				]);

				break;
			case 'imprintUrl':
				$violations = $this->validator->validate($value, [
					new Length([
						'max' => 500,
						'maxMessage' => $this->l10n->t('The given legal notice address is too long'),
					]),
					new Url(false, ['http', 'https'], $this->l10n->t('The given legal notice address is not a valid URL')),
				]);
				break;
			case 'privacyUrl':
				$violations = $this->validator->validate($value, [
					new Length([
						'max' => 500,
						'maxMessage' => $this->l10n->t('The given privacy policy address is too long'),
					]),
					new Url(false, ['http', 'https'], $this->l10n->t('The given privacy policy address is not a valid URL')),
				]);
				break;
			case 'slogan':
				$violations = $this->validator->validate($value, [
					new Length([
						'max' => 500,
						'maxMessage' => $this->l10n->t('The given slogan is too long'),
					])
				]);
				break;
			case 'color':
				$violations = $this->validator->validate($value, [
					new CssColor($this->l10n->t('The given color is invalid')),
				]);
				break;
		}
		if (count($violations) > 0) {
			return new DataResponse([
				'data' => [
					'message' => implode('. ', array_map(fn (Violation $violation) => $violation->getMessage(), $violations)) . '.',
				],
				'status' => 'error'
			], Http::STATUS_BAD_REQUEST);
		}

		$this->themingDefaults->set($setting, $value);

		// reprocess server scss for preview
		$cssCached = $this->scssCacher->process(\OC::$SERVERROOT, 'core/css/css-variables.scss', 'core');

		return new DataResponse(
			[
				'data' =>
					[
						'message' => $this->l10n->t('Saved'),
						'serverCssUrl' => $this->urlGenerator->linkTo('', $this->scssCacher->getCachedSCSS('core', '/core/css/css-variables.scss'))
					],
				'status' => 'success'
			]
		);
	}

	/**
	 * @AuthorizedAdminSetting(settings=OCA\Theming\Settings\Admin)
	 * @return DataResponse
	 * @throws NotPermittedException
	 */
	public function uploadImage(): DataResponse {
		$key = $this->request->getParam('key');
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
		$cssCached = $this->scssCacher->process(\OC::$SERVERROOT, 'core/css/css-variables.scss', 'core');

		return new DataResponse(
			[
				'data' =>
					[
						'name' => $name,
						'url' => $this->imageManager->getImageUrl($key),
						'message' => $this->l10n->t('Saved'),
						'serverCssUrl' => $this->urlGenerator->linkTo('', $this->scssCacher->getCachedSCSS('core', '/core/css/css-variables.scss'))
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
		// reprocess server scss for preview
		$cssCached = $this->scssCacher->process(\OC::$SERVERROOT, 'core/css/css-variables.scss', 'core');

		return new DataResponse(
			[
				'data' =>
					[
						'value' => $value,
						'message' => $this->l10n->t('Saved'),
						'serverCssUrl' => $this->urlGenerator->linkTo('', $this->scssCacher->getCachedSCSS('core', '/core/css/css-variables.scss'))
					],
				'status' => 'success'
			]
		);
	}

	/**
	 * @PublicPage
	 * @NoCSRFRequired
	 *
	 * @param string $key
	 * @param bool $useSvg
	 * @return FileDisplayResponse|NotFoundResponse
	 * @throws NotPermittedException
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
	 *
	 * @return FileDisplayResponse|NotFoundResponse
	 * @throws NotPermittedException
	 * @throws \Exception
	 * @throws \OCP\App\AppPathNotFoundException
	 */
	public function getStylesheet() {
		$appPath = $this->appManager->getAppPath('theming');

		/* SCSSCacher is required here
		 * We cannot rely on automatic caching done by \OC_Util::addStyle,
		 * since we need to add the cacheBuster value to the url
		 */
		$cssCached = $this->scssCacher->process($appPath, 'css/theming.scss', 'theming');
		if (!$cssCached) {
			return new NotFoundResponse();
		}

		try {
			$cssFile = $this->scssCacher->getCachedCSS('theming', 'theming.css');
			$response = new FileDisplayResponse($cssFile, Http::STATUS_OK, ['Content-Type' => 'text/css']);
			$response->cacheFor(86400);
			return $response;
		} catch (NotFoundException $e) {
			return new NotFoundResponse();
		}
	}

	/**
	 * @NoCSRFRequired
	 * @PublicPage
	 *
	 * @return Http\JSONResponse
	 */
	public function getManifest($app) {
		$cacheBusterValue = $this->config->getAppValue('theming', 'cachebuster', '0');
		if ($app === 'core' || $app === 'settings') {
			$name = $this->themingDefaults->getName();
			$shortName = $this->themingDefaults->getName();
			$startUrl = $this->urlGenerator->getBaseUrl();
			$description = $this->themingDefaults->getSlogan();
		} else {
			$info = $this->appManager->getAppInfo($app, false, $this->l10n->getLanguageCode());
			$name = $info['name'] . ' - ' . $this->themingDefaults->getName();
			$shortName = $info['name'];
			if (strpos($this->request->getRequestUri(), '/index.php/') !== false) {
				$startUrl = $this->urlGenerator->getBaseUrl() . '/index.php/apps/' . $app . '/';
			} else {
				$startUrl = $this->urlGenerator->getBaseUrl() . '/apps/' . $app . '/';
			}
			$description = $info['summary'] ?? '';
		}
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
		$response = new Http\JSONResponse($responseJS);
		$response->cacheFor(3600);
		return $response;
	}
}
