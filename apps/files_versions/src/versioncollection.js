/**
 * Copyright (c) 2015
 *
 * @author John Molakvoæ <skjnldsv@protonmail.com>
 * @author Robin Appelman <robin@icewind.nl>
 * @author Vincent Petry <vincent@nextcloud.com>
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

(function() {
	/**
	 * @memberof OCA.Versions
	 */
	const VersionCollection = OC.Backbone.Collection.extend({
		model: OCA.Versions.VersionModel,
		sync: OC.Backbone.davSync,

		/**
		 * @member OCA.Files.FileInfoModel
		 */
		_fileInfo: null,

		_currentUser: null,

		_client: null,

		setFileInfo(fileInfo) {
			this._fileInfo = fileInfo
		},

		getFileInfo() {
			return this._fileInfo
		},

		setCurrentUser(user) {
			this._currentUser = user
		},

		getCurrentUser() {
			return this._currentUser || OC.getCurrentUser().uid
		},

		setClient(client) {
			this._client = client
		},

		getClient() {
			return this._client || new OC.Files.Client({
				host: OC.getHost(),
				root: OC.linkToRemoteBase('dav') + '/versions/' + this.getCurrentUser(),
				useHTTPS: OC.getProtocol() === 'https',
			})
		},

		url() {
			return OC.linkToRemoteBase('dav') + '/versions/' + this.getCurrentUser() + '/versions/' + this._fileInfo.get('id')
		},

		parse(result) {
			const fullPath = this._fileInfo.getFullPath()
			const fileId = this._fileInfo.get('id')
			const name = this._fileInfo.get('name')
			const user = this.getCurrentUser()
			const client = this.getClient()
			return _.map(result, function(version) {
				version.fullPath = fullPath
				version.fileId = fileId
				version.name = name
				version.timestamp = parseInt(moment(new Date(version.timestamp)).format('X'), 10)
				version.id = OC.basename(version.href)
				version.size = parseInt(version.size, 10)
				version.user = user
				version.client = client
				return version
			})
		},
	})

	OCA.Versions = OCA.Versions || {}

	OCA.Versions.VersionCollection = VersionCollection
})()
