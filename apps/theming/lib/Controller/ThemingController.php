<?php
/**
 * @copyright Copyright (c) 2016 Bjoern Schiessle <bjoern@schiessle.org>
 * @copyright Copyright (c) 2016 Lukas Reschke <lukas@statuscode.ch>
 *
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
 * @author Bjoern Schiessle <bjoern@schiessle.org>
 * @author Daniel Calviño Sánchez <danxuliu@gmail.com>
 * @author Jan-Christoph Borchardt <hey@jancborchardt.net>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Julius Haertl <jus@bitgrid.net>
 * @author Julius Härtl <jus@bitgrid.net>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Robin Appelman <robin@icewind.nl>
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

namespace OCA\Theming\Controller;

use OC\Files\AppData\Factory;
use OC\Template\SCSSCacher;
use OCA\Theming\ThemingDefaults;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataDownloadResponse;
use OCP\AppFramework\Http\FileDisplayResponse;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Http\NotFoundResponse;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\Files\File;
use OCP\Files\IAppData;
use OCP\Files\NotFoundException;
use OCP\Files\NotPermittedException;
use OCP\IConfig;
use OCP\IL10N;
use OCP\ILogger;
use OCP\IRequest;
use OCA\Theming\Util;
use OCP\ITempManager;
use OCP\IURLGenerator;
use OCP\App\IAppManager;

/**
 * Class ThemingController
 *
 * handle ajax requests to update the theme
 *
 * @package OCA\Theming\Controller
 */
class ThemingController extends Controller {
	/** @var ThemingDefaults */
	private $themingDefaults;
	/** @var Util */
	private $util;
	/** @var ITimeFactory */
	private $timeFactory;
	/** @var IL10N */
	private $l10n;
	/** @var IConfig */
	private $config;
	/** @var ITempManager */
	private $tempManager;
	/** @var IAppData */
	private $appData;
	/** @var SCSSCacher */
	private $scssCacher;
	/** @var IURLGenerator */
	private $urlGenerator;
	/** @var IAppManager */
	private $appManager;

	/**
	 * ThemingController constructor.
	 *
	 * @param string $appName
	 * @param IRequest $request
	 * @param IConfig $config
	 * @param ThemingDefaults $themingDefaults
	 * @param Util $util
	 * @param ITimeFactory $timeFactory
	 * @param IL10N $l
	 * @param ITempManager $tempManager
	 * @param IAppData $appData
	 * @param SCSSCacher $scssCacher
	 * @param IURLGenerator $urlGenerator
	 * @param IAppManager $appManager
	 */
	public function __construct(
		$appName,
		IRequest $request,
		IConfig $config,
		ThemingDefaults $themingDefaults,
		Util $util,
		ITimeFactory $timeFactory,
		IL10N $l,
		ITempManager $tempManager,
		IAppData $appData,
		SCSSCacher $scssCacher,
		IURLGenerator $urlGenerator,
		IAppManager $appManager
	) {
		parent::__construct($appName, $request);

		$this->themingDefaults = $themingDefaults;
		$this->util = $util;
		$this->timeFactory = $timeFactory;
		$this->l10n = $l;
		$this->config = $config;
		$this->tempManager = $tempManager;
		$this->appData = $appData;
		$this->scssCacher = $scssCacher;
		$this->urlGenerator = $urlGenerator;
		$this->appManager = $appManager;
	}

