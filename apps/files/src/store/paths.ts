/**
 * @copyright Copyright (c) 2023 John Molakvoæ <skjnldsv@protonmail.com>
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
import type { FileId, PathsStore, PathOptions, ServicesState } from '../types'
import { defineStore } from 'pinia'
import Vue from 'vue'

export const usePathsStore = function(...args) {
	const store = defineStore('paths', {
		state: () => ({
			paths: {} as ServicesState,
		} as PathsStore),

		getters: {
			getPath: (state) => {
				return (service: string, path: string): FileId|undefined => {
					if (!state.paths[service]) {
						return undefined
					}
					return state.paths[service][path]
				}
			},
		},

		actions: {
			addPath(payload: PathOptions) {
				// If it doesn't exists, init the service state
				if (!this.paths[payload.service]) {
					Vue.set(this.paths, payload.service, {})
				}

				// Now we can set the provided path
				Vue.set(this.paths[payload.service], payload.path, payload.fileid)
			},
		},
	})

	const pathsStore = store(...args)
	// Make sure we only register the listeners once
	if (!pathsStore._initialized) {
		// TODO: watch folders to update paths?
		// subscribe('files:node:created', pathsStore.onCreatedNode)
		// subscribe('files:node:deleted', pathsStore.onDeletedNode)
		// subscribe('files:node:moved', pathsStore.onMovedNode)

		pathsStore._initialized = true
	}

	return pathsStore
}
