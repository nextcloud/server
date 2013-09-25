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
	 * @brief subject for share notification mail
	 * @param string $user user who shared the item
	 * @pram string $itemName name of the item
	 */
	public function getShareNotificationSubject($user, $itemName) {
		if ($this->themeExist('getShareNotificationSubject')) {
			return $this->theme->getShareNotificationSubject($user, $itemName);
		} else {
			return $this->l->t("%s shared »%s« with you", array($user, $itemName));
		}
	}

	/**
	 * @brief mail body for share notification mail (text only)
	 * @param string $sender owner of the file/folder
	 * @param string $itemName name of the file/folder
	 * @param string $link link directly to the file/folder in your ownCloud
	 * @param string $expiration expiration date
	 */
	public function getShareNotificationTextHtml($sender, $itemName, $link, $expiration=null) {
		if ($this->themeExist('getShareNotificationTextHtml')) {
			return $this->theme->getShareNotificationTextHtml($sender, $itemName, $link, $expiration);
		} else {

			$message = $this->l->t('Hey there,<br><br>just letting you know that %s shared »%s« with you.'.
					'<br><a href="%s">View it!</a>', array($sender, $itemName, $link));

			if ($expiration) {
				$message .= '<br><br>';
				$message .= $this->l->t("The share will expire at %s.", array($expiration));
			}

			$message .= '<br><br>';
			$message .= $this->l->t('Cheers!');

			return $message;
		}
	}

	/**
	 * @brief mail body for share notification mail (text only)
	 * @param string $sender owner of the file/folder
	 * @param string $itemName name of the file/folder
	 * @param string $link link directly to the file/folder in your ownCloud
	 * @param string $expiration expiration date
	 */
	public function getShareNotificationTextAlt($sender, $itemName, $link, $expiration=null) {
		if ($this->themeExist('getShareNotificationTextAlt')) {
			return $this->theme->getShareNotificationTextAlt($sender, $itemName, $link, $expiration);
		} else {

			$message = $this->l->t("Hey there,\n\njust letting you know that %s shared %s with you.\n".
					"View it: %s", array($sender, $itemName, $link));

			if ($expiration) {
				$message .= "\n\n";
				$message .= $this->l->t("The share will expire at %s.", array($expiration));
			}

			$message .= "\n\n";
			$message .= $this->l->t('Cheers!');

			return $message;
		}
	}

	public function getMailFooterHtml() {
		if ($this->themeExist('getMailFooterHtml')) {
			return $this->theme->getMailFooterHtml();
		} else {
			$footer = $this->getName() . ' - ' . $this->getSlogan() .
					'<br>' .
					'<a href="'. $this->getBaseUrl() .'">'.$this->getBaseUrl().'</a>';

			return $footer;
		}
	}

		public function getMailFooterAlt() {
		if ($this->themeExist('getMailFooterAlt')) {
			return $this->theme->getMailFooterAlt();
		} else {
			$footer = $this->getName() . ' - ' . $this->getSlogan() .
					"\n" . $this->getBaseUrl();

			return $footer;
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
