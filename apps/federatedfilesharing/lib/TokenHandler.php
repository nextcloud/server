<?php

/**
 * SPDX-FileCopyrightText: 2019-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\FederatedFileSharing;

use OCP\Security\ISecureRandom;

/**
 * Class TokenHandler
 *
 * @package OCA\FederatedFileSharing
 */
class TokenHandler {
	public const TOKEN_LENGTH = 15;

	/**
	 * TokenHandler constructor.
	 *
	 * @param ISecureRandom $secureRandom
	 */
	public function __construct(
		private ISecureRandom $secureRandom,
	) {
	}

	/**
	 * generate to token used to authenticate federated shares
	 *
	 * @return string
	 */
	public function generateToken() {
		$token = $this->secureRandom->generate(
			self::TOKEN_LENGTH,
			ISecureRandom::CHAR_ALPHANUMERIC);
		return $token;
	}
}
