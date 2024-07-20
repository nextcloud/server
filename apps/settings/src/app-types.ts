/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

export interface IAppstoreCategory {
	/**
	 * The category ID
	 */
	id: string
	/**
	 * The display name (can be localized)
	 */
	displayName: string
	/**
	 * Inline SVG path
	 */
	icon: string
}

export interface IAppstoreAppRelease {
	version: string
	translations: {
		[key: string]: {
			changelog: string
		}
	}
}

export interface IAppstoreApp {
	id: string
	name: string
	summary: string
	description: string
	licence: string
	author: string[] | Record<string, string>
	level: number
	version: string
	category: string|string[]

	preview?: string
	screenshot?: string

	active: boolean
	internal: boolean
	removeable: boolean
	installed: boolean
	canInstall: boolean
	canUninstall: boolean
	isCompatible: boolean

	appstoreData: Record<string, never>
	releases?: IAppstoreAppRelease[]
}
