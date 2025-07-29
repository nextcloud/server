<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Theming\Service;

use InvalidArgumentException;
use OC\User\NoUserException;
use OCA\Theming\AppInfo\Application;
use OCP\Files\File;
use OCP\Files\IAppData;
use OCP\Files\IRootFolder;
use OCP\Files\NotFoundException;
use OCP\Files\NotPermittedException;
use OCP\Files\SimpleFS\ISimpleFile;
use OCP\Files\SimpleFS\ISimpleFolder;
use OCP\IAppConfig;
use OCP\IConfig;
use OCP\Image;
use OCP\Lock\LockedException;
use OCP\PreConditionNotMetException;
use RuntimeException;

class BackgroundService {
	public const DEFAULT_COLOR = '#00679e';
	public const DEFAULT_BACKGROUND_COLOR = '#00679e';

	/**
	 * One of our shipped background images is used
	 */
	public const BACKGROUND_SHIPPED = 'shipped';
	/**
	 * A custom background image is used
	 */
	public const BACKGROUND_CUSTOM = 'custom';
	/**
	 * The default background image is used
	 */
	public const BACKGROUND_DEFAULT = 'default';
	/**
	 * Just a background color is used
	 */
	public const BACKGROUND_COLOR = 'color';

	public const DEFAULT_BACKGROUND_IMAGE = 'jenna-kim-the-globe.webp';

