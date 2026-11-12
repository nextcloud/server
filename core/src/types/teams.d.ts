/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

export interface TeamFolder {
	id: number
	mountPoint: string
	quota: number | null
}

export interface SharedResourceProvider {
	id: string
	name: string
}

export interface SharedResource {
	id: string | number
	label: string
	url: string
	iconSvg?: string
	iconURL?: string
	iconEmoji?: string
	provider: SharedResourceProvider
}

export interface TeamAppNavigationAction {
	id: string
	label: string
	/** Emoji or SVG path data for a 24x24 view box, starting with an M/m move command; not SVG markup or a URL. */
	icon?: string
	prompt?: string
}

export interface TeamAppNavigationEntry {
	id: string
	label: string
	/** Emoji or SVG path data for a 24x24 view box, starting with an M/m move command; not SVG markup or a URL. */
	icon?: string
	href?: string
	referenceUrl?: string
	openMode?: 'link' | 'reference' | null
}

export interface TeamAppNavigation {
	entries: TeamAppNavigationEntry[]
	createAction?: TeamAppNavigationAction
}
