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
	ADDRESS: 'address',
	AVATAR: 'avatar',
	DISPLAYNAME: 'displayname',
	EMAIL: 'email',
	EMAIL_COLLECTION: 'additional_mail',
	PHONE: 'phone',
	TWITTER: 'twitter',
	WEBSITE: 'website',
})

/** Enum of account properties to human readable account properties */
export const ACCOUNT_PROPERTY_READABLE_ENUM = Object.freeze({
	ADDRESS: 'Address',
	AVATAR: 'Avatar',
	DISPLAYNAME: 'Full name',
	EMAIL: 'Email',
	EMAIL_COLLECTION: 'Additional Email',
	PHONE: 'Phone',
	TWITTER: 'Twitter',
	WEBSITE: 'Website',
})

/** Enum of scopes */
export const SCOPE_ENUM = Object.freeze({
	LOCAL: 'v2-local',
	PRIVATE: 'v2-private',
	FEDERATED: 'v2-federated',
	PUBLISHED: 'v2-published',
})

/** Enum of readable account properties to supported scopes */
export const PROPERTY_READABLE_SUPPORTED_SCOPES_ENUM = Object.freeze({
	[ACCOUNT_PROPERTY_READABLE_ENUM.ADDRESS]: [SCOPE_ENUM.LOCAL, SCOPE_ENUM.PRIVATE],
	[ACCOUNT_PROPERTY_READABLE_ENUM.AVATAR]: [SCOPE_ENUM.LOCAL, SCOPE_ENUM.PRIVATE],
	[ACCOUNT_PROPERTY_READABLE_ENUM.DISPLAYNAME]: [SCOPE_ENUM.LOCAL],
	[ACCOUNT_PROPERTY_READABLE_ENUM.EMAIL]: [SCOPE_ENUM.LOCAL],
	[ACCOUNT_PROPERTY_READABLE_ENUM.EMAIL_COLLECTION]: [SCOPE_ENUM.LOCAL],
	[ACCOUNT_PROPERTY_READABLE_ENUM.PHONE]: [SCOPE_ENUM.LOCAL, SCOPE_ENUM.PRIVATE],
	[ACCOUNT_PROPERTY_READABLE_ENUM.TWITTER]: [SCOPE_ENUM.LOCAL, SCOPE_ENUM.PRIVATE],
	[ACCOUNT_PROPERTY_READABLE_ENUM.WEBSITE]: [SCOPE_ENUM.LOCAL, SCOPE_ENUM.PRIVATE],
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
	[SCOPE_ENUM.LOCAL]: {
		name: SCOPE_ENUM.LOCAL,
		displayName: t('settings', 'Local'),
		tooltip: t('settings', 'Only visible to people on this instance and guests'),
		iconClass: 'icon-password',
	},
	[SCOPE_ENUM.PRIVATE]: {
		name: SCOPE_ENUM.PRIVATE,
		displayName: t('settings', 'Private'),
		tooltip: t('settings', 'Only visible to people matched via phone number integration through Talk on mobile'),
		iconClass: 'icon-phone',
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
