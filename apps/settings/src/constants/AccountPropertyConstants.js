/**
 * @copyright 2021, Christopher Ng <chrng8@gmail.com>
 *
 * @author Christopher Ng <chrng8@gmail.com>
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

/*
 * SYNC to be kept in sync with lib/public/Accounts/IAccountManager.php
 */

/** Enum of account properties */
export const ACCOUNT_PROPERTY_ENUM = Object.freeze({
	AVATAR: 'avatar',
	DISPLAYNAME: 'displayname',
	PHONE: 'phone',
	EMAIL: 'email',
	WEBSITE: 'website',
	ADDRESS: 'address',
	TWITTER: 'twitter',
	EMAIL_COLLECTION: 'additional_mail',
})

/** Enum of scopes */
export const SCOPE_ENUM = Object.freeze({
	PRIVATE: 'v2-private',
	LOCAL: 'v2-local',
	FEDERATED: 'v2-federated',
	PUBLISHED: 'v2-published',
})

/** Scope suffix */
export const SCOPE_SUFFIX = 'Scope'

/** Default additional email scope */
export const DEFAULT_ADDITIONAL_EMAIL_SCOPE = SCOPE_ENUM.LOCAL

/**
 * Enum of scope names to properties
 *
 * *Used for federation control*
 */
export const SCOPE_PROPERTY_ENUM = Object.freeze({
	[SCOPE_ENUM.PRIVATE]: {
		name: SCOPE_ENUM.PRIVATE,
		displayName: t('settings', 'Private'),
		tooltip: t('settings', 'Only visible to people matched via phone number integration through Talk on mobile'),
		iconClass: 'icon-phone',
	},
	[SCOPE_ENUM.LOCAL]: {
		name: SCOPE_ENUM.LOCAL,
		displayName: t('settings', 'Local'),
		tooltip: t('settings', 'Only visible to people on this instance and guests'),
		iconClass: 'icon-password',
	},
	[SCOPE_ENUM.FEDERATED]: {
		name: SCOPE_ENUM.FEDERATED,
		displayName: t('settings', 'Federated'),
		tooltip: t('settings', 'Only synchronize to trusted servers'),
		iconClass: 'icon-contacts-dark',
	},
	[SCOPE_ENUM.PUBLISHED]: {
		name: SCOPE_ENUM.PUBLISHED,
		displayName: t('settings', 'Published'),
		tooltip: t('settings', 'Synchronize to trusted servers and the global and public address book'),
		iconClass: 'icon-link',
	},
})
