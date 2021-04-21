<!--
  - @copyright Copyright (c) 2021 Enoch <enoch@nextcloud.com>
  -
  - @author Enoch <enoch@nextcloud.com>
  -
  - @license GNU AGPL version 3 or any later version
  -
  - This program is free software: you can redistribute it and/or modify
  - it under the terms of the GNU Affero General Public License as
  - published by the Free Software Foundation, either version 3 of the
  - License, or (at your option) any later version.
  -
  - This program is distributed in the hope that it will be useful,
  - but WITHOUT ANY WARRANTY; without even the implied warranty of
  - MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  - GNU Affero General Public License for more details.
  -
  - You should have received a copy of the GNU Affero General Public License
  - along with this program. If not, see <http://www.gnu.org/licenses/>.
  -
  -->

<template>
	<div :class="{ 'icon-loading': loading }">
		<!-- error message -->
		<div v-if="error" class="emptycontent">
			<div class="icon icon-error" />
			<h2>{{ error }}</h2>
		</div>

		<!-- Version content -->
		<template>
			<!-- Version information -->
			<ListItemIcon :versions="versionsList" icon="icon-text" title="10 days ago" subtitle="< 1KB">
				<Actions>
					<ActionButton icon="icon-edit" @click="alert('Edit')">{{version.timestamp}}Restore</ActionButton>
					<ActionButton icon="icon-delete" @click="alert('Delete')">Download</ActionButton>
				</Actions>
			</ListItemIcon>

		</template>
	</div>
</template>

<script>
// import { CollectionList } from 'nextcloud-vue-collections'

import Avatar from '@nextcloud/vue/dist/Components/Avatar'
import axios from '@nextcloud/axios'
import { generateRemoteUrl} from "@nextcloud/router";
import { ListItemIcon } from '@nextcloud/vue'

export default {
	name: 'VersionTab',

	components: {

		Avatar,
		ListItemIcon,
		VersionEntry
	},

	data() {
		return {
			config: new Config(),

			error: '',
			expirationInterval: null,
			loading: true,

			fileInfo: null,

			currentUser: null,

			client: null,

			// version object
			versionsList: [],
	}
	},


	methods: {
		setFileInfo (fileInfo) {
			this._fileInfo = fileInfo
		},

		getFileInfo () {
			return this._fileInfo
		},

		setCurrentUser (user) {
			this._currentUser = user
		},

		getCurrentUser () {
			return this._currentUser || OC.getCurrentUser().uid
		},

		setClient (client) {
			this._client = client
		},
		/**
		 * Update current fileInfo and fetch new data
		 * @param {Object} fileInfo the current file FileInfo
		 */
		async update (fileInfo) {
			fileInfo = this.fileInfo
			name = this._fileInfo.get('name')
		},

		/**
		 * Get the Version infos
		 */

		/**
		 * Get the existing shares infos
		 */
		async getVersions () {
			try {
				this.loading = true

				// init params
				const shareUrl = generateRemoteUrl('dav') + this.getCurrentUser() + '/versions/' + this._fileInfo.get('id')
				const format = 'json'
                console.log('Shareurl:', shareUrl);
				// TODO: replace with proper getFUllpath implementation of our own FileInfo model
				const path = (this.fileInfo.path + '/' + this.fileInfo.name).replace('//', '/')

				console.log(path);
				// fetch version
				const fetchVersion = await axios.get(shareUrl, {
					params: {
						format,
						path,
					},
				})
				// wait for data
				this.loading = false
				// process results
				this.versionList = fetchVersion.data
				console.log(versionList);
				this.version.fullPath = fullPath
				this.version.fileId = fileId
				this.version.name = name
				this.version.timestamp = parseInt(moment(new Date(version.timestamp)).format('X'), 10)
				this.version.id = OC.basename(version.href)
				this.version.size = parseInt(version.size, 10)
				this.version.user = user
				this.version.client = client
				return version
			} catch (error) {
				this.error = t('files_version', 'Unable to load the version list')
				this.loading = false
				console.error('Error loading the version list', error)
			}
		},
		mounted(){
			this.getVersions();
		}
	}
}
</script>