	/**
	 * 'attribution': Name, artist and license
	 * 'description': Alternative text
	 * 'attribution_url': URL for attribution
	 * 'background_color': Cached mean color of the top part to calculate app menu colors and use as fallback
	 * 'primary_color': Recommended primary color for this theme / image
	 */
	public const SHIPPED_BACKGROUNDS = [
		'jenna-kim-the-globe.webp' => [
			'attribution' => 'Globe (Jenna Kim - Nextcloud GmbH, CC-BY-SA-4.0)',
			'description' => 'Background picture of white clouds on in front of a blue sky',
			'attribution_url' => 'https://nextcloud.com/trademarks/',
			'dark_variant' => 'jenna-kim-the-globe-dark.webp',
			'background_color' => self::DEFAULT_BACKGROUND_COLOR,
			'primary_color' => self::DEFAULT_COLOR,
		],
		'kamil-porembinski-clouds.jpg' => [
			'attribution' => 'Clouds (Kamil Porembiński, CC BY-SA)',
			'description' => 'Background picture of white clouds on in front of a blue sky',
			'attribution_url' => 'https://www.flickr.com/photos/paszczak000/8715851521/',
			'background_color' => self::DEFAULT_BACKGROUND_COLOR,
			'primary_color' => self::DEFAULT_COLOR,
		],
		'hannah-maclean-soft-floral.jpg' => [
			'attribution' => 'Soft floral (Hannah MacLean, CC0)',
			'description' => 'Abstract background picture in yellow and white color whith a flower on it',
			'attribution_url' => 'https://stocksnap.io/photo/soft-floral-XOYWCCW5PA',
			'background_color' => '#e4d2c1',
			'primary_color' => '#9f652f',
		],
		'ted-moravec-morning-fog.jpg' => [
			'attribution' => 'Morning fog (Ted Moravec, Public Domain)',
			'description' => 'Background picture of a forest shrouded in fog',
			'attribution_url' => 'https://flickr.com/photos/tmoravec/52392410261',
			'background_color' => '#f6f7f6',
			'primary_color' => '#114c3b',
		],
		'stefanus-martanto-setyo-husodo-underwater-ocean.jpg' => [
			'attribution' => 'Underwater ocean (Stefanus Martanto Setyo Husodo, CC0)',
			'description' => 'Background picture of an underwater ocean',
			'attribution_url' => 'https://stocksnap.io/photo/underwater-ocean-TJA9LBH4WS',
			'background_color' => '#003351',
			'primary_color' => '#04577e',
		],
		'zoltan-voros-rhythm-and-blues.jpg' => [
			'attribution' => 'Rhythm and blues (Zoltán Vörös, CC BY)',
			'description' => 'Abstract background picture of sand dunes during night',
			'attribution_url' => 'https://flickr.com/photos/v923z/51634409289/',
			'background_color' => '#1c2437',
			'primary_color' => '#1c243c',
		],
		'anatoly-mikhaltsov-butterfly-wing-scale.jpg' => [
			'attribution' => 'Butterfly wing scale (Anatoly Mikhaltsov, CC BY-SA)',
			'description' => 'Background picture of a red-ish butterfly wing under microscope',
			'attribution_url' => 'https://commons.wikimedia.org/wiki/File:%D0%A7%D0%B5%D1%88%D1%83%D0%B9%D0%BA%D0%B8_%D0%BA%D1%80%D1%8B%D0%BB%D0%B0_%D0%B1%D0%B0%D0%B1%D0%BE%D1%87%D0%BA%D0%B8.jpg',
			'background_color' => '#652e11',
			'primary_color' => '#a53c17',
		],
		'bernie-cetonia-aurata-take-off-composition.jpg' => [
			'attribution' => 'Cetonia aurata take off composition (Bernie, Public Domain)',
			'description' => 'Montage of a cetonia aurata bug that takes off with white background',
			'attribution_url' => 'https://commons.wikimedia.org/wiki/File:Cetonia_aurata_take_off_composition_05172009.jpg',
			'background_color' => '#dee0d3',
			'primary_color' => '#56633d',
		],
		'dejan-krsmanovic-ribbed-red-metal.jpg' => [
			'attribution' => 'Ribbed red metal (Dejan Krsmanovic, CC BY)',
			'description' => 'Abstract background picture of red ribbed metal with two horizontal white elements on top of it',
			'attribution_url' => 'https://www.flickr.com/photos/dejankrsmanovic/42971456774/',
			'background_color' => '#9b171c',
			'primary_color' => '#9c4236',
		],
		'eduardo-neves-pedra-azul.jpg' => [
			'attribution' => 'Pedra azul milky way (Eduardo Neves, CC BY-SA)',
			'description' => 'Background picture of the milky way during night with a mountain in front of it',
			'attribution_url' => 'https://commons.wikimedia.org/wiki/File:Pedra_Azul_Milky_Way.jpg',
			'background_color' => '#1d242d',
			'primary_color' => '#4f6071',
		],
		'european-space-agency-barents-bloom.jpg' => [
			'attribution' => 'Barents bloom (European Space Agency, CC BY-SA)',
			'description' => 'Abstract background picture of blooming barents in blue and green colors',
			'attribution_url' => 'https://www.esa.int/ESA_Multimedia/Images/2016/08/Barents_bloom',
			'background_color' => '#1c383d',
			'primary_color' => '#396475',
		],
		'hannes-fritz-flippity-floppity.jpg' => [
			'attribution' => 'Flippity floppity (Hannes Fritz, CC BY-SA)',
			'description' => 'Abstract background picture of many pairs of flip flops hanging on a wall in multiple colors',
			'attribution_url' => 'http://hannes.photos/flippity-floppity',
			'background_color' => '#5b2d53',
			'primary_color' => '#98415a',
		],
		'hannes-fritz-roulette.jpg' => [
			'attribution' => 'Roulette (Hannes Fritz, CC BY-SA)',
			'description' => 'Background picture of a rotating giant wheel during night',
			'attribution_url' => 'http://hannes.photos/roulette',
			'background_color' => '#000000',
			'primary_color' => '#845334',
		],
		'hannes-fritz-sea-spray.jpg' => [
			'attribution' => 'Sea spray (Hannes Fritz, CC BY-SA)',
			'description' => 'Background picture of a stone coast with fog and sea behind it',
			'attribution_url' => 'http://hannes.photos/sea-spray',
			'background_color' => '#333f47',
			'primary_color' => '#4f6071',
		],
		'bernard-spragg-new-zealand-fern.jpg' => [
			'attribution' => 'New zealand fern (Bernard Spragg, CC0)',
			'description' => 'Abstract background picture of fern leafes',
			'attribution_url' => 'https://commons.wikimedia.org/wiki/File:NZ_Fern.(Blechnum_chambersii)_(11263534936).jpg',
			'background_color' => '#0c3c03',
			'primary_color' => '#316b26',
		],
		'rawpixel-pink-tapioca-bubbles.jpg' => [
			'attribution' => 'Pink tapioca bubbles (Rawpixel, CC BY)',
			'description' => 'Abstract background picture of pink tapioca bubbles',
			'attribution_url' => 'https://www.flickr.com/photos/byrawpixel/27665140298/in/photostream/',
			'background_color' => '#c56e95',
			'primary_color' => '#7b4e7e',
		],
		'nasa-waxing-crescent-moon.jpg' => [
			'attribution' => 'Waxing crescent moon (NASA, Public Domain)',
			'description' => 'Background picture of glowing earth in foreground and moon in the background',
			'attribution_url' => 'https://www.nasa.gov/image-feature/a-waxing-crescent-moon',
			'background_color' => '#000002',
			'primary_color' => '#005ac1',
		],
		'tommy-chau-already.jpg' => [
			'attribution' => 'Cityscape (Tommy Chau, CC BY)',
			'description' => 'Background picture of a skyscraper city during night',
			'attribution_url' => 'https://www.flickr.com/photos/90975693@N05/16910999368',
			'background_color' => '#35229f',
			'primary_color' => '#6a2af4',
		],
		'tommy-chau-lion-rock-hill.jpg' => [
			'attribution' => 'Lion rock hill (Tommy Chau, CC BY)',
			'description' => 'Background picture of mountains during sunset or sunrise',
			'attribution_url' => 'https://www.flickr.com/photos/90975693@N05/17136440246',
			'background_color' => '#cb92b7',
			'primary_color' => '#7f4f70',
		],
		'lali-masriera-yellow-bricks.jpg' => [
			'attribution' => 'Yellow bricks (Lali Masriera, CC BY)',
			'description' => 'Background picture of yellow bricks with some yellow tubes',
			'attribution_url' => 'https://www.flickr.com/photos/visualpanic/3982464447',
			'background_color' => '#c78a19',
			'primary_color' => '#7f5700',
		],
	];

