/**
 * @copyright 2021, Christopher Ng <chrng8@gmail.com>
 *
 * @author Christopher Ng <chrng8@gmail.com>
 *
 * @license AGPL-3.0-or-later
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
 * SYNC to be kept in sync with `lib/public/Accounts/IAccountManager.php`
 */

import { translate as t } from '@nextcloud/l10n'

/** Enum of account properties */
export const ACCOUNT_PROPERTY_ENUM = Object.freeze({
	ADDRESS: 'address',
	AVATAR: 'avatar',
	BIOGRAPHY: 'biography',
	DISPLAYNAME: 'displayname',
	EMAIL_COLLECTION: 'additional_mail',
	EMAIL: 'email',
	HEADLINE: 'headline',
	NOTIFICATION_EMAIL: 'notify_email',
	ORGANISATION: 'organisation',
	PHONE: 'phone',
	PROFILE_ENABLED: 'profile_enabled',
	ROLE: 'role',
	TWITTER: 'twitter',
	WEBSITE: 'website',
})

/** Enum of account properties to human readable account property names */
export const ACCOUNT_PROPERTY_READABLE_ENUM = Object.freeze({
	ADDRESS: t('settings', 'Location'),
	AVATAR: t('settings', 'Profile picture'),
	BIOGRAPHY: t('settings', 'About'),
	DISPLAYNAME: t('settings', 'Full name'),
	EMAIL_COLLECTION: t('settings', 'Additional email'),
	EMAIL: t('settings', 'Email'),
	HEADLINE: t('settings', 'Headline'),
	ORGANISATION: t('settings', 'Organisation'),
	PHONE: t('settings', 'Phone number'),
	PROFILE_ENABLED: t('settings', 'Profile'),
	ROLE: t('settings', 'Role'),
	TWITTER: t('settings', 'Twitter'),
	WEBSITE: t('settings', 'Website'),
})

export const NAME_READABLE_ENUM = Object.freeze({
	[ACCOUNT_PROPERTY_ENUM.ADDRESS]: ACCOUNT_PROPERTY_READABLE_ENUM.ADDRESS,
	[ACCOUNT_PROPERTY_ENUM.AVATAR]: ACCOUNT_PROPERTY_READABLE_ENUM.AVATAR,
	[ACCOUNT_PROPERTY_ENUM.BIOGRAPHY]: ACCOUNT_PROPERTY_READABLE_ENUM.BIOGRAPHY,
	[ACCOUNT_PROPERTY_ENUM.DISPLAYNAME]: ACCOUNT_PROPERTY_READABLE_ENUM.DISPLAYNAME,
	[ACCOUNT_PROPERTY_ENUM.EMAIL_COLLECTION]: ACCOUNT_PROPERTY_READABLE_ENUM.EMAIL_COLLECTION,
	[ACCOUNT_PROPERTY_ENUM.EMAIL]: ACCOUNT_PROPERTY_READABLE_ENUM.EMAIL,
	[ACCOUNT_PROPERTY_ENUM.HEADLINE]: ACCOUNT_PROPERTY_READABLE_ENUM.HEADLINE,
	[ACCOUNT_PROPERTY_ENUM.ORGANISATION]: ACCOUNT_PROPERTY_READABLE_ENUM.ORGANISATION,
	[ACCOUNT_PROPERTY_ENUM.PHONE]: ACCOUNT_PROPERTY_READABLE_ENUM.PHONE,
	[ACCOUNT_PROPERTY_ENUM.PROFILE_ENABLED]: ACCOUNT_PROPERTY_READABLE_ENUM.PROFILE_ENABLED,
	[ACCOUNT_PROPERTY_ENUM.ROLE]: ACCOUNT_PROPERTY_READABLE_ENUM.ROLE,
	[ACCOUNT_PROPERTY_ENUM.TWITTER]: ACCOUNT_PROPERTY_READABLE_ENUM.TWITTER,
	[ACCOUNT_PROPERTY_ENUM.WEBSITE]: ACCOUNT_PROPERTY_READABLE_ENUM.WEBSITE,
})

/** Enum of profile specific sections to human readable names */
export const PROFILE_READABLE_ENUM = Object.freeze({
	PROFILE_VISIBILITY: t('settings', 'Profile visibility'),
})

