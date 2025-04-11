<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\FederatedFileSharing;

use OCP\Federation\ICloudIdManager;
use OCP\HintException;
use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\Util;

/**
 * Class AddressHandler - parse, modify and construct federated sharing addresses
 *
 * @package OCA\FederatedFileSharing
 */
class AddressHandler {

	/**
	 * AddressHandler constructor.
	 *
	 * @param IURLGenerator $urlGenerator
	 * @param IL10N $l
	 * @param ICloudIdManager $cloudIdManager
	 */
	public function __construct(
		private IURLGenerator $urlGenerator,
		private IL10N $l,
		private ICloudIdManager $cloudIdManager,
	) {
	}

	/**
	 * split user and remote from federated cloud id
	 *
	 * @param string $address federated share address
	 * @return array<string> [user, remoteURL]
	 * @throws HintException
	 */
	public function splitUserRemote($address) {
		try {
			$cloudId = $this->cloudIdManager->resolveCloudId($address);
			return [$cloudId->getUser(), $cloudId->getRemote()];
		} catch (\InvalidArgumentException $e) {
			$hint = $this->l->t('Invalid Federated Cloud ID');
			throw new HintException('Invalid Federated Cloud ID', $hint, 0, $e);
		}
	}

	/**
	 * generate remote URL part of federated ID
	 *
	 * @return string url of the current server
	 */
	public function generateRemoteURL() {
		return $this->urlGenerator->getAbsoluteURL('/');
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
	public function compareAddresses($user1, $server1, $user2, $server2) {
		$normalizedServer1 = strtolower($this->removeProtocolFromUrl($server1));
		$normalizedServer2 = strtolower($this->removeProtocolFromUrl($server2));

		if (rtrim($normalizedServer1, '/') === rtrim($normalizedServer2, '/')) {
			// FIXME this should be a method in the user management instead
			Util::emitHook(
				'\OCA\Files_Sharing\API\Server2Server',
				'preLoginNameUsedAsUserName',
				['uid' => &$user1]
			);
			Util::emitHook(
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

	/**
	 * remove protocol from URL
	 *
	 * @param string $url
	 * @return string
	 */
	public function removeProtocolFromUrl($url) {
		if (str_starts_with($url, 'https://')) {
			return substr($url, strlen('https://'));
		} elseif (str_starts_with($url, 'http://')) {
			return substr($url, strlen('http://'));
		}

		return $url;
	}

	/**
	 * check if the url contain the protocol (http or https)
	 *
	 * @param string $url
	 * @return bool
	 */
	public function urlContainProtocol($url) {
		if (str_starts_with($url, 'https://') ||
			str_starts_with($url, 'http://')) {
			return true;
		}

		return false;
	}
}