	public function __construct(
		private IRootFolder $rootFolder,
		private IAppData $appData,
		private IAppConfig $appConfig,
		private IConfig $config,
		private ?string $userId,
	) {
	}

	public function setDefaultBackground(?string $userId = null): void {
		$userId = $userId ?? $this->getUserId();

		$this->config->deleteUserValue($userId, Application::APP_ID, 'background_image');
		$this->config->deleteUserValue($userId, Application::APP_ID, 'background_color');
		$this->config->deleteUserValue($userId, Application::APP_ID, 'primary_color');
	}

	/**
	 * @param $path
	 * @throws NotFoundException
	 * @throws NotPermittedException
	 * @throws LockedException
	 * @throws PreConditionNotMetException
	 * @throws NoUserException
	 */
	public function setFileBackground(string $path, ?string $userId = null): void {
		$userId = $userId ?? $this->getUserId();
		$userFolder = $this->rootFolder->getUserFolder($userId);

		/** @var File $file */
		$file = $userFolder->get($path);
		$handle = $file->fopen('r');
		if ($handle === false) {
			throw new InvalidArgumentException('Invalid image file');
		}
		$this->getAppDataFolder()->newFile('background.jpg', $handle);

		$this->recalculateMeanColor();
	}

	public function recalculateMeanColor(?string $userId = null): void {
		$userId = $userId ?? $this->getUserId();

		$image = new Image();
		$handle = $this->getAppDataFolder($userId)->getFile('background.jpg')->read();
		if ($handle === false || $image->loadFromFileHandle($handle) === false) {
			throw new InvalidArgumentException('Invalid image file');
		}

		$meanColor = $this->calculateMeanColor($image);
		if ($meanColor !== false) {
			$this->setColorBackground($meanColor);
		}
		$this->config->setUserValue($userId, Application::APP_ID, 'background_image', self::BACKGROUND_CUSTOM);
	}

	/**
	 * Set background of user to a shipped background identified by the filename
	 * @param string $filename The shipped background filename
	 * @param null|string $userId The user to set - defaults to currently logged in user
	 * @throws RuntimeException If neither $userId is specified nor a user is logged in
	 * @throws InvalidArgumentException If the specified filename does not match any shipped background
	 */
	public function setShippedBackground(string $filename, ?string $userId = null): void {
		$userId = $userId ?? $this->getUserId();

		if (!array_key_exists($filename, self::SHIPPED_BACKGROUNDS)) {
			throw new InvalidArgumentException('The given file name is invalid');
		}
		$this->setColorBackground(self::SHIPPED_BACKGROUNDS[$filename]['background_color'], $userId);
		$this->config->setUserValue($userId, Application::APP_ID, 'background_image', $filename);
		$this->config->setUserValue($userId, Application::APP_ID, 'primary_color', self::SHIPPED_BACKGROUNDS[$filename]['primary_color']);
	}

