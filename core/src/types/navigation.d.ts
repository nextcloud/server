/*!
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

/** See NavigationManager */
export interface INavigationEntry {
	/** Navigation id */
	id: string
	/** If this is the currently active app */
	active: boolean
	/** Order where this entry should be shown */
	order: number
	/** Target of the navigation entry */
	href: string
	/** The icon used for the naviation entry */
	icon: string
	/** Type of the navigation entry ('link' vs 'settings') */
	type: 'link' | 'settings'
	/** Localized name of the navigation entry */
	name: string
	/** Whether this is the default app */
	default?: boolean
	/** App that registered this navigation entry (not necessarly the same as the id) */
	app?: string
	/** If this app has unread notification */
	unread: number
	/** True when the link should be opened in a new tab */
	target?: boolean
}
