<?php

/**
 * Default strings and values which differ between the enterprise and the
 * community edition. Use the get methods to always get the right strings.
 */
class OC_Defaults {

	private $theme;
	private $l;

	private $defaultEntity;
	private $defaultName;
	private $defaultTitle;
	private $defaultBaseUrl;
	private $defaultSyncClientUrl;
	private $defaultiOSClientUrl;
	private $defaultiTunesAppId;
	private $defaultAndroidClientUrl;
	private $defaultDocBaseUrl;
	private $defaultDocVersion;
	private $defaultSlogan;
	private $defaultLogoClaim;
	private $defaultMailHeaderColor;

	function __construct() {
		$this->l = \OC::$server->getL10N('lib');
		$version = OC_Util::getVersion();

		$this->defaultEntity = 'ownCloud'; /* e.g. company name, used for footers and copyright notices */
		$this->defaultName = 'ownCloud'; /* short name, used when referring to the software */
		$this->defaultTitle = 'ownCloud'; /* can be a longer name, for titles */
		$this->defaultBaseUrl = 'https://owncloud.org';
		$this->defaultSyncClientUrl = 'https://owncloud.org/sync-clients/';
		$this->defaultiOSClientUrl = 'https://itunes.apple.com/us/app/owncloud/id543672169?mt=8';
		$this->defaultiTunesAppId = '543672169';
		$this->defaultAndroidClientUrl = 'https://play.google.com/store/apps/details?id=com.owncloud.android';
		$this->defaultDocBaseUrl = 'http://doc.owncloud.org';
		$this->defaultDocVersion = $version[0] . '.0'; // used to generate doc links
		$this->defaultSlogan = $this->l->t('web services under your control');
		$this->defaultLogoClaim = '';
		$this->defaultMailHeaderColor = '#1d2d44'; /* header color of mail notifications */

		if (file_exists(OC::$SERVERROOT . '/themes/' . OC_Util::getTheme() . '/defaults.php')) {
			// prevent defaults.php from printing output
			ob_start();
			require_once 'themes/' . OC_Util::getTheme() . '/defaults.php';
			ob_end_clean();
			$this->theme = new OC_Theme();
		}
	}

	/**
	 * @param string $method
	 */
	private function themeExist($method) {
		if (isset($this->theme) && method_exists($this->theme, $method)) {
			return true;
		}
		return false;
	}

	/**
	 * Returns the base URL
	 * @return string URL
	 */
	public function getBaseUrl() {
		if ($this->themeExist('getBaseUrl')) {
			return $this->theme->getBaseUrl();
		} else {
			return $this->defaultBaseUrl;
		}
	}

	/**
	 * Returns the URL where the sync clients are listed
	 * @return string URL
	 */
	public function getSyncClientUrl() {
		if ($this->themeExist('getSyncClientUrl')) {
			return $this->theme->getSyncClientUrl();
		} else {
			return $this->defaultSyncClientUrl;
		}
	}

	/**
	 * Returns the URL to the App Store for the iOS Client
	 * @return string URL
	 */
	public function getiOSClientUrl() {
		if ($this->themeExist('getiOSClientUrl')) {
			return $this->theme->getiOSClientUrl();
		} else {
			return $this->defaultiOSClientUrl;
		}
	}

	/**
	 * Returns the AppId for the App Store for the iOS Client
	 * @return string AppId
	 */
	public function getiTunesAppId() {
		if ($this->themeExist('getiTunesAppId')) {
			return $this->theme->getiTunesAppId();
		} else {
			return $this->defaultiTunesAppId;
		}
	}

	/**
	 * Returns the URL to Google Play for the Android Client
	 * @return string URL
	 */
	public function getAndroidClientUrl() {
		if ($this->themeExist('getAndroidClientUrl')) {
			return $this->theme->getAndroidClientUrl();
		} else {
			return $this->defaultAndroidClientUrl;
		}
	}

	/**
	 * Returns the documentation URL
	 * @return string URL
	 */
	public function getDocBaseUrl() {
		if ($this->themeExist('getDocBaseUrl')) {
			return $this->theme->getDocBaseUrl();
		} else {
			return $this->defaultDocBaseUrl;
		}
	}

	/**
	 * Returns the title
	 * @return string title
	 */
	public function getTitle() {
		if ($this->themeExist('getTitle')) {
			return $this->theme->getTitle();
		} else {
			return $this->defaultTitle;
		}
	}

	/**
	 * Returns the short name of the software
	 * @return string title
	 */
	public function getName() {
		if ($this->themeExist('getName')) {
			return $this->theme->getName();
		} else {
			return $this->defaultName;
		}
	}

	/**
	 * Returns the short name of the software containing HTML strings
	 * @return string title
	 */
	public function getHTMLName() {
		if ($this->themeExist('getHTMLName')) {
			return $this->theme->getHTMLName();
		} else {
			return $this->defaultName;
		}
	}

	/**
	 * Returns entity (e.g. company name) - used for footer, copyright
	 * @return string entity name
	 */
	public function getEntity() {
		if ($this->themeExist('getEntity')) {
			return $this->theme->getEntity();
		} else {
			return $this->defaultEntity;
		}
	}

	/**
	 * Returns slogan
	 * @return string slogan
	 */
	public function getSlogan() {
		if ($this->themeExist('getSlogan')) {
			return $this->theme->getSlogan();
		} else {
			return $this->defaultSlogan;
		}
	}

	/**
	 * Returns logo claim
	 * @return string logo claim
	 */
	public function getLogoClaim() {
		if ($this->themeExist('getLogoClaim')) {
			return $this->theme->getLogoClaim();
		} else {
			return $this->defaultLogoClaim;
		}
	}

	/**
	 * Returns short version of the footer
	 * @return string short footer
	 */
	public function getShortFooter() {
		if ($this->themeExist('getShortFooter')) {
			$footer = $this->theme->getShortFooter();
		} else {
			$footer = '<a href="'. $this->getBaseUrl() . '" target="_blank">' .$this->getEntity() . '</a>'.
				' – ' . $this->getSlogan();
		}

		return $footer;
	}

	/**
	 * Returns long version of the footer
	 * @return string long footer
	 */
	public function getLongFooter() {
		if ($this->themeExist('getLongFooter')) {
			$footer = $this->theme->getLongFooter();
		} else {
			$footer = $this->getShortFooter();
		}

		return $footer;
	}

	public function buildDocLinkToKey($key) {
		if ($this->themeExist('buildDocLinkToKey')) {
			return $this->theme->buildDocLinkToKey($key);
		}
		return $this->getDocBaseUrl() . '/server/' . $this->defaultDocVersion . '/go.php?to=' . $key;
	}

	/**
	 * Returns mail header color
	 * @return string
	 */
	public function getMailHeaderColor() {
		if ($this->themeExist('getMailHeaderColor')) {
			return $this->theme->getMailHeaderColor();
		} else {
			return $this->defaultMailHeaderColor;
		}
	}

}
