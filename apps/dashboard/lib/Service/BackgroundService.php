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

use OCP\Files\IAppData;
use OCP\Files\IRootFolder;
use OCP\Files\NotFoundException;
use OCP\Files\SimpleFS\ISimpleFile;
use OCP\IConfig;

class BackgroundService {
	public const THEMING_MODE_DARK = 'dark';

	public const SHIPPED_BACKGROUNDS = [
		'anatoly-mikhaltsov-butterfly-wing-scale.jpg' => [
			'attribution' => 'Butterfly',
			'attribution_url' => 'https://',
		],
		'bernie-cetonia-aurata-take-off-composition.jpg' => [
			'attribution' => '',
			'attribution_url' => '',
			'theming' => self::THEMING_MODE_DARK,
		],
		'dejan-krsmanovic-ribbed-red-metal.jpg' => [
			'attribution' => '',
			'attribution_url' => '',
		],
		'eduardo-neves-pedra-azul.jpg' => [
			'attribution' => '',
			'attribution_url' => '',
		],
		'european-space-agency-barents-bloom.jpg' => [
			'attribution' => '',
			'attribution_url' => '',
		],
		'european-space-agency-namib-desert.jpg' => [
			'attribution' => '',
			'attribution_url' => '',
		],
		'hannes-fritz-flippity-floppity.jpg' => [
			'attribution' => '',
			'attribution_url' => '',
		],
		'hannes-fritz-roulette.jpg' => [
			'attribution' => '',
			'attribution_url' => '',
		],
		'hannes-fritz-sea-spray.jpg' => [
			'attribution' => '',
			'attribution_url' => '',
		],
		'kamil-porembinski-clouds.jpg' => [
			'attribution' => '',
			'attribution_url' => '',
		]
	];
	/**
	 * @var \OCP\Files\Folder
	 */
	private $userFolder;
	/**
	 * @var \OCP\Files\SimpleFS\ISimpleFolder
	 */
	private $dashboardUserFolder;
	/**
	 * @var IConfig
	 */
	private $config;
	private $userId;

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

	public function setDefaultBackground(): void {
		$this->config->deleteUserValue($this->userId, 'dashboard', 'background');
	}

	/**
	 * @param $path
	 * @throws NotFoundException
	 * @throws \OCP\Files\NotPermittedException
	 * @throws \OCP\PreConditionNotMetException
	 */
	public function setFileBackground($path): void {
		$this->config->setUserValue($this->userId, 'dashboard', 'background', 'custom');
		$file = $this->userFolder->get($path);
		$this->dashboardUserFolder->newFile('background.jpg', $file->fopen('r'));
	}

	public function setShippedBackground($fileName): void {
		if (!array_key_exists($fileName, self::SHIPPED_BACKGROUNDS)) {
			throw new \InvalidArgumentException('The given file name is invalid');
		}
		$this->config->setUserValue($this->userId, 'dashboard', 'background', $fileName);
	}

	public function setColorBackground(string $color): void {
		if (!preg_match('/^\#([0-9a-f]{3}|[0-9a-f]{6})$/i', $color)) {
			throw new \InvalidArgumentException('The given color is invalid');
		}
		$this->config->setUserValue($this->userId, 'dashboard', 'background', $color);
	}

	public function getBackground(): ?ISimpleFile {
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
