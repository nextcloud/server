<?php
/**
 * @copyright Copyright (c) 2016 Bjoern Schiessle <bjoern@schiessle.org>
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

namespace OCA\Theming\Controller;

use OCA\Theming\Template;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;
use OCP\Files\IRootFolder;
use OCP\IConfig;
use OCP\IL10N;
use OCP\IRequest;

/**
 * Class ThemingController
 *
 * handle ajax requests to update the theme
 *
 * @package OCA\Theming\Controller
 */
class ThemingController extends Controller {
	/** @var Template */
	private $template;
	/** @var IL10N */
	private $l;
	/** @var IConfig */
	private $config;
	/** @var IRootFolder */
	private $rootFolder;

	/**
	 * ThemingController constructor.
	 *
	 * @param string $appName
	 * @param IRequest $request
	 * @param IConfig $config
	 * @param Template $template
	 * @param IL10N $l
	 * @param IRootFolder $rootFolder
	 */
	public function __construct(
		$appName,
		IRequest $request,
		IConfig $config,
		Template $template,
		IL10N $l,
		IRootFolder $rootFolder
	) {
		parent::__construct($appName, $request);

		$this->template = $template;
		$this->l = $l;
		$this->config = $config;
		$this->rootFolder = $rootFolder;
	}

	/**
	 * @param string $setting
	 * @param string $value
	 * @return DataResponse
	 * @internal param string $color
	 */
	public function updateStylesheet($setting, $value) {
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
				Http::STATUS_UNPROCESSABLE_ENTITY);
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
			stream_copy_to_stream(fopen($newBackgroundLogo['tmp_name'], 'r'), $target->fopen('w'));
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
	 * @return Http\StreamResponse
	 */
	public function getLogo() {
		$pathToLogo = $this->config->getSystemValue('datadirectory', \OC::$SERVERROOT . '/data/') . '/themedinstancelogo';
		if(!file_exists($pathToLogo)) {
			return new DataResponse();
		}

		\OC_Response::setExpiresHeader(gmdate('D, d M Y H:i:s', time() + (60*60*24*45)) . ' GMT');
		\OC_Response::enableCaching();
		$response = new Http\StreamResponse($pathToLogo);
		$response->cacheFor(3600);
		$response->addHeader('Content-Disposition', 'attachment');
		$response->addHeader('Content-Type', $this->config->getAppValue($this->appName, 'logoMime', ''));
		return $response;
	}

	/**
	 * @PublicPage
	 * @NoCSRFRequired
	 *
	 * @return Http\StreamResponse
	 */
	public function getLoginBackground() {
		$pathToLogo = $this->config->getSystemValue('datadirectory', \OC::$SERVERROOT . '/data/') . '/themedbackgroundlogo';
		if(!file_exists($pathToLogo)) {
			return new DataResponse();
		}

		\OC_Response::setExpiresHeader(gmdate('D, d M Y H:i:s', time() + (60*60*24*45)) . ' GMT');
		\OC_Response::enableCaching();
		$response = new Http\StreamResponse($pathToLogo);
		$response->cacheFor(3600);
		$response->addHeader('Content-Disposition', 'attachment');
		$response->addHeader('Content-Type', $this->config->getAppValue($this->appName, 'backgroundMime', ''));
		return $response;
	}

	/**
	 * @NoCSRFRequired
	 * @PublicPage
	 *
	 * @return Http\DataDownloadResponse
	 */
	public function getStylesheet() {
		$cacheBusterValue = $this->config->getAppValue('theming', 'cachebuster', '0');
		$responseCss = '';
		$color = $this->config->getAppValue($this->appName, 'color');
		if($color !== '') {
			$responseCss .= sprintf(
				'#body-user #header,#body-settings #header,#body-public #header,#body-login,.searchbox input[type="search"]:focus,.searchbox input[type="search"]:active,.searchbox input[type="search"]:valid {background-color: %s}',
				$color
			);
		}
		$logo = $this->config->getAppValue($this->appName, 'logoMime');
		if($logo !== '') {
			$responseCss .= sprintf('#header .logo {
				background-image: url(\'./logo?v='.$cacheBusterValue.'\');
				background-size: contain;
			}
			#header .logo-icon {
				background-image: url(\'./logo?v='.$cacheBusterValue.'\');
				background-size: contain;
			}'
			);
		}
		$backgroundLogo = $this->config->getAppValue($this->appName, 'backgroundMime');
		if($backgroundLogo !== '') {
			$responseCss .= '#body-login {
				background-image: url(\'./loginbackground?v='.$cacheBusterValue.'\');
			}';
		}

		\OC_Response::setExpiresHeader(gmdate('D, d M Y H:i:s', time() + (60*60*24*45)) . ' GMT');
		\OC_Response::enableCaching();
		$response = new Http\DataDownloadResponse($responseCss, 'style', 'text/css');
		$response->cacheFor(3600);
		return $response;
	}
}
