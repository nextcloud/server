<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2020 Julius Härtl <jus@bitgrid.net>
 *
 * @author Jan C. Borchardt <hey@jancborchardt.net>
 * @author Julius Härtl <jus@bitgrid.net>
 * @author Christopher Ng <chrng8@gmail.com>
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
namespace OCA\Theming\Service;

use InvalidArgumentException;
use OC\User\NoUserException;
use OCA\Theming\AppInfo\Application;
use OCA\Theming\ThemingDefaults;
use OCP\Files\File;
use OCP\Files\IAppData;
use OCP\Files\IRootFolder;
use OCP\Files\NotFoundException;
use OCP\Files\NotPermittedException;
use OCP\Files\SimpleFS\ISimpleFile;
use OCP\Files\SimpleFS\ISimpleFolder;
use OCP\IConfig;
use OCP\Lock\LockedException;
use OCP\PreConditionNotMetException;

class BackgroundService {
	// true when the background is bright and need dark icons
	public const THEMING_MODE_DARK = 'dark';
	public const DEFAULT_COLOR = '#0082c9';
	public const DEFAULT_ACCESSIBLE_COLOR = '#006aa3';

	public const BACKGROUND_SHIPPED = 'shipped';
	public const BACKGROUND_CUSTOM = 'custom';
	public const BACKGROUND_DEFAULT = 'default';
	public const BACKGROUND_DISABLED = 'disabled';

	public const DEFAULT_BACKGROUND_IMAGE = 'kamil-porembinski-clouds.jpg';
	public const SHIPPED_BACKGROUNDS = [
		'hannah-maclean-soft-floral.jpg' => [
			'attribution' => 'Soft floral (Hannah MacLean, CC0)',
			'attribution_url' => 'https://stocksnap.io/photo/soft-floral-XOYWCCW5PA',
			'theming' => self::THEMING_MODE_DARK,
			'primary_color' => '#9f652f',
		],
		'ted-moravec-morning-fog.jpg' => [
			'attribution' => 'Morning fog (Ted Moravec, Public Domain)',
			'attribution_url' => 'https://flickr.com/photos/tmoravec/52392410261',
			'theming' => self::THEMING_MODE_DARK,
			'primary_color' => '#114c3b',
		],
		'stefanus-martanto-setyo-husodo-underwater-ocean.jpg' => [
			'attribution' => 'Underwater ocean (Stefanus Martanto Setyo Husodo, CC0)',
			'attribution_url' => 'https://stocksnap.io/photo/underwater-ocean-TJA9LBH4WS',
			'primary_color' => '#04577e',
		],
		'zoltan-voros-rhythm-and-blues.jpg' => [
			'attribution' => 'Rhythm and blues (Zoltán Vörös, CC BY)',
			'attribution_url' => 'https://flickr.com/photos/v923z/51634409289/',
			'primary_color' => '#1c243c',
		],
		'anatoly-mikhaltsov-butterfly-wing-scale.jpg' => [
			'attribution' => 'Butterfly wing scale (Anatoly Mikhaltsov, CC BY-SA)',
			'attribution_url' => 'https://commons.wikimedia.org/wiki/File:%D0%A7%D0%B5%D1%88%D1%83%D0%B9%D0%BA%D0%B8_%D0%BA%D1%80%D1%8B%D0%BB%D0%B0_%D0%B1%D0%B0%D0%B1%D0%BE%D1%87%D0%BA%D0%B8.jpg',
			'primary_color' => '#a53c17',
		],
		'bernie-cetonia-aurata-take-off-composition.jpg' => [
			'attribution' => 'Cetonia aurata take off composition (Bernie, Public Domain)',
			'attribution_url' => 'https://commons.wikimedia.org/wiki/File:Cetonia_aurata_take_off_composition_05172009.jpg',
			'theming' => self::THEMING_MODE_DARK,
			'primary_color' => '#56633d',
		],
		'dejan-krsmanovic-ribbed-red-metal.jpg' => [
			'attribution' => 'Ribbed red metal (Dejan Krsmanovic, CC BY)',
			'attribution_url' => 'https://www.flickr.com/photos/dejankrsmanovic/42971456774/',
			'primary_color' => '#9c4236',
		],
		'eduardo-neves-pedra-azul.jpg' => [
			'attribution' => 'Pedra azul milky way (Eduardo Neves, CC BY-SA)',
			'attribution_url' => 'https://commons.wikimedia.org/wiki/File:Pedra_Azul_Milky_Way.jpg',
			'primary_color' => '#4f6071',
		],
		'european-space-agency-barents-bloom.jpg' => [
			'attribution' => 'Barents bloom (European Space Agency, CC BY-SA)',
			'attribution_url' => 'https://www.esa.int/ESA_Multimedia/Images/2016/08/Barents_bloom',
			'primary_color' => '#396475',
		],
		'hannes-fritz-flippity-floppity.jpg' => [
			'attribution' => 'Flippity floppity (Hannes Fritz, CC BY-SA)',
			'attribution_url' => 'http://hannes.photos/flippity-floppity',
			'primary_color' => '#98415a',
		],
		'hannes-fritz-roulette.jpg' => [
			'attribution' => 'Roulette (Hannes Fritz, CC BY-SA)',
			'attribution_url' => 'http://hannes.photos/roulette',
			'primary_color' => '#845334',
		],
		'hannes-fritz-sea-spray.jpg' => [
			'attribution' => 'Sea spray (Hannes Fritz, CC BY-SA)',
			'attribution_url' => 'http://hannes.photos/sea-spray',
			'primary_color' => '#4f6071',
		],
		'kamil-porembinski-clouds.jpg' => [
			'attribution' => 'Clouds (Kamil Porembiński, CC BY-SA)',
			'attribution_url' => 'https://www.flickr.com/photos/paszczak000/8715851521/',
			'primary_color' => self::DEFAULT_COLOR,
		],
		'bernard-spragg-new-zealand-fern.jpg' => [
			'attribution' => 'New zealand fern (Bernard Spragg, CC0)',
			'attribution_url' => 'https://commons.wikimedia.org/wiki/File:NZ_Fern.(Blechnum_chambersii)_(11263534936).jpg',
			'primary_color' => '#316b26',
		],
		'rawpixel-pink-tapioca-bubbles.jpg' => [
			'attribution' => 'Pink tapioca bubbles (Rawpixel, CC BY)',
			'attribution_url' => 'https://www.flickr.com/photos/byrawpixel/27665140298/in/photostream/',
			'theming' => self::THEMING_MODE_DARK,
			'primary_color' => '#7b4e7e',
		],
		'nasa-waxing-crescent-moon.jpg' => [
			'attribution' => 'Waxing crescent moon (NASA, Public Domain)',
			'attribution_url' => 'https://www.nasa.gov/image-feature/a-waxing-crescent-moon',
			'primary_color' => '#005ac1',
		],
		'tommy-chau-already.jpg' => [
			'attribution' => 'Cityscape (Tommy Chau, CC BY)',
			'attribution_url' => 'https://www.flickr.com/photos/90975693@N05/16910999368',
			'primary_color' => '#6a2af4',
		],
		'tommy-chau-lion-rock-hill.jpg' => [
			'attribution' => 'Lion rock hill (Tommy Chau, CC BY)',
			'attribution_url' => 'https://www.flickr.com/photos/90975693@N05/17136440246',
			'theming' => self::THEMING_MODE_DARK,
			'primary_color' => '#7f4f70',
		],
		'lali-masriera-yellow-bricks.jpg' => [
			'attribution' => 'Yellow bricks (Lali Masriera, CC BY)',
			'attribution_url' => 'https://www.flickr.com/photos/visualpanic/3982464447',
			'theming' => self::THEMING_MODE_DARK,
			'primary_color' => '#7f5700',
		],
	];