/** Enum of readable account properties to account property keys used by the server */
export const PROPERTY_READABLE_KEYS_ENUM = Object.freeze({
	[ACCOUNT_PROPERTY_READABLE_ENUM.ADDRESS]: ACCOUNT_PROPERTY_ENUM.ADDRESS,
	[ACCOUNT_PROPERTY_READABLE_ENUM.AVATAR]: ACCOUNT_PROPERTY_ENUM.AVATAR,
	[ACCOUNT_PROPERTY_READABLE_ENUM.BIOGRAPHY]: ACCOUNT_PROPERTY_ENUM.BIOGRAPHY,
	[ACCOUNT_PROPERTY_READABLE_ENUM.DISPLAYNAME]: ACCOUNT_PROPERTY_ENUM.DISPLAYNAME,
	[ACCOUNT_PROPERTY_READABLE_ENUM.EMAIL_COLLECTION]: ACCOUNT_PROPERTY_ENUM.EMAIL_COLLECTION,
	[ACCOUNT_PROPERTY_READABLE_ENUM.EMAIL]: ACCOUNT_PROPERTY_ENUM.EMAIL,
	[ACCOUNT_PROPERTY_READABLE_ENUM.HEADLINE]: ACCOUNT_PROPERTY_ENUM.HEADLINE,
	[ACCOUNT_PROPERTY_READABLE_ENUM.ORGANISATION]: ACCOUNT_PROPERTY_ENUM.ORGANISATION,
	[ACCOUNT_PROPERTY_READABLE_ENUM.PHONE]: ACCOUNT_PROPERTY_ENUM.PHONE,
	[ACCOUNT_PROPERTY_READABLE_ENUM.PROFILE_ENABLED]: ACCOUNT_PROPERTY_ENUM.PROFILE_ENABLED,
	[ACCOUNT_PROPERTY_READABLE_ENUM.ROLE]: ACCOUNT_PROPERTY_ENUM.ROLE,
	[ACCOUNT_PROPERTY_READABLE_ENUM.TWITTER]: ACCOUNT_PROPERTY_ENUM.TWITTER,
	[ACCOUNT_PROPERTY_READABLE_ENUM.WEBSITE]: ACCOUNT_PROPERTY_ENUM.WEBSITE,
})

/**
 * Enum of account setting properties
 *
 * Account setting properties unlike account properties do not support scopes*
 */
export const ACCOUNT_SETTING_PROPERTY_ENUM = Object.freeze({
	LANGUAGE: 'language',
	LOCALE: 'locale',
})

/** Enum of account setting properties to human readable setting properties */
export const ACCOUNT_SETTING_PROPERTY_READABLE_ENUM = Object.freeze({
	LANGUAGE: t('settings', 'Language'),
	LOCALE: t('settings', 'Locale'),
})

/** Enum of scopes */
export const SCOPE_ENUM = Object.freeze({
	PRIVATE: 'v2-private',
	LOCAL: 'v2-local',
	FEDERATED: 'v2-federated',
	PUBLISHED: 'v2-published',
})

/** Enum of readable account properties to supported scopes */
export const PROPERTY_READABLE_SUPPORTED_SCOPES_ENUM = Object.freeze({
	[ACCOUNT_PROPERTY_READABLE_ENUM.ADDRESS]: [SCOPE_ENUM.LOCAL, SCOPE_ENUM.PRIVATE],
	[ACCOUNT_PROPERTY_READABLE_ENUM.AVATAR]: [SCOPE_ENUM.LOCAL, SCOPE_ENUM.PRIVATE],
	[ACCOUNT_PROPERTY_READABLE_ENUM.BIOGRAPHY]: [SCOPE_ENUM.LOCAL, SCOPE_ENUM.PRIVATE],
	[ACCOUNT_PROPERTY_READABLE_ENUM.DISPLAYNAME]: [SCOPE_ENUM.LOCAL],
	[ACCOUNT_PROPERTY_READABLE_ENUM.EMAIL_COLLECTION]: [SCOPE_ENUM.LOCAL],
	[ACCOUNT_PROPERTY_READABLE_ENUM.EMAIL]: [SCOPE_ENUM.LOCAL],
	[ACCOUNT_PROPERTY_READABLE_ENUM.HEADLINE]: [SCOPE_ENUM.LOCAL, SCOPE_ENUM.PRIVATE],
	[ACCOUNT_PROPERTY_READABLE_ENUM.ORGANISATION]: [SCOPE_ENUM.LOCAL, SCOPE_ENUM.PRIVATE],
	[ACCOUNT_PROPERTY_READABLE_ENUM.PHONE]: [SCOPE_ENUM.LOCAL, SCOPE_ENUM.PRIVATE],
	[ACCOUNT_PROPERTY_READABLE_ENUM.PROFILE_ENABLED]: [SCOPE_ENUM.LOCAL, SCOPE_ENUM.PRIVATE],
	[ACCOUNT_PROPERTY_READABLE_ENUM.ROLE]: [SCOPE_ENUM.LOCAL, SCOPE_ENUM.PRIVATE],
	[ACCOUNT_PROPERTY_READABLE_ENUM.TWITTER]: [SCOPE_ENUM.LOCAL, SCOPE_ENUM.PRIVATE],
	[ACCOUNT_PROPERTY_READABLE_ENUM.WEBSITE]: [SCOPE_ENUM.LOCAL, SCOPE_ENUM.PRIVATE],
})