	/**
	 * @param string $setting
	 * @param string $value
	 * @return DataResponse
	 * @internal param string $color
	 */
	public function updateStylesheet($setting, $value) {
		$value = trim($value);
		switch ($setting) {
			case 'name':
				if (strlen($value) > 250) {
					return new DataResponse([
						'data' => [
							'message' => $this->l10n->t('The given name is too long'),
						],
						'status' => 'error'
					]);
				}
				break;
			case 'url':
				if (strlen($value) > 500) {
					return new DataResponse([
						'data' => [
							'message' => $this->l10n->t('The given web address is too long'),
						],
						'status' => 'error'
					]);
				}
				break;
			case 'imprintUrl':
				if (strlen($value) > 500) {
					return new DataResponse([
						'data' => [
							'message' => $this->l10n->t('The given legal notice address is too long'),
						],
						'status' => 'error'
					]);
				}
				break;
			case 'privacyUrl':
				if (strlen($value) > 500) {
					return new DataResponse([
						'data' => [
							'message' => $this->l10n->t('The given privacy policy address is too long'),
						],
						'status' => 'error'
					]);
				}
				break;
			case 'slogan':
				if (strlen($value) > 500) {
					return new DataResponse([
						'data' => [
							'message' => $this->l10n->t('The given slogan is too long'),
						],
						'status' => 'error'
					]);
				}
				break;
			case 'color':
				if (!preg_match('/^\#([0-9a-f]{3}|[0-9a-f]{6})$/i', $value)) {
					return new DataResponse([
						'data' => [
							'message' => $this->l10n->t('The given color is invalid'),
						],
						'status' => 'error'
					]);
				}
				break;
		}

		$this->themingDefaults->set($setting, $value);

		// reprocess server scss for preview
		$cssCached = $this->scssCacher->process(\OC::$SERVERROOT, 'core/css/server.scss', 'core');

		return new DataResponse(
			[
				'data' =>
					[
						'message' => $this->l10n->t('Saved'),
						'serverCssUrl' => $this->urlGenerator->linkTo('', $this->scssCacher->getCachedSCSS('core', '/core/css/server.scss'))
					],
				'status' => 'success'
			]
		);
	}

