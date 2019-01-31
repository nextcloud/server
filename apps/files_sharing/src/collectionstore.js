/*
 * @copyright Copyright (c) 2019 Julius Härtl <jus@bitgrid.net>
 *
 * @author Julius Härtl <jus@bitgrid.net>
 *
 * @license GNU AGPL version 3 or any later version
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

import Vuex from 'vuex';
import Vue from 'vue';
import service from './collectionservice';

const CollectionStoreModule = {
	state: {
		collections: []
	},
	mutations: {
		addCollections (state, collections) {
			state.collections = collections;
		},
		addCollection (state, collection) {
			state.collections.push(collection);
		},
		removeCollection (state, collectionId) {
			state.collections = state.collections.filter(item => item.id !== collectionId);
		},
		updateCollection(state, collection) {
			let index = state.collections.findIndex((_item) => _item.id === collection.id);
			if (index !== -1) {
				Vue.set(state.collections, index, collection);
			} else {
				state.collections.push(collection);
			}
		}
	},
	getters: {
		collectionsByResource(state) {
			return (resourceType, resourceId) => {
				return state.collections.filter((collection) => {
					return typeof collection.resources.find((resource) => resource && resource.id === ''+resourceId && resource.type === resourceType) !== 'undefined';
				});
			};
		},
		getSearchResults(state) {
			return (term) => {
				return state.collections.filter((collection) => collection.name.contains(term));
			};
		}
	},
	actions: {
		fetchCollectionsByResource(context, {resourceType, resourceId}) {
			return service.getCollectionsByResource(resourceType, resourceId).then((collections) => {
				context.commit('addCollections', collections);
				return collections;
			});
		},
		createCollection(context, {baseResourceType, baseResourceId, resourceType, resourceId, name}) {
			return service.createCollection(baseResourceType, baseResourceId, name).then((collection) => {
				context.commit('addCollection', collection);
				context.dispatch('addResourceToCollection', {
					collectionId: collection.id,
					resourceType, resourceId
				});
			});
		},
		renameCollection(context, {collectionId, name}) {
			return service.renameCollection(collectionId, name).then((collection) => {
				context.commit('updateCollection', collection);
				return collection;
			});
		},
		addResourceToCollection(context, {collectionId, resourceType, resourceId}) {
			return service.addResource(collectionId, resourceType, resourceId).then((collection) => {
				context.commit('updateCollection', collection);
				return collection;
			});
		},
		removeResource(context, {collectionId, resourceType, resourceId}) {
			return service.removeResource(collectionId, resourceType, resourceId).then((collection) => {
				if (collection.resources.length > 0) {
					context.commit('updateCollection', collection);
				} else {
					context.commit('removeCollection', collectionId);
				}
			});
		},
		search(context, query) {
			return service.search(query);
		}
	}
};

const CollectionStore = () => new Vuex.Store(CollectionStoreModule);

export { CollectionStoreModule, CollectionStore };
