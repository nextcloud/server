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


import axios from 'nextcloud-axios';

class CollectionService {
	constructor() {
		this.http = axios;
		this.baseUrl = OC.linkToOCS('collaboration/resources', 2);
	}

	listCollection(collectionId) {
		return this.http.get(`${this.baseUrl}collections/${collectionId}`);
	}

	renameCollection(collectionId, collectionName) {
		const resourceBase = OC.linkToOCS('collaboration/resources/collections', 2);
		return this.http.put(`${resourceBase}${collectionId}?format=json`, {
			collectionName
		}).then(result => {
			return result.data.ocs.data;
		});
	}

	getCollectionsByResource(resourceType, resourceId) {
		const resourceBase = OC.linkToOCS(`collaboration/resources/${resourceType}`, 2);
		return this.http.get(`${resourceBase}${resourceId}?format=json`)
			.then(result => {
				return result.data.ocs.data;
			})
			.catch(error => {
				console.error(error);
				return Promise.reject(error);
			});
	}

	createCollection(resourceType, resourceId, name) {
		const resourceBase = OC.linkToOCS(`collaboration/resources/${resourceType}`, 2);
		return this.http.post(`${resourceBase}${resourceId}?format=json`, {
			name: name
		})
			.then((response) => {
				return response.data.ocs.data;
			})
			.catch(error => {
				console.error(error);
				return Promise.reject(error);
			});
	}

	addResource(collectionId, resourceType, resourceId) {
		resourceId = '' + resourceId;
		const resourceBase = OC.linkToOCS('collaboration/resources/collections', 2);
		return this.http.post(`${resourceBase}${collectionId}?format=json`, {
			resourceType,
			resourceId
		}).then((response) => {
			return response.data.ocs.data;
		});
	}

	removeResource(collectionId, resourceType, resourceId) {
		return this.http.delete(`${this.baseUrl}/collections/${collectionId}`, { params: { resourceType, resourceId } } )
			.then((response) => {
				return response.data.ocs.data;
			});
	}

	search(query) {
		const searchBase = OC.linkToOCS('collaboration/resources/collections/search', 2);
		return this.http.get(`${searchBase}%25${query}%25?format=json`)
			.then((response) => {
				return response.data.ocs.data;
			});
	}

}

const service = new CollectionService();

export default service;
