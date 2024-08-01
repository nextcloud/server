<?php
/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Files_External\Config;

class UserPlaceholderHandler extends UserContext implements IConfigHandler {
	use SimpleSubstitutionTrait;

	/**
	 * @param mixed $optionValue
	 * @return mixed the same type as $optionValue
	 * @since 16.0.0
	 */
	public function handle($optionValue) {
		$this->placeholder = 'user';
		$uid = $this->getUserId();
		if ($uid === null) {
			return $optionValue;
		}
		return $this->processInput($optionValue, $uid);
	}
}
