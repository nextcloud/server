/*
 * @copyright Copyright (c) 2018 Julius Härtl <jus@bitgrid.net>
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

let resourceSelectHandlers = {};

export default {

	/**
	 * @class
	 * @param resourceType
	 * @param resourceId
	 * @constructor
	 */
	Resource: function(resourceType, resourceId) {
		this.resourceType = '' + resourceType;
		this.resourceId = '' + resourceId;
	},

	/**
	 * @class
	 * @param id
	 * @param name
	 * @constructor
	 */
	Collection: function(id, name) {
		this.id = '' + id;
		this.name = '' + name;
		this.resources = [];
	},

	/**
	 *
	 * @param resourceType
	 * @param title
	 * @param selectorCallback
	 */
	registerResourceSelector: function(resourceType, title, selectorCallback) {
		if (!this.resourceSelectHandlers.hasOwnProperty(resourceType)) {
			this.resourceSelectHandlers[resourceType] = {
				title: title,
				callback: selectorCallback
			};
		}
	},

	getResourceTypes: function() {
		return this.resourceSelectHandlers;
	},

	/**
	 * Select a resource for a given type
	 *
	 * @param resourceType
	 * @param successCallback
	 * @param abortCallback
	 */
	selectResource: function (resourceType, successCallback, abortCallback) {
		this.resourceSelectHandlers[resourceType].callback(successCallback, abortCallback);
	},

	getCollectionsByResource: function(resourceType, resourceId) {
		// TODO: to implement
	},

	/**
	 * Create a new collection from two resources
	 *
	 * @param name
	 * @param resource1
	 * @param resource2
	 * @param successCallback
	 * @param errorCallback
	 */
	createCollection: function (name, resource1, resource2, successCallback, errorCallback) {
		var self = this;
		this.createCollectionOnResource(name, resource1, function (collection) {
			self.addResource(collection, resource2, function (collection) {
				successCallback(collection);
			})
		});
	},

	/**
	 *
	 * @param name
	 * @param resource1
	 * @param successCallback
	 * @param errorCallback
	 */
	createCollectionOnResource: function (name, resource1, successCallback, errorCallback) {
		var data = {
			name: name,
			resourceType: resource1.resourceType,
			resourceId: ''+resource1.resourceId,
		};
		var request = new XMLHttpRequest();
		request.open('POST', OC.linkToOCS('collaboration/resources/' + data.resourceType, 2) + data.resourceId + '?format=json', true);
		request.setRequestHeader('Content-Type', 'application/json; charset=UTF-8');
		request.setRequestHeader('oc_requesttoken', OC.requestToken);
		request.setRequestHeader('OCS-APIRequest', true);
		request.onreadystatechange = function () {
			if(request.readyState === 4 && request.status === 200) {
				var result = JSON.parse(request.responseText);
				var collection = new OCP.Collaboration.Collection(result.ocs.data.id, result.ocs.data.name);
				collection.resources.push(resource1);
				successCallback(collection);
			}
		};
		request.send(JSON.stringify(data));
	},

	/**
	 * Add a resource to a collection
	 *
	 * @param {OCP.Collaboration.Collection} collection
	 * @param resource
	 * @param successCallback
	 * @param resource
	 */
	addResource: function (collection, resource, successCallback) {
		var data = {
			resourceType: resource.resourceType,
			resourceId: '' + resource.resourceId,
		};
		var request = new XMLHttpRequest();
		request.open('POST', OC.linkToOCS('collaboration/resources/collections', 2) + collection.id + '?format=json', true);
		request.setRequestHeader('Content-Type', 'application/json; charset=UTF-8');
		request.setRequestHeader('oc_requesttoken', OC.requestToken);
		request.setRequestHeader('OCS-APIRequest', true);
		request.onreadystatechange = function () {
			if(request.readyState === 4 && request.status === 200) {
				var result = JSON.parse(request.responseText);
				collection.resources.push(resource);
				successCallback(collection);
			}
		};
		request.send(JSON.stringify(data));
	},

	removeResource: function(collection, resource) {
		// TODO: to implement
	}

};
