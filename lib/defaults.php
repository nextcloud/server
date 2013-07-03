<?php

/**
 * Default strings and values which differ between the enterprise and the
 * community edition. Use the get methods to always get the right strings.
 */

if (file_exists(OC::$SERVERROOT . '/themes/' . OC_Util::getTheme() . '/defaults.php')) {
	require_once 'themes/' . OC_Util::getTheme() . '/defaults.php';
}

class OC_Defaults {

	private static $defaultEntity;
	private static $defaultName;
	private static $defaultBaseUrl;
	private static $defaultSyncClientUrl;
	private static $defaultDocBaseUrl;
	private static $defaultSlogan;
	private static $defaultLogoClaim;

	public static function init() {
		$l = OC_L10N::get('core');

		self::$defaultEntity = "ownCloud";
		self::$defaultName = "ownCloud";
		self::$defaultBaseUrl = "http://owncloud.org";
		self::$defaultSyncClientUrl = " http://owncloud.org/sync-clients/";
		self::$defaultDocBaseUrl = "http://doc.owncloud.org";
		self::$defaultSlogan = $l->t("web services under your control");
		self::$defaultLogoClaim = "";

		if (class_exists("OC_Theme")) {
			OC_Theme::init();
		}
	}

	private static function themeExist($method) {
		if (OC_Util::getTheme() !== '' && method_exists('OC_Theme', $method)) {
			return true;
		}
		return false;
	}

	public static function getBaseUrl() {
		if (self::themeExist('getBaseUrl')) {
			return OC_Theme::getBaseUrl();
		} else {
			return self::$defaultBaseUrl;
		}
	}

	public static function getSyncClientUrl() {
		if (self::themeExist('getSyncClientUrl')) {
			return OC_Theme::getSyncClientUrl();
		} else {
			return self::$defaultSyncClientUrl;
		}
	}

	public static function getDocBaseUrl() {
		if (self::themeExist('getDocBaseUrl')) {
			return OC_Theme::getDocBaseUrl();
		} else {
			return self::$defaultDocBaseUrl;
		}
	}

	public static function getName() {
		if (self::themeExist('getName')) {
			return OC_Theme::getName();
		} else {
			return self::$defaultName;
		}
	}

	public static function getEntity() {
		if (self::themeExist('getEntity')) {
			return OC_Theme::getEntity();
		} else {
			return self::$defaultEntity;
		}
	}

	public static function getSlogan() {
		if (self::themeExist('getSlogan')) {
			return OC_Theme::getSlogan();
		} else {
			return self::$defaultSlogan;
		}
	}

	public static function getLogoClaim() {
		if (self::themeExist('getLogoClaim')) {
			return OC_Theme::getLogoClaim();
		} else {
			return self::$defaultLogoClaim;
		}
	}

	public static function getShortFooter() {
		if (self::themeExist('getShortFooter')) {
			$footer = OC_Theme::getShortFooter();
		} else {
			$footer = "<a href=\"". self::getBaseUrl() . "\" target=\"_blank\">" .self::getEntity() . "</a>".
				' – ' . self::getSlogan();
		}

		return $footer;
	}

	public static function getLongFooter() {
		if (self::themeExist('getLongFooter')) {
			$footer = OC_Theme::getLongFooter();
		} else {
			$footer = self::getShortFooter();
		}

		return $footer;
	}

}
