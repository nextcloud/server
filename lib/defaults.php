<?php

/**
 * Default strings and values which differ between the enterprise and the
 * community edition. Use the get methods to always get the right strings.
 */

class OC_Defaults {

	private static $communityEntity = "ownCloud";
	private static $communityName = "ownCloud";
	private static $communityBaseUrl = "http://owncloud.org";
	private static $communitySyncClientUrl = " http://owncloud.org/sync-clients/";
	private static $communityDocBaseUrl = "http://doc.owncloud.org";
	private static $communitySlogan = "web services under your control";

	private static $enterpriseEntity = "ownCloud Inc.";
	private static $enterpriseName = "ownCloud Enterprise Edition";
	private static $enterpriseBaseUrl = "https://owncloud.com";
	private static $enterpriseDocBaseUrl = "http://doc.owncloud.com";
	private static $enterpiseSyncClientUrl = "https://owncloud.com/products/desktop-clients";
	private static $enterpriseSlogan = "Your Cloud, Your Data, Your Way!";


	public static function getBaseUrl() {
		if (OC_Util::getEditionString() === '') {
			return self::$communityBaseUrl;
		} else {
			return self::$enterpriseBaseUrl;
		}
	}

	public static function getSyncClientUrl() {
		if (OC_Util::getEditionString() === '') {
			return self::$communitySyncClientUrl;
		} else {
			return self::$enterpiseSyncClientUrl;
		}
	}

	public static function getDocBaseUrl() {
		if (OC_Util::getEditionString() === '') {
			return self::$communityDocBaseUrl;
		} else {
			return self::$enterpriseDocBaseUrl;
		}
	}

	public static function getName() {
		if (OC_Util::getEditionString() === '') {
			return self::$communityName;
		} else {
			return self::$enterpriseName;
		}
	}

	public static function getEntity() {
		if (OC_Util::getEditionString() === '') {
			return self::$communityEntity;
		} else {
			return self::$enterpriseEntity;
		}
	}

	public static function getSlogan() {
		$l = OC_L10N::get('core');
		if (OC_Util::getEditionString() === '') {
			return $l->t(self::$communitySlogan);
		} else {
			return self::$enterpriseSlogan;
		}
	}

}