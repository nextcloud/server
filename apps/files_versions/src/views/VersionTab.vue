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
		<template>
			<!-- Version information -->
			<VersionEntry :versions="versionsList" />
		</template>
	</div>
</template>

<script>

import axios from '@nextcloud/axios'
import { generateRemoteUrl } from '@nextcloud/router'
import { ListItemIcon } from '@nextcloud/vue'
import VersionEntry from '../components/VersionEntry'
import FileVersion from "../services/FileVersion";
import fetchFileVersions from "../services/FileVersion";

export default {
	name: 'VersionTab',

	components: {

		ListItemIcon,
		VersionEntry,
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
		setFileInfo(fileInfo) {
			this._fileInfo = fileInfo
		},

		getFileInfo() {
			return this._fileInfo
		},

		setCurrentUser(user) {
			this._currentUser = user
		},
		setClient(client) {
			this._client = client
		},
		/**
		 * Update current fileInfo and fetch new data
		 * @param {Object} fileInfo the current file FileInfo
		 */
		async update(fileInfo) {
			fileInfo = this.fileInfo
			name = this._fileInfo.get('name')
		},

		/**
		 * Get the Version infos
		 */

		/**
		 * Get the existing shares infos
		 */
		async getVersions() {
			try {
				this.loading = true
				const fetchVersions = fetchFileVersions(this._fileInfo.get('id'));
				fetchVersions();
			}
			catch(e){
                console.log(error);
			}
		}
		mounted() {
			this.getVersions()
		},
	},
}
</script>
