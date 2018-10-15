<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2018 Julius Härtl <jus@bitgrid.net>
 *
 * @author Julius Härtl <jus@bitgrid.net>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCP\Accounts;

use OCP\IUser;

/**
 * Access user profile information
 *
 * @since 15.0.0
 *
 * @package OCP\Accounts
 */
interface IAccountManager {

	/** nobody can see my account details */
	const VISIBILITY_PRIVATE = 'private';
	/** only contacts, especially trusted servers can see my contact details */
	const VISIBILITY_CONTACTS_ONLY = 'contacts';
	/** every body ca see my contact detail, will be published to the lookup server */
	const VISIBILITY_PUBLIC = 'public';

	const PROPERTY_AVATAR = 'avatar';
	const PROPERTY_DISPLAYNAME = 'displayname';
	const PROPERTY_PHONE = 'phone';
	const PROPERTY_EMAIL = 'email';
	const PROPERTY_WEBSITE = 'website';
	const PROPERTY_ADDRESS = 'address';
	const PROPERTY_TWITTER = 'twitter';

	const NOT_VERIFIED = '0';
	const VERIFICATION_IN_PROGRESS = '1';
	const VERIFIED = '2';

	/**
	 * Get the account data for a given user
	 *
	 * @since 15.0.0
	 *
	 * @param IUser $user
	 * @return IAccount
	 */
	public function getAccount(IUser $user): IAccount;

}
