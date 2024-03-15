/**
 * @copyright Copyright (c) 2024 Ferdinand Thiessen <opensource@fthiessen.de>
 *
 * @author Ferdinand Thiessen <opensource@fthiessen.de>
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
	releases: IAppstoreAppRelease[]
}
