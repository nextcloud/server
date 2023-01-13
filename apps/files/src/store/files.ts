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
import type { Folder, Node } from '@nextcloud/files'
import Vue from 'vue'
import type { FileStore, RootStore, RootOptions, Service } from '../types'

const state = {
	files: {} as FileStore,
	roots: {} as RootStore,
}

const getters = {
	/**
	 * Get a file or folder by id
	 */
	getNode: (state)  => (id: number): Node|undefined => state.files[id],

	/**
	 * Get a list of files or folders by their IDs
	 * Does not return undefined values
	 */
	getNodes: (state) => (ids: number[]): Node[] => ids
		.map(id => state.files[id])
		.filter(Boolean),
	/**
	 * Get a file or folder by id
	 */
	getRoot: (state)  => (service: Service): Folder|undefined => state.roots[service],
}

const mutations = {
	updateNodes: (state, nodes: Node[]) => {
		nodes.forEach(node => {
			if (!node.attributes.fileid) {
				return
			}
			Vue.set(state.files, node.attributes.fileid, node)
			// state.files = {
			// 	...state.files,
			// 	[node.attributes.fileid]: node,
			// }
		})
	},

	setRoot: (state, { service, root }: RootOptions) => {
		state.roots = {
			...state.roots,
			[service]: root,
		}
	}
}

const actions = {
	/**
	 * Insert valid nodes into the store.
	 * Roots (that does _not_ have a fileid) should
	 * be defined in the roots store
	 */
	addNodes: (context, nodes: Node[]) => {
		context.commit('updateNodes', nodes)
	},

	/**
	 * Set the root of a service
	 */
	setRoot(context, { service, root }: RootOptions) {
		context.commit('setRoot', { service, root })
	}
}

export default {
	namespaced: true,
	state,
	getters,
	mutations,
	actions,
}
