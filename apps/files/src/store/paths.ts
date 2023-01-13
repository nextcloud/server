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
/* eslint-disable */
import type { Folder } from '@nextcloud/files'
import Vue from 'vue'
import type { PathOptions, ServicePaths, ServiceStore } from '../types'

const module = {
	state: {
		services: {
			files: {} as ServicePaths,
		} as ServiceStore,
	},

	getters: {
		getPath(state: { services: ServiceStore }) {
			return (service: string, path: string): number|undefined => {
				if (!state.services[service]) {
					return undefined
				}
				return state.services[service][path]
			}
		},
	},

	mutations: {
		addPath: (state, opts: PathOptions) => {
			// If it doesn't exists, init the service state
			if (!state.services[opts.service]) {
				// TODO: investigate why Vue.set is not working
				state.services = {
					[opts.service]: {} as ServicePaths,
					...state.services
				}
			}

			// Now we can set the path
			Vue.set(state.services[opts.service], opts.path,  opts.fileid)
		}
	},

	actions: {
		addPath: (context, opts: PathOptions) => {
			context.commit('addPath', opts)
		},
	}
}

export default {
	namespaced: true,
	...module,
}