/** List of readable account properties which aren't published to the lookup server */
export const UNPUBLISHED_READABLE_PROPERTIES = Object.freeze([
	ACCOUNT_PROPERTY_READABLE_ENUM.BIOGRAPHY,
	ACCOUNT_PROPERTY_READABLE_ENUM.HEADLINE,
	ACCOUNT_PROPERTY_READABLE_ENUM.ORGANISATION,
	ACCOUNT_PROPERTY_READABLE_ENUM.ROLE,
])

/** Scope suffix */
export const SCOPE_SUFFIX = 'Scope'

/**
 * Enum of scope names to properties
 *
 * Used for federation control*
 */
export const SCOPE_PROPERTY_ENUM = Object.freeze({
	[SCOPE_ENUM.PRIVATE]: {
		name: SCOPE_ENUM.PRIVATE,
		displayName: t('settings', 'Private'),
		tooltip: t('settings', 'Only visible to people matched via phone number integration through Talk on mobile'),
		tooltipDisabled: t('settings', 'Not available as this property is required for core functionality including file sharing and calendar invitations'),
		iconClass: 'icon-phone',
	},
	[SCOPE_ENUM.LOCAL]: {
		name: SCOPE_ENUM.LOCAL,
		displayName: t('settings', 'Local'),
		tooltip: t('settings', 'Only visible to people on this instance and guests'),
		// tooltipDisabled is not required here as this scope is supported by all account properties
		iconClass: 'icon-password',
	},
	[SCOPE_ENUM.FEDERATED]: {
		name: SCOPE_ENUM.FEDERATED,
		displayName: t('settings', 'Federated'),
		tooltip: t('settings', 'Only synchronize to trusted servers'),
		tooltipDisabled: t('settings', 'Not available as publishing account specific data to the lookup server is not allowed, contact your system administrator if you have any questions'),
		iconClass: 'icon-contacts-dark',
	},
	[SCOPE_ENUM.PUBLISHED]: {
		name: SCOPE_ENUM.PUBLISHED,
		displayName: t('settings', 'Published'),
		tooltip: t('settings', 'Synchronize to trusted servers and the global and public address book'),
		tooltipDisabled: t('settings', 'Not available as publishing account specific data to the lookup server is not allowed, contact your system administrator if you have any questions'),
		iconClass: 'icon-link',
	},
})

/** Default additional email scope */
export const DEFAULT_ADDITIONAL_EMAIL_SCOPE = SCOPE_ENUM.LOCAL

/** Enum of verification constants, according to IAccountManager */
export const VERIFICATION_ENUM = Object.freeze({
	NOT_VERIFIED: 0,
	VERIFICATION_IN_PROGRESS: 1,
	VERIFIED: 2,
})

/**
 * Email validation regex
 *
 * Sourced from https://github.com/mpyw/FILTER_VALIDATE_EMAIL.js/blob/71e62ca48841d2246a1b531e7e84f5a01f15e615/src/regexp/ascii.ts*
 */
// eslint-disable-next-line no-control-regex
export const VALIDATE_EMAIL_REGEX = /^(?!(?:(?:\x22?\x5C[\x00-\x7E]\x22?)|(?:\x22?[^\x5C\x22]\x22?)){255,})(?!(?:(?:\x22?\x5C[\x00-\x7E]\x22?)|(?:\x22?[^\x5C\x22]\x22?)){65,}@)(?:(?:[\x21\x23-\x27\x2A\x2B\x2D\x2F-\x39\x3D\x3F\x5E-\x7E]+)|(?:\x22(?:[\x01-\x08\x0B\x0C\x0E-\x1F\x21\x23-\x5B\x5D-\x7F]|(?:\x5C[\x00-\x7F]))*\x22))(?:\.(?:(?:[\x21\x23-\x27\x2A\x2B\x2D\x2F-\x39\x3D\x3F\x5E-\x7E]+)|(?:\x22(?:[\x01-\x08\x0B\x0C\x0E-\x1F\x21\x23-\x5B\x5D-\x7F]|(?:\x5C[\x00-\x7F]))*\x22)))*@(?:(?:(?!.*[^.]{64,})(?:(?:(?:xn--)?[a-z0-9]+(?:-+[a-z0-9]+)*\.){1,126}){1,}(?:(?:[a-z][a-z0-9]*)|(?:(?:xn--)[a-z0-9]+))(?:-+[a-z0-9]+)*)|(?:\[(?:(?:IPv6:(?:(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){7})|(?:(?!(?:.*[a-f0-9][:\]]){7,})(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){0,5})?::(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){0,5})?)))|(?:(?:IPv6:(?:(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){5}:)|(?:(?!(?:.*[a-f0-9]:){5,})(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){0,3})?::(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){0,3}:)?)))?(?:(?:25[0-5])|(?:2[0-4][0-9])|(?:1[0-9]{2})|(?:[1-9]?[0-9]))(?:\.(?:(?:25[0-5])|(?:2[0-4][0-9])|(?:1[0-9]{2})|(?:[1-9]?[0-9]))){3}))\]))$/i
