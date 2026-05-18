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
use OCP\IUserManager;

/**
 * Class AddressHandler - parse, modify and construct federated sharing addresses
 *
 * @package OCA\FederatedFileSharing
 */
class AddressHandler {

	/**
	 * AddressHandler constructor.
	 */
	public function __construct(
		private readonly IURLGenerator $urlGenerator,
		private readonly IL10N $l,
		private readonly ICloudIdManager $cloudIdManager,
		private readonly IUserManager $userManager,
	) {
	}

	/**
	 * Split user and remote from federated cloud id.
	 *
	 * @param string $address federated share address
	 * @return array<string> [user, remoteURL]
	 * @throws HintException
	 */
	public function splitUserRemote(string $address): array {
		try {
			$cloudId = $this->cloudIdManager->resolveCloudId($address);
			return [$cloudId->getUser(), $cloudId->getRemote()];
		} catch (\InvalidArgumentException $e) {
			$hint = $this->l->t('Invalid Federated Cloud ID');
			throw new HintException('Invalid Federated Cloud ID', $hint, 0, $e);
		}
	}

	/**
	 * Generate remote URL part of federated ID
	 *
	 * @return string url of the current server
	 */
	public function generateRemoteURL(): string {
		return $this->urlGenerator->getAbsoluteURL('/');
	}

	/**
	 * Check if two federated cloud IDs refer to the same user
	 *
	 * @return bool true if both users and servers are the same
	 */
	public function compareAddresses(string $user1, string $server1, string $user2, string $server2): bool {
		$normalizedServer1 = strtolower($this->removeProtocolFromUrl($server1));
		$normalizedServer2 = strtolower($this->removeProtocolFromUrl($server2));

		if (rtrim($normalizedServer1, '/') !== rtrim($normalizedServer2, '/')) {
			return false;
		}

		$user1 = $this->userManager->getUserNameFromLoginName($user1);
		$user2 = $this->userManager->getUserNameFromLoginName($user2);
		return $user1 === $user2;
	}

	/**
	 * Remove protocol from URL
	 */
	public function removeProtocolFromUrl(string $url): string {
		if (str_starts_with($url, 'https://')) {
			return substr($url, strlen('https://'));
		} elseif (str_starts_with($url, 'http://')) {
			return substr($url, strlen('http://'));
		}

		return $url;
	}

	/**
	 * Check if the url contain the protocol (http or https).
	 */
	public function urlContainProtocol(string $url): bool {
		return str_starts_with($url, 'https://') || str_starts_with($url, 'http://');
	}
}
