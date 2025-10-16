<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Theming;

use OCA\Theming\AppInfo\Application;
use OCA\Theming\Service\BackgroundService;
use OCP\App\AppPathNotFoundException;
use OCP\App\IAppManager;
use OCP\AppFramework\Services\IAppConfig;
use OCP\Files\NotFoundException;
use OCP\Files\SimpleFS\ISimpleFile;
use OCP\ICacheFactory;
use OCP\IConfig;
use OCP\IL10N;
use OCP\INavigationManager;
use OCP\IURLGenerator;
use OCP\IUserSession;
use OCP\ServerVersion;
use OCP\Theming\IDefaults;

class ThemingDefaults implements IDefaults {
	private ?\OC_Theme $theme = null;
	private ?string $defaultSlogan = null;

	private string $iTunesAppId;
	private string $iOSClientUrl;
	private string $AndroidClientUrl;
	private string $FDroidClientUrl;
	private string $defaultDocVersion;

	/**
	 * ThemingDefaults constructor.
	 */
	public function __construct(
		private IConfig $config,
		private IAppConfig $appConfig,
		private IL10N $l,
		private IUserSession $userSession,
		private IURLGenerator $urlGenerator,
		private ICacheFactory $cacheFactory,
		private Util $util,
		private ImageManager $imageManager,
		private IAppManager $appManager,
		private INavigationManager $navigationManager,
		private BackgroundService $backgroundService,
		ServerVersion $serverVersion,
	) {
		$themeName = $config->getSystemValueString('theme', '');
		if ($themeName === '') {
			if (is_dir(\OC::$SERVERROOT . '/themes/default')) {
				$themeName = 'default';
			}
		}
		$themePath = \OC::$SERVERROOT . '/themes/' . $themeName . '/defaults.php';
		if (file_exists($themePath)) {
			// prevent defaults.php from printing output
			ob_start();
			require_once $themePath;
			ob_end_clean();
			if (class_exists(\OC_Theme::class)) {
				$this->theme = new \OC_Theme();
			}
		}

		$this->iTunesAppId = $this->getFromLegacyTheme(
			'getiTunesAppId',
			$config->getSystemValueString('customclient_ios_appid', '1125420102')
		);
		$this->iOSClientUrl = $this->getFromLegacyTheme(
			'getiOSClientUrl',
			$config->getSystemValueString('customclient_ios', 'https://geo.itunes.apple.com/us/app/nextcloud/id1125420102?mt=8')
		);
		$this->AndroidClientUrl = $this->getFromLegacyTheme(
			'getAndroidClientUrl',
			$config->getSystemValueString('customclient_android', 'https://play.google.com/store/apps/details?id=com.nextcloud.client')
		);
		$this->FDroidClientUrl = $this->getFromLegacyTheme(
			'getFDroidClientUrl',
			$config->getSystemValueString('customclient_fdroid', 'https://f-droid.org/packages/com.nextcloud.client/')
		);
		$this->defaultDocVersion = (string)$serverVersion->getMajorVersion(); // used to generate doc links
	}

	private function getFromLegacyTheme(string $method, string $default): string {
		if (isset($this->theme) && method_exists($this->theme, $method)) {
			return $this->theme->$method();
		}
		return $default;
	}

	public function getName(): string {
		return strip_tags($this->appConfig->getAppValueString('name', $this->getFromLegacyTheme('getName', 'Nextcloud')));
	}

	public function getTitle(): string {
		return strip_tags($this->appConfig->getAppValueString('name', $this->getFromLegacyTheme('getTitle', 'Nextcloud')));
	}

	public function getEntity(): string {
		return strip_tags($this->appConfig->getAppValueString('name', $this->getFromLegacyTheme('getEntity', 'Nextcloud')));
	}

	public function getProductName(): string {
		return strip_tags($this->appConfig->getAppValueString('productName', $this->getFromLegacyTheme('getProductName', 'Nextcloud')));
	}

	public function getBaseUrl(): string {
		return $this->appConfig->getAppValueString('url', $this->getFromLegacyTheme('getBaseUrl', 'https://nextcloud.com'));
	}

