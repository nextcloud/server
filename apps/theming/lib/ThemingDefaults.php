<?php
/**
 * @copyright Copyright (c) 2016 Bjoern Schiessle <bjoern@schiessle.org>
 * @copyright Copyright (c) 2017 Lukas Reschke <lukas@statuscode.ch>
 *
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
 * @author Bjoern Schiessle <bjoern@schiessle.org>
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Daniel Kesselberg <mail@danielkesselberg.de>
 * @author Guillaume COMPAGNON <gcompagnon@outlook.com>
 * @author Jan-Christoph Borchardt <hey@jancborchardt.net>
 * @author Joachim Bauch <bauch@struktur.de>
 * @author Joas Schilling <coding@schilljs.com>
 * @author John Molakvoæ <skjnldsv@protonmail.com>
 * @author Julien Veyssier <eneiluj@posteo.net>
 * @author Julius Haertl <jus@bitgrid.net>
 * @author Julius Härtl <jus@bitgrid.net>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Michael Weimann <mail@michael-weimann.eu>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Patrik Kernstock <info@pkern.at>
 * @author Robin Appelman <robin@icewind.nl>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
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
namespace OCA\Theming;

use OCA\Theming\AppInfo\Application;
use OCA\Theming\Service\BackgroundService;
use OCP\App\AppPathNotFoundException;
use OCP\App\IAppManager;
use OCP\Files\NotFoundException;
use OCP\Files\SimpleFS\ISimpleFile;
use OCP\ICacheFactory;
use OCP\IConfig;
use OCP\IL10N;
use OCP\INavigationManager;
use OCP\IURLGenerator;
use OCP\IUserSession;

class ThemingDefaults extends \OC_Defaults {

	private IConfig $config;
	private IL10N $l;
	private ImageManager $imageManager;
	private IUserSession $userSession;
	private IURLGenerator $urlGenerator;
	private ICacheFactory $cacheFactory;
	private Util $util;
	private IAppManager $appManager;
	private INavigationManager $navigationManager;

	private string $name;
	private string $title;
	private string $entity;
	private string $productName;
	private string $url;
	private string $color;
	private string $docBaseUrl;

	private string $iTunesAppId;
	private string $iOSClientUrl;
	private string $AndroidClientUrl;
	private string $FDroidClientUrl;

	/**
	 * ThemingDefaults constructor.
	 *
	 * @param IConfig $config
	 * @param IL10N $l
	 * @param ImageManager $imageManager
	 * @param IUserSession $userSession
	 * @param IURLGenerator $urlGenerator
	 * @param ICacheFactory $cacheFactory
	 * @param Util $util
	 * @param IAppManager $appManager
	 */
	public function __construct(IConfig $config,
								IL10N $l,
								IUserSession $userSession,
								IURLGenerator $urlGenerator,
								ICacheFactory $cacheFactory,
								Util $util,
								ImageManager $imageManager,
								IAppManager $appManager,
								INavigationManager $navigationManager
	) {
		parent::__construct();
		$this->config = $config;
		$this->l = $l;
		$this->imageManager = $imageManager;
		$this->userSession = $userSession;
		$this->urlGenerator = $urlGenerator;
		$this->cacheFactory = $cacheFactory;
		$this->util = $util;
		$this->appManager = $appManager;
		$this->navigationManager = $navigationManager;

		$this->name = parent::getName();
		$this->title = parent::getTitle();
		$this->entity = parent::getEntity();
		$this->productName = parent::getProductName();
		$this->url = parent::getBaseUrl();
		$this->color = parent::getColorPrimary();
		$this->iTunesAppId = parent::getiTunesAppId();
		$this->iOSClientUrl = parent::getiOSClientUrl();
		$this->AndroidClientUrl = parent::getAndroidClientUrl();
		$this->FDroidClientUrl = parent::getFDroidClientUrl();
		$this->docBaseUrl = parent::getDocBaseUrl();
	}

	public function getName() {
		return strip_tags($this->config->getAppValue('theming', 'name', $this->name));
	}

	public function getHTMLName() {
		return $this->config->getAppValue('theming', 'name', $this->name);
	}

	public function getTitle() {
		return strip_tags($this->config->getAppValue('theming', 'name', $this->title));
	}

	public function getEntity() {
		return strip_tags($this->config->getAppValue('theming', 'name', $this->entity));
	}

	public function getProductName() {
		return strip_tags($this->config->getAppValue('theming', 'productName', $this->productName));
	}

	public function getBaseUrl() {
		return $this->config->getAppValue('theming', 'url', $this->url);
	}

	/**
	 * We pass a string and sanitizeHTML will return a string too in that case
	 * @psalm-suppress InvalidReturnStatement
	 * @psalm-suppress InvalidReturnType
	 */
	public function getSlogan(?string $lang = null) {
		return \OCP\Util::sanitizeHTML($this->config->getAppValue('theming', 'slogan', parent::getSlogan($lang)));
	}

	public function getImprintUrl() {
		return (string)$this->config->getAppValue('theming', 'imprintUrl', '');
	}

	public function getPrivacyUrl() {
		return (string)$this->config->getAppValue('theming', 'privacyUrl', '');
	}

	public function getDocBaseUrl() {
		return (string)$this->config->getAppValue('theming', 'docBaseUrl', $this->docBaseUrl);
	}

	public function getShortFooter() {
		$slogan = $this->getSlogan();
		$baseUrl = $this->getBaseUrl();
		$entity = $this->getEntity();
		$footer = '';

		if ($entity !== '') {
			if ($baseUrl !== '') {
				$footer = '<a href="' . $baseUrl . '" target="_blank"' .
					' rel="noreferrer noopener" class="entity-name">' . $entity . '</a>';
			} else {
				$footer = '<span class="entity-name">' .$entity . '</span>';
			}
		}
		$footer .= ($slogan !== '' ? ' – ' . $slogan : '');

		$links = [
			[
				'text' => $this->l->t('Legal notice'),
				'url' => (string)$this->getImprintUrl()
			],
			[
				'text' => $this->l->t('Privacy policy'),
				'url' => (string)$this->getPrivacyUrl()
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
				$legalLinks .= $divider . '<a href="' . $link['url'] . '" class="legal" target="_blank"' .
					' rel="noreferrer noopener">' . $link['text'] . '</a>';
				$divider = ' · ';
			}
		}
		if ($legalLinks !== '') {
			$footer .= '<br/>' . $legalLinks;
		}

		return $footer;
	}

	/**
	 * Color that is used for the header as well as for mail headers
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
			$themingBackgroundColor = $this->config->getUserValue($user->getUID(), Application::APP_ID, 'background_color', '');
			// If the user selected a specific colour
			if (preg_match('/^\#([0-9a-f]{3}|[0-9a-f]{6})$/i', $themingBackgroundColor)) {
				return $themingBackgroundColor;
			}
		}

		// If the default color is not valid, return the default background one
		if (!preg_match('/^\#([0-9a-f]{3}|[0-9a-f]{6})$/i', $defaultColor)) {
			return BackgroundService::DEFAULT_COLOR;
		}

		// Finally, return the system global primary color
		return $defaultColor;
	}

	/**
	 * Return the default color primary
	 */
	public function getDefaultColorPrimary(): string {
		$color = $this->config->getAppValue(Application::APP_ID, 'color', '');
		if (!preg_match('/^\#([0-9a-f]{3}|[0-9a-f]{6})$/i', $color)) {
			return BackgroundService::DEFAULT_COLOR;
		}

		return $color;
	}

	/**
	 * Themed logo url
	 *
	 * @param bool $useSvg Whether to point to the SVG image or a fallback
	 * @return string
	 */
	public function getLogo($useSvg = true): string {
		$logo = $this->config->getAppValue('theming', 'logoMime', '');

		// short cut to avoid setting up the filesystem just to check if the logo is there
		//
		// explanation: if an SVG is requested and the app config value for logoMime is set then the logo is there.
		// otherwise we need to check it and maybe also generate a PNG from the SVG (that's done in getImage() which
		// needs to be called then)
		if ($useSvg === true && $logo !== false) {
			$logoExists = true;
		} else {
			try {
				$this->imageManager->getImage('logo', $useSvg);
				$logoExists = true;
			} catch (\Exception $e) {
				$logoExists = false;
			}
		}

		$cacheBusterCounter = $this->config->getAppValue('theming', 'cachebuster', '0');

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
	 * @return string
	 */
	public function getBackground(): string {
		return $this->imageManager->getImageUrl('background');
	}

	/**
	 * @return string
	 */
	public function getiTunesAppId() {
		return $this->config->getAppValue('theming', 'iTunesAppId', $this->iTunesAppId);
	}

	/**
	 * @return string
	 */
	public function getiOSClientUrl() {
		return $this->config->getAppValue('theming', 'iOSClientUrl', $this->iOSClientUrl);
	}

	/**
	 * @return string
	 */
	public function getAndroidClientUrl() {
		return $this->config->getAppValue('theming', 'AndroidClientUrl', $this->AndroidClientUrl);
	}

	/**
	 * @return string
	 */
	public function getFDroidClientUrl() {
		return $this->config->getAppValue('theming', 'FDroidClientUrl', $this->FDroidClientUrl);
	}

	/**
	 * @return array scss variables to overwrite
	 */
	public function getScssVariables() {
		$cacheBuster = $this->config->getAppValue('theming', 'cachebuster', '0');
		$cache = $this->cacheFactory->createDistributed('theming-' . $cacheBuster . '-' . $this->urlGenerator->getBaseUrl());
		if ($value = $cache->get('getScssVariables')) {
			return $value;
		}

		$variables = [
			'theming-cachebuster' => "'" . $cacheBuster . "'",
			'theming-logo-mime' => "'" . $this->config->getAppValue('theming', 'logoMime') . "'",
			'theming-background-mime' => "'" . $this->config->getAppValue('theming', 'backgroundMime') . "'",
			'theming-logoheader-mime' => "'" . $this->config->getAppValue('theming', 'logoheaderMime') . "'",
			'theming-favicon-mime' => "'" . $this->config->getAppValue('theming', 'faviconMime') . "'"
		];

		$variables['image-logo'] = "url('".$this->imageManager->getImageUrl('logo')."')";
		$variables['image-logoheader'] = "url('".$this->imageManager->getImageUrl('logoheader')."')";
		$variables['image-favicon'] = "url('".$this->imageManager->getImageUrl('favicon')."')";
		$variables['image-login-background'] = "url('".$this->imageManager->getImageUrl('background')."')";
		$variables['image-login-plain'] = 'false';

		if ($this->config->getAppValue('theming', 'color', '') !== '') {
			$variables['color-primary'] = $this->getColorPrimary();
			$variables['color-primary-text'] = $this->getTextColorPrimary();
			$variables['color-primary-element'] = $this->util->elementColor($this->getColorPrimary());
		}

		if ($this->config->getAppValue('theming', 'backgroundMime', '') === 'backgroundColor') {
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
		$cacheBusterValue = $this->config->getAppValue('theming', 'cachebuster', '0');

		$route = false;
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
		$cacheBusterKey = (int)$this->config->getAppValue('theming', 'cachebuster', '0');
		$this->config->setAppValue('theming', 'cachebuster', (string)($cacheBusterKey + 1));
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
		$this->config->setAppValue('theming', $setting, $value);
		$this->increaseCacheBuster();
	}

	/**
	 * Revert all settings to the default value
	 */
	public function undoAll(): void {
		$this->config->deleteAppValues('theming');
		$this->increaseCacheBuster();
	}

	/**
	 * Revert settings to the default value
	 *
	 * @param string $setting setting which should be reverted
	 * @return string default value
	 */
	public function undo($setting): string {
		$this->config->deleteAppValue('theming', $setting);
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
			case 'color':
				$returnValue = $this->getDefaultColorPrimary();
				break;
			case 'logo':
			case 'logoheader':
			case 'background':
			case 'favicon':
				$this->imageManager->delete($setting);
				$this->config->deleteAppValue('theming', $setting . 'Mime');
				break;
		}

		return $returnValue;
	}

	/**
	 * Color of text in the header and primary buttons
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
		return $this->config->getAppValue('theming', 'disable-user-theming', 'no') === 'yes';
	}
}
