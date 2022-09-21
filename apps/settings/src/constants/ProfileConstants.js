/**
 * @copyright 2021 Christopher Ng <chrng8@gmail.com>
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
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