	/**
	 * We pass a string and sanitizeHTML will return a string too in that case
	 * @psalm-suppress InvalidReturnStatement
	 * @psalm-suppress InvalidReturnType
	 */
	public function getSlogan(?string $lang = null): string {
		if ($this->appConfig->hasAppKey('slogan')) {
			return \OCP\Util::sanitizeHTML($this->appConfig->getAppValueString('slogan'));
		}
		if (isset($this->theme) && method_exists($this->theme, 'getSlogan')) {
			return $this->theme->getSlogan($lang);
		}
		if ($this->defaultSlogan === null) {
			$l10n = \OCP\Util::getL10N('lib', $lang);
			$this->defaultSlogan = $l10n->t('a safe home for all your data');
		}
		return $this->defaultSlogan;
	}

	public function getImprintUrl(): string {
		return $this->appConfig->getAppValueString('imprintUrl', '');
	}

	public function getPrivacyUrl(): string {
		return $this->appConfig->getAppValueString('privacyUrl', '');
	}

	public function getDocBaseUrl(): string {
		return $this->appConfig->getAppValueString('docBaseUrl', $this->getFromLegacyTheme('getDocBaseUrl', 'https://docs.nextcloud.com'));
	}

	public function getShortFooter(): string {
		$slogan = $this->getSlogan();
		$baseUrl = $this->getBaseUrl();
		$entity = $this->getEntity();
		$footer = '';

		if ($entity !== '') {
			if ($baseUrl !== '') {
				$footer = '<a href="' . $baseUrl . '" target="_blank"'
					. ' rel="noreferrer noopener" class="entity-name">' . $entity . '</a>';
			} else {
				$footer = '<span class="entity-name">' . $entity . '</span>';
			}
		}
		$footer .= ($slogan !== '' ? ' – ' . $slogan : '');

		$links = [
			[
				'text' => $this->l->t('Legal notice'),
				'url' => $this->getImprintUrl()
			],
			[
				'text' => $this->l->t('Privacy policy'),
				'url' => $this->getPrivacyUrl()
			],
		];

		$navigation = $this->navigationManager->getAll(INavigationManager::TYPE_GUEST);
		$guestNavigation = array_map(function ($nav) {
			return [
				'text' => $nav['name'],
				'url' => $nav['href']
			];
		}, $navigation);
		$links = array_merge($links, $guestNavigation);

		$legalLinks = '';
		$divider = '';
		foreach ($links as $link) {
			if ($link['url'] !== ''
				&& filter_var($link['url'], FILTER_VALIDATE_URL)
			) {
				$legalLinks .= $divider . '<a href="' . $link['url'] . '" class="legal" target="_blank"'
					. ' rel="noreferrer noopener">' . $link['text'] . '</a>';
				$divider = ' · ';
			}
		}
		if ($legalLinks !== '') {
			$footer .= '<br/><span class="footer__legal-links">' . $legalLinks . '</span>';
		}

		return $footer;
	}

	/**
	 * Returns long version of the footer
	 */
	public function getLongFooter(): string {
		return $this->getFromLegacyTheme('getLongFooter', $this->getShortFooter());
	}

	/**
	 * Color that is used for highlighting elements like important buttons
	 * If user theming is enabled then the user defined value is returned
	 */
	public function getColorPrimary(): string {
		$user = $this->userSession->getUser();

		// admin-defined primary color
		$defaultColor = $this->getDefaultColorPrimary();

		if ($this->isUserThemingDisabled()) {
			return $defaultColor;
		}

		// user-defined primary color
		if (!empty($user)) {
			$userPrimaryColor = $this->config->getUserValue($user->getUID(), Application::APP_ID, 'primary_color', '');
			if (preg_match('/^\#([0-9a-f]{3}|[0-9a-f]{6})$/i', $userPrimaryColor)) {
				return $userPrimaryColor;
			}
		}

		// Finally, return the system global primary color
		return $defaultColor;
	}

	/**
	 * Color that is used for the page background (e.g. the header)
	 * If user theming is enabled then the user defined value is returned
	 */
	public function getColorBackground(): string {
		$user = $this->userSession->getUser();

		// admin-defined background color
		$defaultColor = $this->getDefaultColorBackground();

		if ($this->isUserThemingDisabled()) {
			return $defaultColor;
		}

		// user-defined background color
		if (!empty($user)) {
			$userBackgroundColor = $this->config->getUserValue($user->getUID(), Application::APP_ID, 'background_color', '');
			if (preg_match('/^\#([0-9a-f]{3}|[0-9a-f]{6})$/i', $userBackgroundColor)) {
				return $userBackgroundColor;
			}
		}

		// Finally, return the system global background color
		return $defaultColor;
	}

