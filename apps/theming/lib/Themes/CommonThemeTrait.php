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
use OCP\IL10N;

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
			'--color-primary-element-hover' => $this->util->mix($colorPrimaryElement, $colorMainBackground, 82),
			'--color-primary-element-text' => $this->util->invertTextColor($colorPrimaryElement) ? '#000000' : '#ffffff',
			// mostly used for disabled states
			'--color-primary-element-text-dark' => $this->util->darken($this->util->invertTextColor($colorPrimaryElement) ? '#000000' : '#ffffff', 6),

			// used for hover/focus states
			'--color-primary-element-light' => $colorPrimaryElementLight,
			'--color-primary-element-light-hover' => $this->util->mix($colorPrimaryElementLight, $colorMainText, 90),
			'--color-primary-element-light-text' => $this->util->mix($colorPrimaryElement, $this->util->invertTextColor($colorPrimaryElementLight) ? '#000000' : '#ffffff', -20),

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
				// --image-background is overridden by user theming if logged in
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
	protected function generateUserBackgroundVariables(IL10N $l10n): array {
		$user = $this->userSession->getUser();
		if ($user !== null
			&& !$this->themingDefaults->isUserThemingDisabled()
			&& $this->appManager->isEnabledForUser(Application::APP_ID)) {
			$adminBackgroundDeleted = $this->config->getAppValue(Application::APP_ID, 'backgroundMime', '') === 'backgroundColor';
			$backgroundImage = $this->config->getUserValue($user->getUID(), Application::APP_ID, 'background_image', BackgroundService::BACKGROUND_DEFAULT);
			$currentVersion = (int)$this->config->getUserValue($user->getUID(), Application::APP_ID, 'userCacheBuster', '0');
			$isPrimaryBright = $this->util->invertTextColor($this->themingDefaults->getColorPrimary());

			// The user removed the background
			if ($backgroundImage === BackgroundService::BACKGROUND_DISABLED) {
				return [
					// Might be defined already by admin theming, needs to be overridden
					'--image-background' => 'none',
					'--color-background-plain' => $this->themingDefaults->getColorPrimary(),
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

			// The user is using the default background and admin removed the background image
			if ($backgroundImage === BackgroundService::BACKGROUND_DEFAULT && $adminBackgroundDeleted) {
				return [
					// --image-background is not defined in this case
					'--color-background-plain' => $this->themingDefaults->getColorPrimary(),
					'--background-image-invert-if-bright' => $isPrimaryBright ? 'invert(100%)' : 'no',
				];
			}

			// The user picked a shipped background
			if (isset($this->getShippedBackgrounds($l10n)[$backgroundImage])) {
				return [
					'--image-background' => "url('" . $this->urlGenerator->linkTo(Application::APP_ID, "img/background/$backgroundImage") . "')",
					'--color-background-plain' => $this->themingDefaults->getColorPrimary(),
					'--background-image-invert-if-bright' => $this->getShippedBackgrounds($l10n)[backgroundImage]['theming'] ?? null === BackgroundService::THEMING_MODE_DARK ? 'invert(100%)' : 'no',
				];
			}
		}

		return [];

		public function getShippedBackgrounds(IL10N $l10n): array {
			return [
				'hannah-maclean-soft-floral.jpg' => [
					'attribution' => $l10n->t('Soft floral (Hannah MacLean, CC0)'),
					'description' => $l10n->t('Abstract picture in yellow and white color whith a flower on it'),
					'attribution_url' => 'https://stocksnap.io/photo/soft-floral-XOYWCCW5PA',
					'theming' => BackgroundService::THEMING_MODE_DARK,
					'primary_color' => '#9f652f',
				],
				'ted-moravec-morning-fog.jpg' => [
					'attribution' => $l10n->t('Morning fog (Ted Moravec, Public Domain)'),
					'description' => $l10n->t('Picture of a forest shrouded in fog'),
					'attribution_url' => 'https://flickr.com/photos/tmoravec/52392410261',
					'theming' => BackgroundService::THEMING_MODE_DARK,
					'primary_color' => '#114c3b',
				],
				'stefanus-martanto-setyo-husodo-underwater-ocean.jpg' => [
					'attribution' => $l10n->t('Underwater ocean (Stefanus Martanto Setyo Husodo, CC0)'),
					'description' => $l10n->t('Picture of an underwater ocean'),
					'attribution_url' => 'https://stocksnap.io/photo/underwater-ocean-TJA9LBH4WS',
					'primary_color' => '#04577e',
				],
				'zoltan-voros-rhythm-and-blues.jpg' => [
					'attribution' => $l10n->t('Rhythm and blues (Zoltán Vörös, CC BY)'),
					'description' => $l10n->t('Abstract picture of sand dunes during night'),
					'attribution_url' => 'https://flickr.com/photos/v923z/51634409289/',
					'primary_color' => '#1c243c',
				],
				'anatoly-mikhaltsov-butterfly-wing-scale.jpg' => [
					'attribution' => $l10n->t('Butterfly wing scale (Anatoly Mikhaltsov, CC BY-SA)'),
					'description' => $l10n->t('Picture of a red-ish butterfly wing under microscope'),
					'attribution_url' => 'https://commons.wikimedia.org/wiki/File:%D0%A7%D0%B5%D1%88%D1%83%D0%B9%D0%BA%D0%B8_%D0%BA%D1%80%D1%8B%D0%BB%D0%B0_%D0%B1%D0%B0%D0%B1%D0%BE%D1%87%D0%BA%D0%B8.jpg',
					'primary_color' => '#a53c17',
				],
				'bernie-cetonia-aurata-take-off-composition.jpg' => [
					'attribution' => $l10n->t('Cetonia aurata take off composition (Bernie, Public Domain)'),
					'description' => $l10n->t('Montage of a cetonia aurata bug that takes off with white background'),
					'attribution_url' => 'https://commons.wikimedia.org/wiki/File:Cetonia_aurata_take_off_composition_05172009.jpg',
					'theming' => BackgroundService::THEMING_MODE_DARK,
					'primary_color' => '#56633d',
				],
				'dejan-krsmanovic-ribbed-red-metal.jpg' => [
					'attribution' => $l10n->t('Ribbed red metal (Dejan Krsmanovic, CC BY)'),
					'description' => $l10n->t('Abstract picture of red ribbed metal with two horizontal white elements on top of it'),
					'attribution_url' => 'https://www.flickr.com/photos/dejankrsmanovic/42971456774/',
					'primary_color' => '#9c4236',
				],
				'eduardo-neves-pedra-azul.jpg' => [
					'attribution' => $l10n->t('Pedra azul milky way (Eduardo Neves, CC BY-SA)'),
					'description' => $l10n->t('Picture of the milky way during night with a mountain in front of it'),
					'attribution_url' => 'https://commons.wikimedia.org/wiki/File:Pedra_Azul_Milky_Way.jpg',
					'primary_color' => '#4f6071',
				],
				'european-space-agency-barents-bloom.jpg' => [
					'attribution' => $l10n->t('Barents bloom (European Space Agency, CC BY-SA)'),
					'description' => $l10n->t('Abstract picture of blooming barents in blue and green colors'),
					'attribution_url' => 'https://www.esa.int/ESA_Multimedia/Images/2016/08/Barents_bloom',
					'primary_color' => '#396475',
				],
				'hannes-fritz-flippity-floppity.jpg' => [
					'attribution' => $l10n->t('Flippity floppity (Hannes Fritz, CC BY-SA)'),
					'description' => $l10n->t('Abstract picture of many pairs of flip flops hanging on a wall in multiple colors'),
					'attribution_url' => 'http://hannes.photos/flippity-floppity',
					'primary_color' => '#98415a',
				],
				'hannes-fritz-roulette.jpg' => [
					'attribution' => $l10n->t('Roulette (Hannes Fritz, CC BY-SA)'),
					'description' => $l10n->t('Picture of a rotating giant wheel during night'),
					'attribution_url' => 'http://hannes.photos/roulette',
					'primary_color' => '#845334',
				],
				'hannes-fritz-sea-spray.jpg' => [
					'attribution' => $l10n->t('Sea spray (Hannes Fritz, CC BY-SA)'),
					'description' => $l10n->t('Picture of a stone coast with fog and sea behind it'),
					'attribution_url' => 'http://hannes.photos/sea-spray',
					'primary_color' => '#4f6071',
				],
				'kamil-porembinski-clouds.jpg' => [
					'attribution' => $l10n->t('Clouds (Kamil Porembiński, CC BY-SA)'),
					'description' => $l10n->t('Picture of white clouds on in front of a blue sky'),
					'attribution_url' => 'https://www.flickr.com/photos/paszczak000/8715851521/',
					'primary_color' => BackgroundService::DEFAULT_COLOR,
				],
				'bernard-spragg-new-zealand-fern.jpg' => [
					'attribution' => $l10n->t('New zealand fern (Bernard Spragg, CC0)'),
					'description' => $l10n->t('Abstract picture of fern leafes'),
					'attribution_url' => 'https://commons.wikimedia.org/wiki/File:NZ_Fern.(Blechnum_chambersii)_(11263534936).jpg',
					'primary_color' => '#316b26',
				],
				'rawpixel-pink-tapioca-bubbles.jpg' => [
					'attribution' => $l10n->t('Pink tapioca bubbles (Rawpixel, CC BY)'),
					'description' => $l10n->t('Abstract picture of pink tapioca bubbles'),
					'attribution_url' => 'https://www.flickr.com/photos/byrawpixel/27665140298/in/photostream/',
					'theming' => BackgroundService::THEMING_MODE_DARK,
					'primary_color' => '#7b4e7e',
				],
				'nasa-waxing-crescent-moon.jpg' => [
					'attribution' => $l10n->t('Waxing crescent moon (NASA, Public Domain)'),
					'description' => $l10n->t('Picture of glowing earth in foreground and moon in the background'),
					'attribution_url' => 'https://www.nasa.gov/image-feature/a-waxing-crescent-moon',
					'primary_color' => '#005ac1',
				],
				'tommy-chau-already.jpg' => [
					'attribution' => $l10n->t('Cityscape (Tommy Chau, CC BY)'),
					'description' => $l10n->t('Picture of a skyscraper city during night'),
					'attribution_url' => 'https://www.flickr.com/photos/90975693@N05/16910999368',
					'primary_color' => '#6a2af4',
				],
				'tommy-chau-lion-rock-hill.jpg' => [
					'attribution' => $l10n->t('Lion rock hill (Tommy Chau, CC BY)'),
					'description' => $l10n->t('Picture of mountains during sunset or sunrise'),
					'attribution_url' => 'https://www.flickr.com/photos/90975693@N05/17136440246',
					'theming' => BackgroundService::THEMING_MODE_DARK,
					'primary_color' => '#7f4f70',
				],
				'lali-masriera-yellow-bricks.jpg' => [
					'attribution' => $l10n->t('Yellow bricks (Lali Masriera, CC BY)'),
					'description' => $l10n->t('Picture of yellow bricks with some yellow tubes'),
					'attribution_url' => 'https://www.flickr.com/photos/visualpanic/3982464447',
					'theming' => BackgroundService::THEMING_MODE_DARK,
					'primary_color' => '#7f5700',
				],
			];
		}
	}
}
