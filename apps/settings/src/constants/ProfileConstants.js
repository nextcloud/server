/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

/*
 * SYNC to be kept in sync with `core/Db/ProfileConfig.php`
 */

/** Enum of profile visibility constants */
export const VISIBILITY_ENUM = Object.freeze({
	SHOW: 'show',
	SHOW_USERS_ONLY: 'show_users_only',
	HIDE: 'hide',
})

/**
 * Enum of profile visibility constants to properties
 */
export const VISIBILITY_PROPERTY_ENUM = Object.freeze({
	[VISIBILITY_ENUM.SHOW]: {
		name: VISIBILITY_ENUM.SHOW,
		label: t('settings', 'Show to everyone'),
	},
	[VISIBILITY_ENUM.SHOW_USERS_ONLY]: {
		name: VISIBILITY_ENUM.SHOW_USERS_ONLY,
		label: t('settings', 'Show to logged in accounts only'),
	},
	[VISIBILITY_ENUM.HIDE]: {
		name: VISIBILITY_ENUM.HIDE,
		label: t('settings', 'Hide'),
	},
})