	/**
	 * Return the default primary color - only taking admin setting into account
	 */
	public function getDefaultColorPrimary(): string {
		// try admin color
		$defaultColor = $this->appConfig->getAppValueString('primary_color', '');
		if (preg_match('/^\#([0-9a-f]{3}|[0-9a-f]{6})$/i', $defaultColor)) {
			return $defaultColor;
		}

		return $this->getFromLegacyTheme('getColorPrimary', $this->getFromLegacyTheme('getMailHeaderColor', '#00679e'));
	}

	/**
	 * Default background color only taking admin setting into account
	 */
	public function getDefaultColorBackground(): string {
		$defaultColor = $this->appConfig->getAppValueString('background_color');
		if (preg_match('/^\#([0-9a-f]{3}|[0-9a-f]{6})$/i', $defaultColor)) {
			return $defaultColor;
		}

		return $this->getFromLegacyTheme('getColorBackground', '#00679e');
	}

	/**
	 * Themed logo url
	 *
	 * @param bool $useSvg Whether to point to the SVG image or a fallback
	 */
	public function getLogo(bool $useSvg = true): string {
		$logo = $this->appConfig->getAppValueString('logoMime', '');

		// short cut to avoid setting up the filesystem just to check if the logo is there
		//
		// explanation: if an SVG is requested and the app config value for logoMime is set then the logo is there.
		// otherwise we need to check it and maybe also generate a PNG from the SVG (that's done in getImage() which
		// needs to be called then)
		if ($useSvg === true && $logo !== '') {
			$logoExists = true;
		} else {
			try {
				$this->imageManager->getImage('logo', $useSvg);
				$logoExists = true;
			} catch (\Exception $e) {
				$logoExists = false;
			}
		}

		$cacheBusterCounter = $this->appConfig->getAppValueString('cachebuster', '0');

		if ($logo !== '' || !$logoExists) {
			if ($useSvg) {
				$logo = $this->urlGenerator->imagePath('core', 'logo/logo.svg');
			} else {
				$logo = $this->urlGenerator->imagePath('core', 'logo/logo.png');
			}
			return $logo . '?v=' . $cacheBusterCounter;
		}

		return $this->urlGenerator->linkToRoute('theming.Theming.getImage', [ 'key' => 'logo', 'useSvg' => $useSvg, 'v' => $cacheBusterCounter ]);
	}

	/**
	 * Themed background image url
	 *
	 * @param bool $darkVariant if the dark variant (if available) of the background should be used
	 */
	public function getBackground(bool $darkVariant = false): string {
		return $this->imageManager->getImageUrl('background' . ($darkVariant ? 'Dark' : ''));
	}

	public function getiTunesAppId(): string {
		return $this->appConfig->getAppValueString('iTunesAppId', $this->iTunesAppId);
	}

	public function getiOSClientUrl(): string {
		return $this->appConfig->getAppValueString('iOSClientUrl', $this->iOSClientUrl);
	}

	public function getAndroidClientUrl(): string {
		return $this->appConfig->getAppValueString('AndroidClientUrl', $this->AndroidClientUrl);
	}

	public function getFDroidClientUrl(): string {
		return $this->appConfig->getAppValueString('FDroidClientUrl', $this->FDroidClientUrl);
	}

	/**
	 * Returns the URL where the sync clients are listed
	 */
	public function getSyncClientUrl(): string {
		return $this->getFromLegacyTheme('getSyncClientUrl', $this->config->getSystemValueString('customclient_desktop', 'https://nextcloud.com/install/#install-clients'));
	}

	/**
	 * Check if the image should be replaced by the theming app
	 * and return the new image location then
	 *
	 * @param string $app name of the app
	 * @param string $image filename of the image
	 * @return string|false false if image should not replaced, otherwise the location of the image
	 */
	public function replaceImagePath(string $app, string $image): string|false {
		if ($app === '' || $app === 'files_sharing') {
			$app = 'core';
		}
		$cacheBusterValue = $this->appConfig->getAppValueString('cachebuster', '0');

		$route = '';
		if ($image === 'favicon.ico' && ($this->imageManager->shouldReplaceIcons() || $this->getCustomFavicon() !== null)) {
			$route = $this->urlGenerator->linkToRoute('theming.Icon.getFavicon', ['app' => $app]);
		}
		if (($image === 'favicon-touch.png' || $image === 'favicon-fb.png') && ($this->imageManager->shouldReplaceIcons() || $this->getCustomFavicon() !== null)) {
			$route = $this->urlGenerator->linkToRoute('theming.Icon.getTouchIcon', ['app' => $app]);
		}
		if ($image === 'manifest.json') {
			try {
				$appPath = $this->appManager->getAppPath($app);
				if (file_exists($appPath . '/img/manifest.json')) {
					return false;
				}
			} catch (AppPathNotFoundException $e) {
			}
			$route = $this->urlGenerator->linkToRoute('theming.Theming.getManifest', ['app' => $app ]);
		}
		if (str_starts_with($image, 'filetypes/') && file_exists(\OC::$SERVERROOT . '/core/img/' . $image)) {
			$route = $this->urlGenerator->linkToRoute('theming.Icon.getThemedIcon', ['app' => $app, 'image' => $image]);
		}

		if ($route !== '') {
			return $route . '?v=' . $this->util->getCacheBuster();
		}

		return false;
	}