	/**
	 * Update the logos and background image
	 *
	 * @return DataResponse
	 */
	public function updateLogo() {
		$backgroundColor = $this->request->getParam('backgroundColor', false);
		if($backgroundColor) {
			$this->themingDefaults->set('backgroundMime', 'backgroundColor');
			return new DataResponse(
				[
					'data' =>
						[
							'name' => 'backgroundColor',
							'message' => $this->l10n->t('Saved')
						],
					'status' => 'success'
				]
			);
		}
		$newLogo = $this->request->getUploadedFile('uploadlogo');
		$newBackgroundLogo = $this->request->getUploadedFile('upload-login-background');
		$error = null;
		$phpFileUploadErrors = [
			UPLOAD_ERR_OK => $this->l10n->t('There is no error, the file uploaded with success'),
			UPLOAD_ERR_INI_SIZE => $this->l10n->t('The uploaded file exceeds the upload_max_filesize directive in php.ini'),
			UPLOAD_ERR_FORM_SIZE => $this->l10n->t('The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form'),
			UPLOAD_ERR_PARTIAL => $this->l10n->t('The uploaded file was only partially uploaded'),
			UPLOAD_ERR_NO_FILE => $this->l10n->t('No file was uploaded'),
			UPLOAD_ERR_NO_TMP_DIR => $this->l10n->t('Missing a temporary folder'),
			UPLOAD_ERR_CANT_WRITE => $this->l10n->t('Failed to write file to disk.'),
			UPLOAD_ERR_EXTENSION => $this->l10n->t('A PHP extension stopped the file upload.'),
		];
		if (empty($newLogo) && empty($newBackgroundLogo)) {
			$error = $this->l10n->t('No file uploaded');
		}
		if (!empty($newLogo) && array_key_exists('error', $newLogo) && $newLogo['error'] !== UPLOAD_ERR_OK) {
			$error = $phpFileUploadErrors[$newLogo['error']];
		}
		if (!empty($newBackgroundLogo) && array_key_exists('error', $newBackgroundLogo) && $newBackgroundLogo['error'] !== UPLOAD_ERR_OK) {
			$error = $phpFileUploadErrors[$newBackgroundLogo['error']];
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

		$name = '';
		try {
			$folder = $this->appData->getFolder('images');
		} catch (NotFoundException $e) {
			$folder = $this->appData->newFolder('images');
		}

		if (!empty($newLogo)) {
			$target = $folder->newFile('logo');
			$supportedFormats = ['image/jpeg', 'image/png', 'image/gif', 'image/svg+xml', 'text/svg'];
			if (!in_array($newLogo['type'], $supportedFormats)) {
				return new DataResponse(
					[
						'data' => [
							'message' => $this->l10n->t('Unsupported image type'),
						],
						'status' => 'failure',
					],
					Http::STATUS_UNPROCESSABLE_ENTITY
				);
			}
			$target->putContent(file_get_contents($newLogo['tmp_name'], 'r'));
			$this->themingDefaults->set('logoMime', $newLogo['type']);
			$name = $newLogo['name'];
		}
		if (!empty($newBackgroundLogo)) {
			$target = $folder->newFile('background');
			$image = @imagecreatefromstring(file_get_contents($newBackgroundLogo['tmp_name'], 'r'));
			if ($image === false) {
				return new DataResponse(
					[
						'data' => [
							'message' => $this->l10n->t('Unsupported image type'),
						],
						'status' => 'failure',
					],
					Http::STATUS_UNPROCESSABLE_ENTITY
				);
			}

			// Optimize the image since some people may upload images that will be
			// either to big or are not progressive rendering.
			$tmpFile = $this->tempManager->getTemporaryFile();
			if (function_exists('imagescale')) {
				// FIXME: Once PHP 5.5.0 is a requirement the above check can be removed
				// Workaround for https://bugs.php.net/bug.php?id=65171
				$newHeight = imagesy($image) / (imagesx($image) / 1920);
				$image = imagescale($image, 1920, $newHeight);
			}
			imageinterlace($image, 1);
			imagejpeg($image, $tmpFile, 75);
			imagedestroy($image);

			$target->putContent(file_get_contents($tmpFile, 'r'));
			$this->themingDefaults->set('backgroundMime', $newBackgroundLogo['type']);
			$name = $newBackgroundLogo['name'];
		}

		return new DataResponse(
			[
				'data' =>
					[
						'name' => $name,
						'message' => $this->l10n->t('Saved')
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
	 */
	public function undo($setting) {
		$value = $this->themingDefaults->undo($setting);
		// reprocess server scss for preview
		$cssCached = $this->scssCacher->process(\OC::$SERVERROOT, 'core/css/server.scss', 'core');

		if($setting === 'logoMime') {
			try {
				$file = $this->appData->getFolder('images')->getFile('logo');
				$file->delete();
			} catch (NotFoundException $e) {
			} catch (NotPermittedException $e) {
			}
		}
		if($setting === 'backgroundMime') {
			try {
				$file = $this->appData->getFolder('images')->getFile('background');
				$file->delete();
			} catch (NotFoundException $e) {
			} catch (NotPermittedException $e) {
			}
		}

		return new DataResponse(
			[
				'data' =>
					[
						'value' => $value,
						'message' => $this->l10n->t('Saved'),
						'serverCssUrl' => $this->urlGenerator->linkTo('', $this->scssCacher->getCachedSCSS('core', '/core/css/server.scss'))
					],
				'status' => 'success'
			]
		);
	}

	/**
	 * @PublicPage
	 * @NoCSRFRequired
	 *
	 * @return FileDisplayResponse|NotFoundResponse
	 */
	public function getLogo() {
		try {
			/** @var File $file */
			$file = $this->appData->getFolder('images')->getFile('logo');
		} catch (NotFoundException $e) {
			return new NotFoundResponse();
		}

		$response = new FileDisplayResponse($file);
		$response->cacheFor(3600);
		$expires = new \DateTime();
		$expires->setTimestamp($this->timeFactory->getTime());
		$expires->add(new \DateInterval('PT24H'));
		$response->addHeader('Expires', $expires->format(\DateTime::RFC2822));
		$response->addHeader('Pragma', 'cache');
		$response->addHeader('Content-Type', $this->config->getAppValue($this->appName, 'logoMime', ''));
		return $response;
	}

	/**
	 * @PublicPage
	 * @NoCSRFRequired
	 *
	 * @return FileDisplayResponse|NotFoundResponse
	 */
	public function getLoginBackground() {
		try {
			/** @var File $file */
			$file = $this->appData->getFolder('images')->getFile('background');
		} catch (NotFoundException $e) {
			return new NotFoundResponse();
		}

		$response = new FileDisplayResponse($file);
		$response->cacheFor(3600);
		$expires = new \DateTime();
		$expires->setTimestamp($this->timeFactory->getTime());
		$expires->add(new \DateInterval('PT24H'));
		$response->addHeader('Expires', $expires->format(\DateTime::RFC2822));
		$response->addHeader('Pragma', 'cache');
		$response->addHeader('Content-Type', $this->config->getAppValue($this->appName, 'backgroundMime', ''));
		return $response;
	}

	/**
	 * @NoCSRFRequired
	 * @PublicPage
	 *
	 * @return FileDisplayResponse|NotFoundResponse
	 */
	public function getStylesheet() {
		$appPath = $this->appManager->getAppPath('theming');

		/* SCSSCacher is required here
		 * We cannot rely on automatic caching done by \OC_Util::addStyle,
		 * since we need to add the cacheBuster value to the url
		 */
		$cssCached = $this->scssCacher->process($appPath, 'css/theming.scss', 'theming');
		if(!$cssCached) {
			return new NotFoundResponse();
		}

		try {
			$cssFile = $this->scssCacher->getCachedCSS('theming', 'theming.css');
			$response = new FileDisplayResponse($cssFile, Http::STATUS_OK, ['Content-Type' => 'text/css']);
			$response->cacheFor(86400);
			$expires = new \DateTime();
			$expires->setTimestamp($this->timeFactory->getTime());
			$expires->add(new \DateInterval('PT24H'));
			$response->addHeader('Expires', $expires->format(\DateTime::RFC1123));
			$response->addHeader('Pragma', 'cache');
			return $response;
		} catch (NotFoundException $e) {
			return new NotFoundResponse();
		}
	}

	/**
	 * @NoCSRFRequired
	 * @PublicPage
	 *
	 * @return DataDownloadResponse
	 */
	public function getJavascript() {
		$cacheBusterValue = $this->config->getAppValue('theming', 'cachebuster', '0');
		$responseJS = '(function() {
	OCA.Theming = {
		name: ' . json_encode($this->themingDefaults->getName()) . ',
		url: ' . json_encode($this->themingDefaults->getBaseUrl()) . ',
		slogan: ' . json_encode($this->themingDefaults->getSlogan()) . ',
		color: ' . json_encode($this->themingDefaults->getColorPrimary()) . ',
		imprintUrl: ' . json_encode($this->themingDefaults->getImprintUrl()) . ',
		privacyUrl: ' . json_encode($this->themingDefaults->getPrivacyUrl()) . ',
		inverted: ' . json_encode($this->util->invertTextColor($this->themingDefaults->getColorPrimary())) . ',
		cacheBuster: ' . json_encode($cacheBusterValue) . '
	};
})();';
		$response = new DataDownloadResponse($responseJS, 'javascript', 'text/javascript');
		$response->addHeader('Expires', date(\DateTime::RFC2822, $this->timeFactory->getTime()));
		$response->addHeader('Pragma', 'cache');
		$response->cacheFor(3600);
		return $response;
	}

	/**
	 * @NoCSRFRequired
	 * @PublicPage
	 *
	 * @return Http\JSONResponse
	 */
	public function getManifest($app) {
		$cacheBusterValue = $this->config->getAppValue('theming', 'cachebuster', '0');
		$responseJS = [
			'name' => $this->themingDefaults->getName(),
			'start_url' => $this->urlGenerator->getBaseUrl(),
			'icons' =>
				[
					[
						'src' => $this->urlGenerator->linkToRoute('theming.Icon.getTouchIcon',
								['app' => $app]) . '?v=' . $cacheBusterValue,
						'type'=> 'image/png',
						'sizes'=> '128x128'
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
		$response->addHeader('Expires', date(\DateTime::RFC2822, $this->timeFactory->getTime()));
		$response->addHeader('Pragma', 'cache');
		$response->cacheFor(3600);
		return $response;
	}
}
