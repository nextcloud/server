<?php
declare(strict_types=1);
/**
 * @copyright Copyright (c) 2022 Joas Schilling <coding@schilljs.com>
 *
 * @author Joas Schilling <coding@schilljs.com>
 * @author John Molakvoæ <skjnldsv@protonmail.com>
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
namespace OCA\Theming\Themes;

use OCA\Theming\Util;
use OCA\Theming\ImageManager;
use OCA\Theming\AppInfo\Application;
use OCA\Theming\Service\BackgroundService;

trait CommonThemeTrait {
	public Util $util;

	/**
	 * Generate primary-related variables
	 * This is shared between multiple themes because colorMainBackground and colorMainText
	 * will change in between.
	 */
	protected function generatePrimaryVariables(string $colorMainBackground, string $colorMainText): array {
		$isBrightColor = $this->util->isBrightColor($colorMainBackground);
		$colorPrimaryElement = $this->util->elementColor($this->primaryColor, $isBrightColor);
		$colorPrimaryLight = $this->util->mix($colorPrimaryElement, $colorMainBackground, -80);
		$colorPrimaryElementLight = $this->util->mix($colorPrimaryElement, $colorMainBackground, -80);

		// primary related colours
		return [
			// invert filter if primary is too bright
			// to be used for legacy reasons only. Use inline
			// svg with proper css variable instead or material
			// design icons.
			// ⚠️ Using 'no' as a value to make sure we specify an
			// invalid one with no fallback. 'unset' could here fallback to some
			// other theme with media queries
			'--primary-invert-if-bright' => $this->util->invertTextColor($this->primaryColor) ? 'invert(100%)' : 'no',

			'--color-primary' => $this->primaryColor,
			'--color-primary-default' => $this->defaultPrimaryColor,
			'--color-primary-text' => $this->util->invertTextColor($this->primaryColor) ? '#000000' : '#ffffff',
			'--color-primary-hover' => $this->util->mix($this->primaryColor, $colorMainBackground, 60),
			'--color-primary-light' => $colorPrimaryLight,
			'--color-primary-light-text' => $this->util->mix($this->primaryColor, $this->util->invertTextColor($colorPrimaryLight) ? '#000000' : '#ffffff', -20),
			'--color-primary-light-hover' => $this->util->mix($colorPrimaryLight, $colorMainText, 90),

			// used for buttons, inputs...
			'--color-primary-element' => $colorPrimaryElement,
			'--color-primary-element-hover' => $this->util->mix($colorPrimaryElement, $colorMainBackground, 60),
			'--color-primary-element-text' => $this->util->invertTextColor($colorPrimaryElement) ? '#000000' : '#ffffff',

			// used for hover/focus states
			'--color-primary-element-light' => $colorPrimaryElementLight,
			'--color-primary-element-light-hover' => $this->util->mix($colorPrimaryElementLight, $colorMainText, 90),
			'--color-primary-element-light-text' => $this->util->mix($colorPrimaryElement, $this->util->invertTextColor($colorPrimaryElementLight) ? '#000000' : '#ffffff', -20),
			// mostly used for disabled states
			'--color-primary-element-text-dark' => $this->util->darken($this->util->invertTextColor($colorPrimaryElement) ? '#000000' : '#ffffff', 7),

			// to use like this: background-image: var(--gradient-primary-background);
			'--gradient-primary-background' => 'linear-gradient(40deg, var(--color-primary) 0%, var(--color-primary-hover) 100%)',
		];
	}

	/**
	 * Generate admin theming background-related variables
	 */
	protected function generateGlobalBackgroundVariables(): array {
		$backgroundDeleted = $this->config->getAppValue(Application::APP_ID, 'backgroundMime', '') === 'backgroundColor';
		$hasCustomLogoHeader = $this->util->isLogoThemed();
		$isDefaultPrimaryBright = $this->util->invertTextColor($this->defaultPrimaryColor);

		$variables = [];

		// Default last fallback values
		$variables['--image-background-default'] = "url('" . $this->themingDefaults->getBackground() . "')";
		$variables['--color-background-plain'] = $this->defaultPrimaryColor;

		// Register image variables only if custom-defined
		foreach (ImageManager::SUPPORTED_IMAGE_KEYS as $image) {
			if ($this->imageManager->hasImage($image)) {
				$imageUrl = $this->imageManager->getImageUrl($image);
				// --image-background is overridden by user theming
				$variables["--image-$image"] = "url('" . $imageUrl . "')";
			}
		}

		// If primary as background has been request or if we have a custom primary colour
		// let's not define the background image
		if ($backgroundDeleted) {
			$variables['--color-background-plain'] = $this->defaultPrimaryColor;
			$variables['--image-background-plain'] = 'yes';
			$variables['--image-background'] = 'no';
			// If no background image is set, we need to check against the shown primary colour
			$variables['--background-image-invert-if-bright'] = $isDefaultPrimaryBright ? 'invert(100%)' : 'no';
		}

		if ($hasCustomLogoHeader) {
			$variables['--image-logoheader-custom'] = 'true';
		}

		return $variables;
	}

	/**
	 * Generate user theming background-related variables
	 */
	protected function generateUserBackgroundVariables(): array {
		$user = $this->userSession->getUser();
		if ($user !== null
			&& !$this->themingDefaults->isUserThemingDisabled()
			&& $this->appManager->isEnabledForUser(Application::APP_ID)) {
			$backgroundImage = $this->config->getUserValue($user->getUID(), Application::APP_ID, 'background_image', BackgroundService::BACKGROUND_DEFAULT);
			$currentVersion = (int)$this->config->getUserValue($user->getUID(), Application::APP_ID, 'userCacheBuster', '0');
			$isPrimaryBright = $this->util->invertTextColor($this->primaryColor);

			// The user removed the background
			if ($backgroundImage === BackgroundService::BACKGROUND_DISABLED) {
				return [
					'--image-background' => 'no',
					'--color-background-plain' => $this->primaryColor,
					// If no background image is set, we need to check against the shown primary colour
					'--background-image-invert-if-bright' => $isPrimaryBright ? 'invert(100%)' : 'no',
				];
			}

			// The user uploaded a custom background
			if ($backgroundImage === BackgroundService::BACKGROUND_CUSTOM) {
				$cacheBuster = substr(sha1($user->getUID() . '_' . $currentVersion), 0, 8);
				return [
					'--image-background' => "url('" . $this->urlGenerator->linkToRouteAbsolute('theming.userTheme.getBackground') . "?v=$cacheBuster')",
					'--color-background-plain' => $this->themingDefaults->getColorPrimary(),
				];
			}

			// The user picked a shipped background
			if (isset(BackgroundService::SHIPPED_BACKGROUNDS[$backgroundImage])) {
				return [
					'--image-background' => "url('" . $this->urlGenerator->linkTo(Application::APP_ID, "img/background/$backgroundImage") . "')",
					'--color-background-plain' => $this->themingDefaults->getColorPrimary(),
					'--background-image-invert-if-bright' => BackgroundService::SHIPPED_BACKGROUNDS[$backgroundImage]['theming'] ?? null === BackgroundService::THEMING_MODE_DARK ? 'invert(100%)' : 'no',
				];
			}
		}

		return [];
	}
}
