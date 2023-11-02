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
use OCA\Theming\Themes\CommonThemeTrait;
use OCA\Theming\ThemingDefaults;
use OCP\Files\File;
use OCP\Files\IAppData;
use OCP\Files\IRootFolder;
use OCP\Files\NotFoundException;
use OCP\Files\NotPermittedException;
use OCP\Files\SimpleFS\ISimpleFile;
use OCP\Files\SimpleFS\ISimpleFolder;
use OCP\IConfig;
use OCP\IL10N;
use OCP\Lock\LockedException;
use OCP\PreConditionNotMetException;

class BackgroundService {
	// true when the background is bright and need dark icons
	public const THEMING_MODE_DARK = 'dark';
	public const DEFAULT_COLOR = '#0082c9';
	public const DEFAULT_ACCESSIBLE_COLOR = '#00679e';

	public const BACKGROUND_SHIPPED = 'shipped';
	public const BACKGROUND_CUSTOM = 'custom';
	public const BACKGROUND_DEFAULT = 'default';
	public const BACKGROUND_DISABLED = 'disabled';

	public const DEFAULT_BACKGROUND_IMAGE = 'kamil-porembinski-clouds.jpg';

	private IRootFolder $rootFolder;
	private IAppData $appData;
	private IConfig $config;
	private string $userId;
	private ThemingDefaults $themingDefaults;
	private CommonThemeTrait $commonThemeTrait;
	private IL10N $l10n;

	public function __construct(IRootFolder $rootFolder,
								IAppData $appData,
								IConfig $config,
								?string $userId,
								ThemingDefaults $themingDefaults,
								CommonThemeTrait $commonThemeTrait,
								IL10N $l10n) {
		if ($userId === null) {
			return;
		}

		$this->rootFolder = $rootFolder;
		$this->config = $config;
		$this->userId = $userId;
		$this->appData = $appData;
		$this->themingDefaults = $themingDefaults;
		$this->commonThemeTrait = $commonThemeTrait;
		$this->l10n = $l10n;
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
		if (!array_key_exists($fileName, $this->commonThemeTrait->getShippedBackgrounds($this->l10n))) {
			throw new InvalidArgumentException('The given file name is invalid');
		}
		$this->config->setUserValue($this->userId, Application::APP_ID, 'background_image', $fileName);
		$this->setColorBackground($this->commonThemeTrait->getShippedBackgrounds($this->l10n)[$fileName]['primary_color']);
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
