<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\Share;

use OC\Core\AppInfo\ConfigLexicon;
use OCP\IAppConfig;
use OCP\Server;

class Helper extends \OC\Share\Constants {
	/**
	 * get default expire settings defined by the admin
	 * @return array contains 'defaultExpireDateSet', 'enforceExpireDate', 'expireAfterDays'
	 */
	public static function getDefaultExpireSetting() {
		$config = \OC::$server->getConfig();
		$appConfig = Server::get(IAppConfig::class);

		$defaultExpireSettings = ['defaultExpireDateSet' => false];

		// get default expire settings
		if ($appConfig->getValueBool('core', ConfigLexicon::SHARE_LINK_EXPIRE_DATE_DEFAULT)) {
			$defaultExpireSettings['defaultExpireDateSet'] = true;
			$defaultExpireSettings['expireAfterDays'] = (int)$config->getAppValue('core', 'shareapi_expire_after_n_days', '7');
			$defaultExpireSettings['enforceExpireDate'] = $appConfig->getValueBool('core', ConfigLexicon::SHARE_LINK_EXPIRE_DATE_ENFORCED);
		}

		return $defaultExpireSettings;
	}

	public static function calcExpireDate() {
		$expireAfter = \OC\Share\Share::getExpireInterval() * 24 * 60 * 60;
		$expireAt = time() + $expireAfter;
		$date = new \DateTime();
		$date->setTimestamp($expireAt);
		$date->setTime(0, 0, 0);
		//$dateString = $date->format('Y-m-d') . ' 00:00:00';

		return $date;
	}

	/**
	 * calculate expire date
	 * @param array $defaultExpireSettings contains 'defaultExpireDateSet', 'enforceExpireDate', 'expireAfterDays'
	 * @param int $creationTime timestamp when the share was created
	 * @param int $userExpireDate expire timestamp set by the user
	 * @return mixed integer timestamp or False
	 */
	public static function calculateExpireDate($defaultExpireSettings, $creationTime, $userExpireDate = null) {
		$expires = false;
		$defaultExpires = null;

		if (!empty($defaultExpireSettings['defaultExpireDateSet'])) {
			$defaultExpires = $creationTime + $defaultExpireSettings['expireAfterDays'] * 86400;
		}


		if (isset($userExpireDate)) {
			// if the admin decided to enforce the default expire date then we only take
			// the user defined expire date of it is before the default expire date
			if ($defaultExpires && !empty($defaultExpireSettings['enforceExpireDate'])) {
				$expires = min($userExpireDate, $defaultExpires);
			} else {
				$expires = $userExpireDate;
			}
		} elseif ($defaultExpires && !empty($defaultExpireSettings['enforceExpireDate'])) {
			$expires = $defaultExpires;
		}

		return $expires;
	}

	/**
	 * Strips away a potential file names and trailing slashes:
	 * - http://localhost
	 * - http://localhost/
	 * - http://localhost/index.php
	 * - http://localhost/index.php/s/{shareToken}
	 *
	 * all return: http://localhost
	 *
	 * @param string $remote
	 * @return string
	 */
	protected static function fixRemoteURL($remote) {
		$remote = str_replace('\\', '/', $remote);
		if ($fileNamePosition = strpos($remote, '/index.php')) {
			$remote = substr($remote, 0, $fileNamePosition);
		}
		$remote = rtrim($remote, '/');

		return $remote;
	}

	/**
	 * check if two federated cloud IDs refer to the same user
	 *
	 * @param string $user1
	 * @param string $server1
	 * @param string $user2
	 * @param string $server2
	 * @return bool true if both users and servers are the same
	 */
	public static function isSameUserOnSameServer($user1, $server1, $user2, $server2) {
		$normalizedServer1 = strtolower(\OC\Share\Share::removeProtocolFromUrl($server1));
		$normalizedServer2 = strtolower(\OC\Share\Share::removeProtocolFromUrl($server2));

		if (rtrim($normalizedServer1, '/') === rtrim($normalizedServer2, '/')) {
			// FIXME this should be a method in the user management instead
			\OCP\Util::emitHook(
				'\OCA\Files_Sharing\API\Server2Server',
				'preLoginNameUsedAsUserName',
				['uid' => &$user1]
			);
			\OCP\Util::emitHook(
				'\OCA\Files_Sharing\API\Server2Server',
				'preLoginNameUsedAsUserName',
				['uid' => &$user2]
			);

			if ($user1 === $user2) {
				return true;
			}
		}

		return false;
	}

	public static function getTokenLength(): int {
		$config = Server::get(\OCP\IAppConfig::class);
		$tokenLength = $config->getValueInt('core', 'shareapi_token_length', self::DEFAULT_TOKEN_LENGTH);
		$tokenLength = $tokenLength ?: self::DEFAULT_TOKEN_LENGTH;

		// Token length should be within the defined min and max limits
		return max(self::MIN_TOKEN_LENGTH, min($tokenLength, self::MAX_TOKEN_LENGTH));
	}
}
