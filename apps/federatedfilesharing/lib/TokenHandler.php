<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Björn Schießle <bjoern@schiessle.org>
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program. If not, see <http://www.gnu.org/licenses/>
 *
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

	/** @var ISecureRandom */
	private $secureRandom;

	/**
	 * TokenHandler constructor.
	 *
	 * @param ISecureRandom $secureRandom
	 */
	public function __construct(ISecureRandom $secureRandom) {
		$this->secureRandom = $secureRandom;
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
