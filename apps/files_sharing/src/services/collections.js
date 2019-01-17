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

class Service {
	constructor() {
		this.service = axios.create();
	}

	listCollection(collectionId) {
		return this.service.get(`/collaboration/resources/collections/${collectionId}`)
	}

	addResource(collectionId, resource) {
		return this.service.post(`/collaboration/resources/collections/${collectionId}`)
	}

	removeResource() {
		return this.service.post(`/collaboration/resources/collections/${collectionId}`)
	}

	createCollectionOnResource(resourceType, resourceId) {
		return this.service.post(`/collaboration/resources/${resourceType}/${resourceId}`)
	}

	getCollectionByResource(resourceType, resourceId) {
		return this.service.get(`/collaboration/resources/${resourceType}/${resourceId}`)
	}

	getProviders() {

	}

	search() {

	}
}

export default new Service;
