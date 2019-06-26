<?php
declare (strict_types = 1);
/**
 * @copyright Copyright (c) 2018, John Molakvoæ (skjnldsv@protonmail.com)
 *
 * @author John Molakvoæ (skjnldsv) <skjnldsv@protonmail.com>
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

namespace OC\Core\Controller;

use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataDisplayResponse;
use OCP\AppFramework\Http\NotFoundResponse;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\Files\NotFoundException;
use OCP\App\IAppManager;
use OCP\IRequest;

class SvgController extends Controller {

	/** @var string */
	protected $serverRoot;

	/** @var ITimeFactory */
	protected $timeFactory;

	/** @var IAppManager */
	protected $appManager;

	public function __construct(string $appName,
								IRequest $request,
								ITimeFactory $timeFactory,
								IAppManager $appManager) {
		parent::__construct($appName, $request);

		$this->serverRoot  = \OC::$SERVERROOT;
		$this->timeFactory = $timeFactory;
		$this->appManager = $appManager;
	}

	/**
	 * @PublicPage
	 * @NoCSRFRequired
	 *
	 * Generate svg from filename with the requested color
	 *
	 * @param string $folder
	 * @param string $fileName
	 * @param string $color
	 * @return DataDisplayResponse|NotFoundResponse
	 */
	public function getSvgFromCore(string $folder, string $fileName, string $color = 'ffffff') {
		$path = $this->serverRoot . "/core/img/$folder/$fileName.svg";
		return $this->getSvg($path, $color, $fileName);
	}

	/**
	 * @PublicPage
	 * @NoCSRFRequired
	 *
	 * Generate svg from filename with the requested color
	 *
	 * @param string $app
	 * @param string $fileName
	 * @param string $color
	 * @return DataDisplayResponse|NotFoundResponse
	 */
	public function getSvgFromApp(string $app, string $fileName, string $color = 'ffffff') {

		if ($app === 'settings') {
			$path = $this->serverRoot . "/settings/img/$fileName.svg";
			return $this->getSvg($path, $color, $fileName);
		}

		// e.g /var/www/html/custom_apps/contacts
		// or outside root /var/www/apps/files
		$appRootPath = $this->appManager->getAppPath($app);

		if (!$appRootPath) {
			return new NotFoundResponse();
		}
		$path = $appRootPath ."/img/$fileName.svg";
		return $this->getSvg($path, $color, $fileName);
	}


	/**
	 * Generate svg from filename with the requested color
	 *
	 * @param string $path
	 * @param string $color
	 * @param string $fileName
	 * @return DataDisplayResponse|NotFoundResponse
	 */
	private function getSvg(string $path, string $color, string $fileName) {
		if (!file_exists($path)) {
			return new NotFoundResponse();
		}

		$svg = file_get_contents($path);

		if (is_null($svg)) {
			return new NotFoundResponse();
		}

		if (!preg_match('/^[0-9a-f]{3,6}$/i', $color)) {
			// Prevent not-sane colors from being written into the SVG
			$color = '000';
		}

		// add fill (fill is not present on black elements)
		$fillRe = '/<((circle|rect|path)((?!fill)[a-z0-9 =".\-#():;])+)\/>/mi';

		$svg = preg_replace($fillRe, '<$1 fill="#' . $color . '"/>', $svg);

		// replace any fill or stroke colors
		$svg = preg_replace('/stroke="#([a-z0-9]{3,6})"/mi', 'stroke="#' . $color . '"', $svg);
		$svg = preg_replace('/fill="#([a-z0-9]{3,6})"/mi', 'fill="#' . $color . '"', $svg);

		$response = new DataDisplayResponse($svg, Http::STATUS_OK, ['Content-Type' => 'image/svg+xml']);

		// Set cache control
		$ttl = 31536000;
		$response->cacheFor($ttl);
		$response->addHeader('Content-Disposition', 'inline; filename="' . $fileName . '.svg"');
		$expires = new \DateTime();
		$expires->setTimestamp($this->timeFactory->getTime());
		$expires->add(new \DateInterval('PT' . $ttl . 'S'));
		$response->addHeader('Expires', $expires->format(\DateTime::RFC1123));
		$response->addHeader('Pragma', 'cache');

		return $response;
	}
}
