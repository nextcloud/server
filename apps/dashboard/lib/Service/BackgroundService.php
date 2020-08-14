<?php
/**
 * @copyright Copyright (c) 2020 Julius Härtl <jus@bitgrid.net>
 *
 * @author Julius Härtl <jus@bitgrid.net>
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

declare(strict_types=1);


namespace OCA\Dashboard\Service;


use OCP\Files\File;
use OCP\Files\IAppData;
use OCP\Files\IRootFolder;
use OCP\Files\NotFoundException;
use OCP\Files\SimpleFS\ISimpleFile;
use OCP\IConfig;

class BackgroundService {

	const SHIPPED_BACKGROUNDS = [
		'anatoly-mikhaltsov-butterfly-wing-scale-5k.jpg',
		'bernie-cetonia-aurata-take-off-composition-8k.jpg',
		'eduardo-neves-pedra-azul-5k.jpg',
		'europeanspaceagency-baltic-blooms-6k.jpg',
		'europeanspaceagency-barents-bloom-2k.jpg',
		'europeanspaceagency-colourful-queensland-6k.jpg',
		'europeanspaceagency-namib-desert-6k.jpg',
		'europeanspaceagency-peruvian-andes-6k.jpg',
		'flickr-148302424@N05-36591009215.jpg',
		'flickr-andymag-26451722126.jpg',
		'flickr-angietrenz-24259359268.jpg',
		'flickr-coconut-cove-mixed-2k.jpg',
		'flickr-dejankrsmanovic-42971456774.jpg',
		'flickr-paszczak000-8715851521.jpg',
		'flickr-xomeox-sewage-3k.jpg',
		'flickr-zalexandra-7948897538.jpg',
		'hannes-fritz-flippity-floppity-4k.jpg',
		'hannes-fritz-parkour-4k.jpg',
		'hannes-fritz-roulette-4k.jpg',
		'hannes-fritz-sea-spray-6k.jpg',
		'insa-wulf-radial-4k.jpg',
		'microcosmos-lab-microcrystals-5k.jpg',
		'nasa-goddard-antarctica-melts-6k.jpg',
		'nasa-goddard-iceberg-greenland-4k.jpg',
		'pexels-snapwire-forest-4k-cc0.jpg',
		'yana-sichikova-sergey-ovachev-stone-flower-2k.jpg',
	];

	public function __construct(IRootFolder $rootFolder, IAppData $appData, IConfig $config, $userId) {
		if ($userId === null) {
			return;
		}
		$this->userFolder = $rootFolder->getUserFolder($userId);
		try {
			$this->dashboardUserFolder = $appData->getFolder($userId);
		} catch (NotFoundException $e) {
			$this->dashboardUserFolder = $appData->newFolder($userId);
		}
		$this->config = $config;
		$this->userId = $userId;
	}

	public function setDefaultBackground() {
		$this->config->deleteUserValue($this->userId, 'dashboard', 'background');
	}

	public function setFileBackground($path) {
		$this->config->setUserValue($this->userId, 'dashboard', 'background', 'custom');
		$file = $this->userFolder->get($path);
		$newFile = $this->dashboardUserFolder->newFile('background.jpg', $file->fopen('r'));
	}

	public function setShippedBackground($fileName) {
		$this->config->setUserValue($this->userId, 'dashboard', 'background', $fileName);
	}

	public function setUrlBackground($url) {
		$this->config->setUserValue($this->userId, 'dashboard', 'background', 'custom');
		if (substr($url, 0, 1) === '/') {
			$url = \OC::$server->getURLGenerator()->getAbsoluteURL($url);
		}

		$client = \OC::$server->getHTTPClientService()->newClient();
		$response = $client->get($url);
		$content = $response->getBody();
		$newFile = $this->dashboardUserFolder->newFile('background.jpg', $content);
	}

	public function getBackground() {
		$background = $this->config->getUserValue($this->userId, 'dashboard', 'background', 'default');
		if ($background === 'custom') {
			try {
				return $this->dashboardUserFolder->getFile('background.jpg');
			} catch (NotFoundException $e) {
			}
		}
		return null;
	}

}