	private IRootFolder $rootFolder;
	private IAppData $appData;
	private IConfig $config;
	private string $userId;
	private ThemingDefaults $themingDefaults;

	public function __construct(IRootFolder $rootFolder,
								IAppData $appData,
								IConfig $config,
								?string $userId,
								ThemingDefaults $themingDefaults) {
		if ($userId === null) {
			return;
		}

		$this->rootFolder = $rootFolder;
		$this->config = $config;
		$this->userId = $userId;
		$this->appData = $appData;
		$this->themingDefaults = $themingDefaults;
	}

	public function setDefaultBackground(): void {
		$this->config->deleteUserValue($this->userId, Application::APP_ID, 'background_image');
		$this->config->deleteUserValue($this->userId, Application::APP_ID, 'background_color');
	}

	/**
	 * @param $path
	 * @throws NotFoundException
	 * @throws NotPermittedException
	 * @throws LockedException
	 * @throws PreConditionNotMetException
	 * @throws NoUserException
	 */
	public function setFileBackground($path): void {
		$this->config->setUserValue($this->userId, Application::APP_ID, 'background_image', self::BACKGROUND_CUSTOM);
		$userFolder = $this->rootFolder->getUserFolder($this->userId);

		/** @var File $file */
		$file = $userFolder->get($path);
		$image = new \OCP\Image();

		if ($image->loadFromFileHandle($file->fopen('r')) === false) {
			throw new InvalidArgumentException('Invalid image file');
		}

		$this->getAppDataFolder()->newFile('background.jpg', $file->fopen('r'));
	}

	public function setShippedBackground($fileName): void {
		if (!array_key_exists($fileName, self::SHIPPED_BACKGROUNDS)) {
			throw new InvalidArgumentException('The given file name is invalid');
		}
		$this->config->setUserValue($this->userId, Application::APP_ID, 'background_image', $fileName);
		$this->setColorBackground(self::SHIPPED_BACKGROUNDS[$fileName]['primary_color']);
	}

	public function setColorBackground(string $color): void {
		if (!preg_match('/^#([0-9a-f]{3}|[0-9a-f]{6})$/i', $color)) {
			throw new InvalidArgumentException('The given color is invalid');
		}
		$this->config->setUserValue($this->userId, Application::APP_ID, 'background_color', $color);
	}

	public function deleteBackgroundImage(): void {
		$this->config->setUserValue($this->userId, Application::APP_ID, 'background_image', self::BACKGROUND_DISABLED);
	}

	public function getBackground(): ?ISimpleFile {
		$background = $this->config->getUserValue($this->userId, Application::APP_ID, 'background_image', self::BACKGROUND_DEFAULT);
		if ($background === self::BACKGROUND_CUSTOM) {
			try {
				return $this->getAppDataFolder()->getFile('background.jpg');
			} catch (NotFoundException | NotPermittedException $e) {
				return null;
			}
		}
		return null;
	}

	/**
	 * Storing the data in appdata/theming/users/USERID
	 *
	 * @return ISimpleFolder
	 * @throws NotPermittedException
	 */
	private function getAppDataFolder(): ISimpleFolder {
		try {
			$rootFolder = $this->appData->getFolder('users');
		} catch (NotFoundException $e) {
			$rootFolder = $this->appData->newFolder('users');
		}
		try {
			return $rootFolder->getFolder($this->userId);
		} catch (NotFoundException $e) {
			return $rootFolder->newFolder($this->userId);
		}
	}
}