	/**
	 * Set the background to color only
	 * @param string|null $userId The user to set the color - default to current logged-in user
	 */
	public function setColorBackground(string $color, ?string $userId = null): void {
		$userId = $userId ?? $this->getUserId();

		if (!preg_match('/^#([0-9a-f]{3}|[0-9a-f]{6})$/i', $color)) {
			throw new InvalidArgumentException('The given color is invalid');
		}
		$this->config->setUserValue($userId, Application::APP_ID, 'background_color', $color);
		$this->config->setUserValue($userId, Application::APP_ID, 'background_image', self::BACKGROUND_COLOR);
	}

	public function deleteBackgroundImage(?string $userId = null): void {
		$userId = $userId ?? $this->getUserId();
		$this->config->setUserValue($userId, Application::APP_ID, 'background_image', self::BACKGROUND_COLOR);
	}

	public function getBackground(?string $userId = null): ?ISimpleFile {
		$userId = $userId ?? $this->getUserId();
		$background = $this->config->getUserValue($userId, Application::APP_ID, 'background_image', self::BACKGROUND_DEFAULT);
		if ($background === self::BACKGROUND_CUSTOM) {
			try {
				return $this->getAppDataFolder()->getFile('background.jpg');
			} catch (NotFoundException|NotPermittedException $e) {
				return null;
			}
		}
		return null;
	}

	/**
	 * Called when a new global background (backgroundMime) is uploaded (admin setting)
	 * This sets all necessary app config values
	 * @param resource|string $path
	 * @return string|null The fallback background color - if any
	 */
	public function setGlobalBackground($path): ?string {
		$image = new Image();
		$handle = is_resource($path) ? $path : fopen($path, 'rb');

		if ($handle && $image->loadFromFileHandle($handle) !== false) {
			$meanColor = $this->calculateMeanColor($image);
			if ($meanColor !== false) {
				$this->appConfig->setValueString(Application::APP_ID, 'background_color', $meanColor);
				return $meanColor;
			}
		}
		return null;
	}

	/**
	 * Calculate mean color of an given image
	 * It only takes the upper part into account so that a matching text color can be derived for the app menu
	 */
	private function calculateMeanColor(Image $image): false|string {
		/**
		 * Small helper to ensure one channel is returned as 8byte hex
		 */
		function toHex(int $channel): string {
			$hex = dechex($channel);
			return match (strlen($hex)) {
				0 => '00',
				1 => '0' . $hex,
				2 => $hex,
				default => 'ff',
			};
		}

		$tempImage = new Image();

		// Crop to only analyze top bar
		$resource = $image->cropNew(0, 0, $image->width(), min(max(50, (int)($image->height() * 0.125)), $image->height()));
		if ($resource === false) {
			return false;
		}

		$tempImage->setResource($resource);
		if (!$tempImage->preciseResize(100, 7)) {
			return false;
		}

		$resource = $tempImage->resource();
		if ($resource === false) {
			return false;
		}

		$reds = [];
		$greens = [];
		$blues = [];
		for ($y = 0; $y < 7; $y++) {
			for ($x = 0; $x < 100; $x++) {
				$value = imagecolorat($resource, $x, $y);
				if ($value === false) {
					continue;
				}
				$reds[] = ($value >> 16) & 0xFF;
				$greens[] = ($value >> 8) & 0xFF;
				$blues[] = $value & 0xFF;
			}
		}
		$meanColor = '#' . toHex((int)(array_sum($reds) / count($reds)));
		$meanColor .= toHex((int)(array_sum($greens) / count($greens)));
		$meanColor .= toHex((int)(array_sum($blues) / count($blues)));
		return $meanColor;
	}

	/**
	 * Storing the data in appdata/theming/users/USERID
	 *
	 * @param string|null $userId The user to get the folder - default to current user
	 * @throws NotPermittedException
	 */
	private function getAppDataFolder(?string $userId = null): ISimpleFolder {
		$userId = $userId ?? $this->getUserId();

		try {
			$rootFolder = $this->appData->getFolder('users');
		} catch (NotFoundException) {
			$rootFolder = $this->appData->newFolder('users');
		}
		try {
			return $rootFolder->getFolder($userId);
		} catch (NotFoundException) {
			return $rootFolder->newFolder($userId);
		}
	}

	/**
	 * @throws RuntimeException Thrown if a method that needs a user is called without any logged-in user
	 */
	private function getUserId(): string {
		if ($this->userId === null) {
			throw new RuntimeException('No currently logged-in user');
		}
		return $this->userId;
	}
}
