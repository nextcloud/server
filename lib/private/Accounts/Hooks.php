<?php
/**
 * @copyright Copyright (c) 2016 Bjoern Schiessle <bjoern@schiessle.org>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */


namespace OC\Accounts;

use OCP\IUser;

class Hooks {

	/** @var  AccountManager */
	private $accountManager = null;

	/**
	 * update accounts table if email address or display name was changed from outside
	 *
	 * @param array $params
	 */
	public function changeUserHook($params) {

		$this->instantiateAccountManager();

		/** @var IUser $user */
		$user = $params['user'];
		$feature = $params['feature'];
		$newValue = $params['value'];
		$accountData = $this->accountManager->getUser($user);

		switch ($feature) {
			case 'eMailAddress':
				if ($accountData[AccountManager::PROPERTY_EMAIL]['value'] !== $newValue) {
					$accountData[AccountManager::PROPERTY_EMAIL]['value'] = $newValue;
					$this->accountManager->updateUser($user, $accountData);
				}
				break;
			case 'displayName':
				if ($accountData[AccountManager::PROPERTY_DISPLAYNAME]['value'] !== $newValue) {
					$accountData[AccountManager::PROPERTY_DISPLAYNAME]['value'] = $newValue;
					$this->accountManager->updateUser($user, $accountData);
				}
				break;
		}

	}

	/**
	 * return instance of accountManager
	 *
	 * @return AccountManager
	 */
	protected function instantiateAccountManager() {
		if (is_null($this->accountManager)) {
			$this->accountManager = new AccountManager(
				\OC::$server->getDatabaseConnection(),
				\OC::$server->getEventDispatcher()
			);
		}
	}

}
