<?php
/**
 * @copyright Copyright (c) 2016 Bjoern Schiessle <bjoern@schiessle.org>
 * @copyright Copyright (c) 2016 Lukas Reschke <lukas@statuscode.ch>
 *
 * @author Bjoern Schiessle <bjoern@schiessle.org>
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

namespace OCA\Theming\Controller;

use OCA\Theming\ThemingDefaults;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataDownloadResponse;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Http\StreamResponse;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\Files\IRootFolder;
use OCP\IConfig;
use OCP\IL10N;
use OCP\IRequest;
use OCA\Theming\Util;
use OCP\ITempManager;

/**
 * Class ThemingController
 *
 * handle ajax requests to update the theme
 *
 * @package OCA\Theming\Controller
 */
class ThemingController extends Controller {
	/** @var ThemingDefaults */
	private $template;
	/** @var Util */
	private $util;
	/** @var ITimeFactory */
	private $timeFactory;
	/** @var IL10N */
	private $l;
	/** @var IConfig */
	private $config;
	/** @var IRootFolder */
	private $rootFolder;
	/** @var ITempManager */
	private $tempManager;

	/**
	 * ThemingController constructor.
	 *
	 * @param string $appName
	 * @param IRequest $request
	 * @param IConfig $config
	 * @param ThemingDefaults $template
	 * @param Util $util
	 * @param ITimeFactory $timeFactory
	 * @param IL10N $l
	 * @param IRootFolder $rootFolder
	 * @param ITempManager $tempManager
	 */
	public function __construct(
		$appName,
		IRequest $request,
		IConfig $config,
		ThemingDefaults $template,
		Util $util,
		ITimeFactory $timeFactory,
		IL10N $l,
		IRootFolder $rootFolder,
		ITempManager $tempManager
	) {
		parent::__construct($appName, $request);

		$this->template = $template;
		$this->util = $util;
		$this->timeFactory = $timeFactory;
		$this->l = $l;
		$this->config = $config;
		$this->rootFolder = $rootFolder;
		$this->tempManager = $tempManager;
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
							'message' => $this->l->t('The given name is too long'),
						],
						'status' => 'error'
					]);
				}
				break;
			case 'url':
				if (strlen($value) > 500) {
					return new DataResponse([
						'data' => [
							'message' => $this->l->t('The given web address is too long'),
						],
						'status' => 'error'
					]);
				}
				break;
			case 'slogan':
				if (strlen($value) > 500) {
					return new DataResponse([
						'data' => [
							'message' => $this->l->t('The given slogan is too long'),
						],
						'status' => 'error'
					]);
				}
				break;
			case 'color':
				if (!preg_match('/^\#([0-9a-f]{3}|[0-9a-f]{6})$/i', $value)) {
					return new DataResponse([
						'data' => [
							'message' => $this->l->t('The given color is invalid'),
						],
						'status' => 'error'
					]);
				}
				break;
		}

		$this->template->set($setting, $value);
		return new DataResponse(
			[
				'data' =>
					[
						'message' => $this->l->t('Saved')
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
		$newLogo = $this->request->getUploadedFile('uploadlogo');
		$newBackgroundLogo = $this->request->getUploadedFile('upload-login-background');
		if (empty($newLogo) && empty($newBackgroundLogo)) {
			return new DataResponse(
				[
					'data' => [
						'message' => $this->l->t('No file uploaded')
					]
				],
				Http::STATUS_UNPROCESSABLE_ENTITY
			);
		}
		$name = '';
		if(!empty($newLogo)) {
			$target = $this->rootFolder->newFile('themedinstancelogo');
			stream_copy_to_stream(fopen($newLogo['tmp_name'], 'r'), $target->fopen('w'));
			$this->template->set('logoMime', $newLogo['type']);
			$name = $newLogo['name'];
		}
		if(!empty($newBackgroundLogo)) {
			$target = $this->rootFolder->newFile('themedbackgroundlogo');

			$image = @imagecreatefromstring(file_get_contents($newBackgroundLogo['tmp_name'], 'r'));
			if($image === false) {
				return new DataResponse(
					[
						'data' => [
							'message' => $this->l->t('Unsupported image type'),
						],
						'status' => 'failure',
					],
					Http::STATUS_UNPROCESSABLE_ENTITY
				);
			}

			// Optimize the image since some people may upload images that will be
			// either to big or are not progressive rendering.
			$tmpFile = $this->tempManager->getTemporaryFile();
			if(function_exists('imagescale')) {
				// FIXME: Once PHP 5.5.0 is a requirement the above check can be removed
				// Workaround for https://bugs.php.net/bug.php?id=65171
				$newHeight = imagesy($image)/(imagesx($image)/1920);
				$image = imagescale($image, 1920, $newHeight);
			}
			imageinterlace($image, 1);
			imagejpeg($image, $tmpFile, 75);
			imagedestroy($image);

			stream_copy_to_stream(fopen($tmpFile, 'r'), $target->fopen('w'));
			$this->template->set('backgroundMime', $newBackgroundLogo['type']);
			$name = $newBackgroundLogo['name'];
		}

		return new DataResponse(
			[
				'data' =>
					[
						'name' => $name,
						'message' => $this->l->t('Saved')
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
		$value = $this->template->undo($setting);
		return new DataResponse(
			[
				'data' =>
					[
						'value' => $value,
						'message' => $this->l->t('Saved')
					],
				'status' => 'success'
			]
		);
	}

	/**
	 * @PublicPage
	 * @NoCSRFRequired
	 *
	 * @return StreamResponse|DataResponse
	 */
	public function getLogo() {
		$pathToLogo = $this->config->getSystemValue('datadirectory', \OC::$SERVERROOT . '/data/') . '/themedinstancelogo';
		if(!file_exists($pathToLogo)) {
			return new DataResponse();
		}

		$response = new Http\StreamResponse($pathToLogo);
		$response->cacheFor(3600);
		$response->addHeader('Expires', date(\DateTime::RFC2822, $this->timeFactory->getTime()));
		$response->addHeader('Content-Disposition', 'attachment');
		$response->addHeader('Content-Type', $this->config->getAppValue($this->appName, 'logoMime', ''));
		$response->addHeader('Pragma', 'cache');
		return $response;
	}

	/**
	 * @PublicPage
	 * @NoCSRFRequired
	 *
	 * @return StreamResponse|DataResponse
	 */
	public function getLoginBackground() {
		$pathToLogo = $this->config->getSystemValue('datadirectory', \OC::$SERVERROOT . '/data/') . '/themedbackgroundlogo';
		if(!file_exists($pathToLogo)) {
			return new DataResponse();
		}

		$response = new StreamResponse($pathToLogo);
		$response->cacheFor(3600);
		$response->addHeader('Expires', date(\DateTime::RFC2822, $this->timeFactory->getTime()));
		$response->addHeader('Content-Disposition', 'attachment');
		$response->addHeader('Content-Type', $this->config->getAppValue($this->appName, 'backgroundMime', ''));
		$response->addHeader('Pragma', 'cache');
		return $response;
	}

	/**
	 * @NoCSRFRequired
	 * @PublicPage
	 *
	 * @return DataDownloadResponse
	 */
	public function getStylesheet() {
		$cacheBusterValue = $this->config->getAppValue('theming', 'cachebuster', '0');
		$responseCss = '';
		$color = $this->config->getAppValue($this->appName, 'color');
		$elementColor = $this->util->elementColor($color);
		if($color !== '') {
			$responseCss .= sprintf(
				'#body-user #header,#body-settings #header,#body-public #header,#body-login,.searchbox input[type="search"]:focus,.searchbox input[type="search"]:active,.searchbox input[type="search"]:valid {background-color: %s}' . "\n",
				$color
			);
			$responseCss .= sprintf('input[type="checkbox"].checkbox:checked:enabled:not(.checkbox--white) + label:before {' .
				'background-image:url(\'%s/core/img/actions/checkmark-white.svg\');' .
				'background-color: %s; background-position: center center; background-size:contain;' .
				'width:12px; height:12px; padding:0; margin:2px 6px 6px 2px; border-radius:1px;' .
				"}\n",
				\OC::$WEBROOT,
				$elementColor
			);
			$responseCss .= 'input[type="radio"].radio:checked:not(.radio--white):not(:disabled) + label:before {' .
				'background-image: url(\'data:image/svg+xml;base64,'.$this->util->generateRadioButton($elementColor).'\');' .
				"}\n";
			$responseCss .= '
				#firstrunwizard .firstrunwizard-header {
					background-color: ' . $color . ';
				}
				#firstrunwizard p a {
					color: ' . $color . ';
				}
				';
			$responseCss .= sprintf('.nc-theming-main-background {background-color: %s}' . "\n", $color);
			$responseCss .= sprintf('.nc-theming-main-text {color: %s}' . "\n", $color);

		}
		$logo = $this->config->getAppValue($this->appName, 'logoMime');
		if($logo !== '') {
			$responseCss .= sprintf(
				'#header .logo {' .
				'background-image: url(\'./logo?v='.$cacheBusterValue.'\');' .
				'background-size: contain;' .
				'}' . "\n" .
				'#header .logo-icon {' .
				'background-image: url(\'./logo?v='.$cacheBusterValue.'\');' .
				'background-size: contain;' .
				'}' . "\n" .
				'#firstrunwizard .firstrunwizard-header .logo {' .
				'background-image: url(\'./logo?v='.$cacheBusterValue.'\');' .
				'background-size: contain;' .
				'}' . "\n"
			);
		}
		$backgroundLogo = $this->config->getAppValue($this->appName, 'backgroundMime');
		if($backgroundLogo !== '') {
			$responseCss .= '#body-login {background-image: url(\'./loginbackground?v='.$cacheBusterValue.'\');}' . "\n";
			$responseCss .= '#firstrunwizard .firstrunwizard-header {' .
				'background-image: url(\'./loginbackground?v='.$cacheBusterValue.'\');' .
			'}' . "\n";
		}
		if($this->util->invertTextColor($color)) {
			$responseCss .= '#header .header-appname, #expandDisplayName { color: #000000; }' . "\n";
			$responseCss .= '#header .icon-caret { background-image: url(\'' . \OC::$WEBROOT . '/core/img/actions/caret-dark.svg\'); }' . "\n";
			$responseCss .= '.searchbox input[type="search"] { background: transparent url(\'' . \OC::$WEBROOT . '/core/img/actions/search.svg\') no-repeat 6px center; color: #000; }' . "\n";
			$responseCss .= '.searchbox input[type="search"]:focus,.searchbox input[type="search"]:active,.searchbox input[type="search"]:valid { color: #000; border: 1px solid rgba(0, 0, 0, .5); }' . "\n";
			$responseCss .= '.nc-theming-contrast {color: #000000}' . "\n";
		} else {
			$responseCss .= '.nc-theming-contrast {color: #ffffff}' . "\n";
		}

		$response = new DataDownloadResponse($responseCss, 'style', 'text/css');
		$response->addHeader('Expires', date(\DateTime::RFC2822, $this->timeFactory->getTime()));
		$response->addHeader('Pragma', 'cache');
		$response->cacheFor(3600);
		return $response;
	}
	/**
	 * @NoCSRFRequired
	 * @PublicPage
	 *
	 * @return DataDownloadResponse
	 */
	public function getJavascript() {
		$responseJS = '(function() {
	OCA.Theming = {
		name: ' . json_encode($this->template->getName()) . ',
		url: ' . json_encode($this->template->getBaseUrl()) . ',
		slogan: ' . json_encode($this->template->getSlogan()) . ',
		color: ' . json_encode($this->template->getMailHeaderColor()) . ',
		inverted: ' . json_encode($this->util->invertTextColor($this->template->getMailHeaderColor())) . ',
	};
})();';
		$response = new Http\DataDisplayResponse($responseJS);
		$response->addHeader('Content-type', 'text/javascript');
		$response->addHeader('Expires', date(\DateTime::RFC2822, $this->timeFactory->getTime()));
		$response->addHeader('Pragma', 'cache');
		$response->cacheFor(3600);
		return $response;
	}
}
