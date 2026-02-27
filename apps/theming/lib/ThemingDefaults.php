<?php

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

class ThemingDefaults extends \OC_Defaults {

	private string $name;
	private string $title;
	private string $entity;
	private string $productName;
	private string $url;
	private string $backgroundColor;
	private string $primaryColor;
	private string $docBaseUrl;

	private string $iTunesAppId;
	private string $iOSClientUrl;
	private string $AndroidClientUrl;
	private string $FDroidClientUrl;

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
	) {
		parent::__construct();

		$this->name = parent::getName();
		$this->title = parent::getTitle();
		$this->entity = parent::getEntity();
		$this->productName = parent::getProductName();
		$this->url = parent::getBaseUrl();
		$this->primaryColor = parent::getColorPrimary();
		$this->backgroundColor = parent::getColorBackground();
		$this->iTunesAppId = parent::getiTunesAppId();
		$this->iOSClientUrl = parent::getiOSClientUrl();
		$this->AndroidClientUrl = parent::getAndroidClientUrl();
		$this->FDroidClientUrl = parent::getFDroidClientUrl();
		$this->docBaseUrl = parent::getDocBaseUrl();
	}

	public function getName() {
		return strip_tags($this->appConfig->getAppValueString(ConfigLexicon::INSTANCE_NAME, $this->name));
	}

	public function getHTMLName() {
		return $this->appConfig->getAppValueString(ConfigLexicon::INSTANCE_NAME, $this->name);
	}

	public function getTitle() {
		return strip_tags($this->appConfig->getAppValueString(ConfigLexicon::INSTANCE_NAME, $this->title));
	}

	public function getEntity() {
		return strip_tags($this->appConfig->getAppValueString(ConfigLexicon::INSTANCE_NAME, $this->entity));
	}

	public function getProductName() {
		return strip_tags($this->appConfig->getAppValueString(ConfigLexicon::PRODUCT_NAME, $this->productName));
	}

	public function getBaseUrl() {
		return $this->appConfig->getAppValueString(ConfigLexicon::BASE_URL, $this->url);
	}

	/**
	 * We pass a string and sanitizeHTML will return a string too in that case
	 * @psalm-suppress InvalidReturnStatement
	 * @psalm-suppress InvalidReturnType
	 */
	public function getSlogan(?string $lang = null): string {
		return \OCP\Util::sanitizeHTML($this->appConfig->getAppValueString(ConfigLexicon::INSTANCE_SLOGAN, parent::getSlogan($lang)));
	}

	public function getImprintUrl(): string {
		return $this->appConfig->getAppValueString(ConfigLexicon::INSTANCE_IMPRINT_URL, '');
	}

	public function getPrivacyUrl(): string {
		return $this->appConfig->getAppValueString(ConfigLexicon::INSTANCE_PRIVACY_URL, '');
	}

	public function getDocBaseUrl(): string {
		return $this->appConfig->getAppValueString(ConfigLexicon::DOC_BASE_URL, $this->docBaseUrl);
	}

	public function getShortFooter() {
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

		// fall back to default primary color
		return $this->primaryColor;
	}

	/**
	 * Default background color only taking admin setting into account
	 */
	public function getDefaultColorBackground(): string {
		$defaultColor = $this->appConfig->getAppValueString('background_color');
		if (preg_match('/^\#([0-9a-f]{3}|[0-9a-f]{6})$/i', $defaultColor)) {
			return $defaultColor;
		}

		return $this->backgroundColor;
	}

	/**
	 * Themed logo url
	 *
	 * @param bool $useSvg Whether to point to the SVG image or a fallback
	 * @return string
	 */
	public function getLogo($useSvg = true): string {
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

		$cacheBusterCounter = (string)$this->appConfig->getAppValueInt(ConfigLexicon::CACHE_BUSTER);
		if (!$logo || !$logoExists) {
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
	 * @return string
	 */
	public function getBackground(bool $darkVariant = false): string {
		return $this->imageManager->getImageUrl('background' . ($darkVariant ? 'Dark' : ''));
	}

	/**
	 * @return string
	 */
	public function getiTunesAppId() {
		return $this->appConfig->getAppValueString('iTunesAppId', $this->iTunesAppId);
	}

	/**
	 * @return string
	 */
	public function getiOSClientUrl() {
		return $this->appConfig->getAppValueString('iOSClientUrl', $this->iOSClientUrl);
	}

	/**
	 * @return string
	 */
	public function getAndroidClientUrl() {
		return $this->appConfig->getAppValueString('AndroidClientUrl', $this->AndroidClientUrl);
	}

	/**
	 * @return string
	 */
	public function getFDroidClientUrl() {
		return $this->appConfig->getAppValueString('FDroidClientUrl', $this->FDroidClientUrl);
	}

	/**
	 * @return array scss variables to overwrite
	 * @deprecated since Nextcloud 22 - https://github.com/nextcloud/server/issues/9940
	 */
	public function getScssVariables() {
		$cacheBuster = $this->appConfig->getAppValueInt(ConfigLexicon::CACHE_BUSTER);
		$cache = $this->cacheFactory->createDistributed('theming-' . (string)$cacheBuster . '-' . $this->urlGenerator->getBaseUrl());
		if ($value = $cache->get('getScssVariables')) {
			return $value;
		}

		$variables = [
			'theming-cachebuster' => "'" . $cacheBuster . "'",
			'theming-logo-mime' => "'" . $this->appConfig->getAppValueString('logoMime') . "'",
			'theming-background-mime' => "'" . $this->appConfig->getAppValueString('backgroundMime') . "'",
			'theming-logoheader-mime' => "'" . $this->appConfig->getAppValueString('logoheaderMime') . "'",
			'theming-favicon-mime' => "'" . $this->appConfig->getAppValueString('faviconMime') . "'"
		];

		$variables['image-logo'] = "url('" . $this->imageManager->getImageUrl('logo') . "')";
		$variables['image-logoheader'] = "url('" . $this->imageManager->getImageUrl('logoheader') . "')";
		$variables['image-favicon'] = "url('" . $this->imageManager->getImageUrl('favicon') . "')";
		$variables['image-login-background'] = "url('" . $this->imageManager->getImageUrl('background') . "')";
		$variables['image-login-plain'] = 'false';

		if ($this->appConfig->getAppValueString('primary_color', '') !== '') {
			$variables['color-primary'] = $this->getColorPrimary();
			$variables['color-primary-text'] = $this->getTextColorPrimary();
			$variables['color-primary-element'] = $this->util->elementColor($this->getColorPrimary());
		}

		if ($this->appConfig->getAppValueString('backgroundMime', '') === 'backgroundColor') {
			$variables['image-login-plain'] = 'true';
		}

		$variables['has-legal-links'] = 'false';
		if ($this->getImprintUrl() !== '' || $this->getPrivacyUrl() !== '') {
			$variables['has-legal-links'] = 'true';
		}

		$cache->set('getScssVariables', $variables);
		return $variables;
	}

	/**
	 * Check if the image should be replaced by the theming app
	 * and return the new image location then
	 *
	 * @param string $app name of the app
	 * @param string $image filename of the image
	 * @return bool|string false if image should not replaced, otherwise the location of the image
	 */
	public function replaceImagePath($app, $image) {
		if ($app === '' || $app === 'files_sharing') {
			$app = 'core';
		}

		$route = false;
		if ($image === 'favicon.ico' && ($this->imageManager->canConvert('ICO') || $this->getCustomFavicon() !== null)) {
			$route = $this->urlGenerator->linkToRoute('theming.Icon.getFavicon', ['app' => $app]);
		}
		if (($image === 'favicon-touch.png' || $image === 'favicon-fb.png') && ($this->imageManager->canConvert('PNG') || $this->getCustomFavicon() !== null)) {
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

		if ($route) {
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
		$cacheBusterKey = $this->appConfig->getAppValueInt(ConfigLexicon::CACHE_BUSTER);
		$this->appConfig->setAppValueInt(ConfigLexicon::CACHE_BUSTER, $cacheBusterKey + 1);
		$this->cacheFactory->createDistributed('theming-')->clear();
		$this->cacheFactory->createDistributed('imagePath')->clear();
	}

	/**
	 * Update setting in the database
	 *
	 * @param string $setting
	 * @param string $value
	 */
	public function set($setting, $value): void {
		switch ($setting) {
			case ConfigLexicon::CACHE_BUSTER:
				$this->appConfig->setAppValueInt(ConfigLexicon::CACHE_BUSTER, (int)$value);
				break;
			case ConfigLexicon::USER_THEMING_DISABLED:
				$value = in_array($value, ['1', 'true', 'yes', 'on']);
				$this->appConfig->setAppValueBool(ConfigLexicon::USER_THEMING_DISABLED, $value);
				break;
			default:
				$this->appConfig->setAppValueString($setting, $value);
				break;
		}
		$this->increaseCacheBuster();
	}

	/**
	 * Revert all settings to the default value
	 */
	public function undoAll(): void {
		// Remember the current cachebuster value, as we do not want to reset this value
		// Otherwise this can lead to caching issues as the value might be known to a browser already
		$cacheBusterKey = $this->appConfig->getAppValueInt(ConfigLexicon::CACHE_BUSTER);
		$this->appConfig->deleteAppValues();
		$this->appConfig->setAppValueInt(ConfigLexicon::CACHE_BUSTER, $cacheBusterKey);
		$this->increaseCacheBuster();
	}

	/**
	 * Revert admin settings to the default value
	 *
	 * @param string $setting setting which should be reverted
	 * @return string default value
	 */
	public function undo($setting): string {
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
	 *
	 * @return string
	 */
	public function getTextColorBackground() {
		return $this->util->invertTextColor($this->getColorBackground()) ? '#000000' : '#ffffff';
	}

	/**
	 * Color of text on primary buttons and other elements
	 *
	 * @return string
	 */
	public function getTextColorPrimary() {
		return $this->util->invertTextColor($this->getColorPrimary()) ? '#000000' : '#ffffff';
	}

	/**
	 * Color of text in the header and primary buttons
	 *
	 * @return string
	 */
	public function getDefaultTextColorPrimary() {
		return $this->util->invertTextColor($this->getDefaultColorPrimary()) ? '#000000' : '#ffffff';
	}

	/**
	 * Has the admin disabled user customization
	 */
	public function isUserThemingDisabled(): bool {
		return $this->appConfig->getAppValueBool(ConfigLexicon::USER_THEMING_DISABLED, false);
	}
}
