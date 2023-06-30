/**
 * @copyright Copyright (c) 2022 John Molakvoæ <skjnldsv@protonmail.com>
 *
 * @author John Molakvoæ <skjnldsv@protonmail.com>
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
/* eslint-disable */
import type { Folder, Node } from '@nextcloud/files'
import isSvg from 'is-svg'

import logger from '../logger.js'

export type ContentsWithRoot = {
	folder: Folder,
	contents: Node[]
}

export interface Column {
	/** Unique column ID */
	id: string
	/** Translated column title */
	title: string
	/** The content of the cell. The element will be appended within */
	render: (node: Node, view: Navigation) => HTMLElement
	/** Function used to sort Nodes between them */
	sort?: (nodeA: Node, nodeB: Node) => number
	/** Custom summary of the column to display at the end of the list.
	 Will not be displayed if  nothing is provided */
	summary?: (node: Node[], view: Navigation) => string
}

export interface Navigation {
	/** Unique view ID */
	id: string
	/** Translated view name */
	name: string
	/** Translated view accessible description */
	caption?: string
	/**
	 * Method return the content of the  provided path
	 * This ideally should be a cancellable promise.
	 * promise.cancel(reason) will be called when the directory
	 * change and the promise is not resolved yet.
	 * You _must_ also return the current directory
	 * information alongside with its content.
	 */
	getContents: (path: string) => Promise<ContentsWithRoot>
	/** The view icon as an inline svg */
	icon: string
	/** The view order */
	order: number
	/** This view column(s). Name and actions are
	by default always included */
	columns?: Column[]
	/** The empty view element to render your empty content into */
	emptyView?: (div: HTMLDivElement) => void
	/** The parent unique ID */
	parent?: string
	/** This view is sticky (sent at the bottom) */
	sticky?: boolean
	/** This view has children and is expanded or not,
	 * will be overridden by user config.
	 */
	expanded?: boolean

	/**
	 * Will be used as default if the user
	 * haven't customized their sorting column
	 * */
	defaultSortKey?: string

	/**
	 * This view is sticky a legacy view.
	 * Here until all the views are migrated to Vue.
	 * @deprecated It will be removed in a near future
	 */
	legacy?: boolean
	/**
	 * An icon class. 
	 * @deprecated It will be removed in a near future
	 */
	iconClass?: string
}

export default class {

	private _views: Navigation[] = []
	private _currentView: Navigation | null = null

	constructor() {
		logger.debug('Navigation service initialized')
	}

	register(view: Navigation) {
		try {
			isValidNavigation(view)
			isUniqueNavigation(view, this._views)
		} catch (e) {
			if (e instanceof Error) {
				logger.error(e.message, { view })
			}
			throw e
		}

		if (view.legacy) {
			logger.warn('Legacy view detected, please migrate to Vue')
		}

		if (view.iconClass) {
			view.legacy = true
		}

		this._views.push(view)
	}

	remove(id: string) {
		const index = this._views.findIndex(view => view.id === id)
		if (index !== -1) {
			this._views.splice(index, 1)
		}
	}

	get views(): Navigation[] {
		return this._views
	}

	setActive(view: Navigation | null) {
		this._currentView = view
	}

	get active(): Navigation | null {
		return this._currentView
	}

}

/**
 * Make sure the given view is unique
 * and not already registered.
 */
const isUniqueNavigation = function(view: Navigation, views: Navigation[]): boolean {
	if (views.find(search => search.id === view.id)) {
		throw new Error(`Navigation id ${view.id} is already registered`)
	}
	return true
}

/**
 * Typescript cannot validate an interface.
 * Please keep in sync with the Navigation interface requirements.
 */
const isValidNavigation = function(view: Navigation): boolean {
	if (!view.id || typeof view.id !== 'string') {
		throw new Error('Navigation id is required and must be a string')
	}

	if (!view.name || typeof view.name !== 'string') {
		throw new Error('Navigation name is required and must be a string')
	}

	if (view.columns && view.columns.length > 0
		&& (!view.caption || typeof view.caption !== 'string')) {
		throw new Error('Navigation caption is required for top-level views and must be a string')
	}

	/**
	 * Legacy handle their content and icon differently
	 * TODO: remove when support for legacy views is removed
	 */
	if (!view.legacy) {
		if (!view.getContents || typeof view.getContents !== 'function') {
			throw new Error('Navigation getContents is required and must be a function')
		}

		if (!view.icon || typeof view.icon !== 'string' || !isSvg(view.icon)) {
			throw new Error('Navigation icon is required and must be a valid svg string')
		}
	}

	if (!('order' in view) || typeof view.order !== 'number') {
		throw new Error('Navigation order is required and must be a number')
	}

	// Optional properties
	if (view.columns) {
		view.columns.forEach(isValidColumn)
	}

	if (view.emptyView && typeof view.emptyView !== 'function') {
		throw new Error('Navigation emptyView must be a function')
	}

	if (view.parent && typeof view.parent !== 'string') {
		throw new Error('Navigation parent must be a string')
	}

	if ('sticky' in view && typeof view.sticky !== 'boolean') {
		throw new Error('Navigation sticky must be a boolean')
	}

	if ('expanded' in view && typeof view.expanded !== 'boolean') {
		throw new Error('Navigation expanded must be a boolean')
	}

	if (view.defaultSortKey && typeof view.defaultSortKey !== 'string') {
		throw new Error('Navigation defaultSortKey must be a string')
	}

	return true
}

/**
 * Typescript cannot validate an interface.
 * Please keep in sync with the Column interface requirements.
 */
const isValidColumn = function(column: Column): boolean {
	if (!column.id || typeof column.id !== 'string') {
		throw new Error('A column id is required')
	}

	if (!column.title || typeof column.title !== 'string') {
		throw new Error('A column title is required')
	}

	if (!column.render || typeof column.render !== 'function') {
		throw new Error('A render function is required')
	}

	// Optional properties
	if (column.sort && typeof column.sort !== 'function') {
		throw new Error('Column sortFunction must be a function')
	}

	if (column.summary && typeof column.summary !== 'function') {
		throw new Error('Column summary must be a function')
	}

	return true
}