	protected function getCustomFavicon(): ?ISimpleFile {
		try {
			return $this->imageManager->getImage('favicon');
		} catch (NotFoundException $e) {
			return null;
		}
	}

	/**
	 * Increases the cache buster key
	 */
	public function increaseCacheBuster(): void {
		$cacheBusterKey = (int)$this->appConfig->getAppValueString('cachebuster', '0');
		$this->appConfig->setAppValueString('cachebuster', (string)($cacheBusterKey + 1));
		$this->cacheFactory->createDistributed('theming-')->clear();
		$this->cacheFactory->createDistributed('imagePath')->clear();
	}

	/**
	 * Update setting in the database
	 */
	public function set(string $setting, string $value): void {
		$this->appConfig->setAppValueString($setting, $value);
		$this->increaseCacheBuster();
	}

	/**
	 * Revert all settings to the default value
	 */
	public function undoAll(): void {
		// Remember the current cachebuster value, as we do not want to reset this value
		// Otherwise this can lead to caching issues as the value might be known to a browser already
		$cacheBusterKey = $this->appConfig->getAppValueString('cachebuster', '0');
		$this->appConfig->deleteAppValues();
		$this->appConfig->setAppValueString('cachebuster', $cacheBusterKey);
		$this->increaseCacheBuster();
	}

	/**
	 * Revert admin settings to the default value
	 *
	 * @param string $setting setting which should be reverted
	 * @return string default value
	 */
	public function undo(string $setting): string {
		$this->appConfig->deleteAppValue($setting);
		$this->increaseCacheBuster();

		$returnValue = '';
		switch ($setting) {
			case 'name':
				$returnValue = $this->getEntity();
				break;
			case 'url':
				$returnValue = $this->getBaseUrl();
				break;
			case 'slogan':
				$returnValue = $this->getSlogan();
				break;
			case 'primary_color':
				$returnValue = BackgroundService::DEFAULT_COLOR;
				break;
			case 'background_color':
				// If a background image is set we revert to the mean image color
				if ($this->imageManager->hasImage('background')) {
					$file = $this->imageManager->getImage('background');
					$returnValue = $this->backgroundService->setGlobalBackground($file->read()) ?? '';
				}
				break;
			case 'logo':
			case 'logoheader':
			case 'background':
			case 'favicon':
				$this->imageManager->delete($setting);
				$this->appConfig->deleteAppValue($setting . 'Mime');
				break;
		}

		return $returnValue;
	}

	/**
	 * Color of text in the header menu
	 */
	public function getTextColorBackground(): string {
		return $this->util->invertTextColor($this->getColorBackground()) ? '#000000' : '#ffffff';
	}

	/**
	 * Color of text on primary buttons and other elements
	 */
	public function getTextColorPrimary(): string {
		return $this->util->invertTextColor($this->getColorPrimary()) ? '#000000' : '#ffffff';
	}

	/**
	 * Color of text in the header and primary buttons
	 */
	public function getDefaultTextColorPrimary(): string {
		return $this->util->invertTextColor($this->getDefaultColorPrimary()) ? '#000000' : '#ffffff';
	}

	/**
	 * Has the admin disabled user customization
	 */
	public function isUserThemingDisabled(): bool {
		return $this->appConfig->getAppValueBool('disable-user-theming');
	}

	/**
	 * @return string URL to doc with key
	 */
	public function buildDocLinkToKey(string $key): string {
		if (isset($this->theme) && method_exists($this->theme, 'buildDocLinkToKey')) {
			return $this->theme->buildDocLinkToKey($key);
		}
		return $this->getDocBaseUrl() . '/server/' . $this->defaultDocVersion . '/go.php?to=' . $key;
	}
}
