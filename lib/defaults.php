<?php

/**
 * Default strings and values which differ between the enterprise and the
 * community edition. Use the get methods to always get the right strings.
 */


if (file_exists(OC::$SERVERROOT . '/themes/' . OC_Util::getTheme() . '/defaults.php')) {
	require_once 'themes/' . OC_Util::getTheme() . '/defaults.php';
}

class OC_Defaults {

	private $theme;
	private $l;

	private $defaultEntity;
	private $defaultName;
	private $defaultTitle;
	private $defaultBaseUrl;
	private $defaultSyncClientUrl;
	private $defaultDocBaseUrl;
	private $defaultSlogan;
	private $defaultLogoClaim;

	function __construct() {
		$this->l = OC_L10N::get('core');

		$this->defaultEntity = "ownCloud"; /* e.g. company name, used for footers and copyright notices */
		$this->defaultName = "ownCloud"; /* short name, used when referring to the software */
		$this->defaultTitle = "ownCloud"; /* can be a longer name, for titles */
		$this->defaultBaseUrl = "http://owncloud.org";
		$this->defaultSyncClientUrl = " http://owncloud.org/sync-clients/";
		$this->defaultDocBaseUrl = "http://doc.owncloud.org";
		$this->defaultSlogan = $this->l->t("web services under your control");
		$this->defaultLogoClaim = "";

		if (class_exists("OC_Theme")) {
			$this->theme = new OC_Theme();
		}
	}

	private function themeExist($method) {
		if (OC_Util::getTheme() !== '' && method_exists('OC_Theme', $method)) {
			return true;
		}
		return false;
	}

	/**
	 *
	 * @param string $itemType typically "file" or "folder"
	 */
	public function getShareNotificationSubject($itemType) {
		if ($this->themeExist('getShareNotificationSubject')) {
			return $this->theme->getShareNotificationSubject($itemType);
		} else {
			return $this->l->t("A %s was shared with you", array($itemType));
		}
	}

	/**
	 * @param string $sender owner of the file/folder
	 * @param string $itemName name of the file/folder
	 * @param string $itemType typically "file" or "folder"
	 * @param string $link link directly to the file/folder in your ownCloud
	 * @param string $expiration expiration date
	 */
	public function getShareNotificationText($sender, $itemName, $itemType, $link, $expiration=null) {
		if ($this->themeExist('getShareNotificationText')) {
			return $this->theme->getShareNotificationText($sender, $itemName, $itemType, $link, $expiration);
		} else {
			if ($expiration) {
				return $this->l->t("%s shared a %s called %s with you. The share will expire at %s. You can find the %s here: %s", array($sender, $itemType, $itemName, $expiration, $itemType, $link));
			} else {
				return $this->l->t("%s shared a %s called %s with you. You can find the %s here: %s", array($sender, $itemType, $itemName, $itemType, $link));
			}
		}
	}

	public function getBaseUrl() {
		if ($this->themeExist('getBaseUrl')) {
			return $this->theme->getBaseUrl();
		} else {
			return $this->defaultBaseUrl;
		}
	}

	public function getSyncClientUrl() {
		if ($this->themeExist('getSyncClientUrl')) {
			return $this->theme->getSyncClientUrl();
		} else {
			return $this->defaultSyncClientUrl;
		}
	}

	public function getDocBaseUrl() {
		if ($this->themeExist('getDocBaseUrl')) {
			return $this->theme->getDocBaseUrl();
		} else {
			return $this->defaultDocBaseUrl;
		}
	}

	public function getTitle() {
		if ($this->themeExist('getTitle')) {
			return $this->theme->getTitle();
		} else {
			return $this->defaultTitle;
		}
	}

	public function getName() {
		if ($this->themeExist('getName')) {
			return $this->theme->getName();
		} else {
			return $this->defaultName;
		}
	}

	public function getEntity() {
		if ($this->themeExist('getEntity')) {
			return $this->theme->getEntity();
		} else {
			return $this->defaultEntity;
		}
	}

	public function getSlogan() {
		if ($this->themeExist('getSlogan')) {
			return $this->theme->getSlogan();
		} else {
			return $this->defaultSlogan;
		}
	}

	public function getLogoClaim() {
		if ($this->themeExist('getLogoClaim')) {
			return $this->theme->getLogoClaim();
		} else {
			return $this->defaultLogoClaim;
		}
	}

	public function getShortFooter() {
		if ($this->themeExist('getShortFooter')) {
			$footer = $this->theme->getShortFooter();
		} else {
			$footer = "<a href=\"". $this->getBaseUrl() . "\" target=\"_blank\">" .$this->getEntity() . "</a>".
				' – ' . $this->getSlogan();
		}

		return $footer;
	}

	public function getLongFooter() {
		if ($this->themeExist('getLongFooter')) {
			$footer = $this->theme->getLongFooter();
		} else {
			$footer = $this->getShortFooter();
		}

		return $footer;
	}

}
