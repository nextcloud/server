<!--
 - @copyright 2022 Carl Schwan <carl@carlschwan.eu>
 - @license AGPL-3.0-or-later
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
 -->
<template>
	<ul>
		<Version :key="currentVersion.mtime" :version="currentVersion" :is-current="true" />
		<Version v-for="version in versions"
			:key="version.mtime"
			:version="version"
			@restore="handleRestore"
			@name-update="handleNameUpdate"
			@delete="handleDelete" />
	</ul>
</template>

<script>
import { generateUrl } from '@nextcloud/router'
import { joinPaths } from '@nextcloud/paths'
import { translate } from '@nextcloud/l10n'
import { showError, showSuccess } from '@nextcloud/dialogs'
import { fetchVersions, deleteVersion, restoreVersion, setVersionName } from '../utils/versions.js'
import Version from '../components/Version.vue'

export default {
	name: 'VersionTab',
	components: {
		Version,
	},
	data() {
		return {
			fileInfo: null,
			/** @type {import('../utils/versions.js').Version[]} */
			versions: [],
			loading: false,
		}
	},
	computed: {
		/**
		 * @return {import('../utils/versions.js').Version}
		 */
		currentVersion() {
			return {
				fileId: this.fileInfo.id,
				title: translate('files_versions', 'Current version'),
				fileName: this.fileInfo.filename,
				mimeType: this.fileInfo.mimeType,
				size: this.fileInfo.size,
				type: this.fileInfo.type,
				mtime: this.fileInfo.mtime,
				preview: generateUrl('/core/preview?fileId={fileId}&c={fileEtag}&x=250&y=250&forceIcon=0&a=0', {
					fileId: this.fileInfo.id,
					fileEtag: this.fileInfo.etag,
				}),
				url: joinPaths('/remote.php/webdav', this.fileInfo.path, this.fileInfo.name),
				fileVersion: null,
			}
		},

		/**
		 * @return {import('../utils/versions.js').Version[]}
		 */
		orderedVersions() {
			return [...this.versions].sort((a, b) => a.mtime - b.mtime)
		},
	},
	methods: {
		/**
		 * Update current fileInfo and fetch new data
		 *
		 * @param {object} fileInfo the current file FileInfo
		 */
		async update(fileInfo) {
			this.fileInfo = fileInfo
			this.resetState()
			this.fetchVersions()
		},

		/**
		 * Get the existing versions infos
		 */
		async fetchVersions() {
			try {
				this.loading = true
				this.versions = await fetchVersions(this.fileInfo)
			} finally {
				this.loading = false
			}
		},

		/**
		 * Handle version-restored event from Version.vue
		 *
		 * @param {import('../utils/versions.js').Version} version
		 */
		async handleRestore(version) {
			// File info is not updated so we manually update its size and mtime.
			const oldFileInfo = { ...this.fileInfo }
			this.fileInfo.size = version.size
			this.fileInfo.mtime = version.mtime

			const index = this.versions.indexOf(version)
			this.versions.splice(index, 1)

			try {
				await restoreVersion(version)
				showSuccess(t('files_versions', 'Version restored'))
				await this.fetchVersions()
			} catch (exception) {
				this.fileInfo.size = oldFileInfo.size
				this.fileInfo.mtime = oldFileInfo.mtime
				this.versions.push(version)
				showError(t('files_versions', 'Could not restore version'))
			}
		},

		/**
		 * Handle version-name-updated event from Version.vue
		 *
		 * @param {import('../utils/versions.js').Version} version
		 * @param {string} newName
		 */
		async handleNameUpdate(version, newName) {
			const oldTitle = version.title
			version.title = newName

			try {
				await setVersionName(version, newName)
			} catch (exception) {
				version.title = oldTitle
				showError(t('files_versions', 'Could not set version name'))
			}

		},

		/**
		 * Handle version-deleted event from Version.vue
		 *
		 * @param {import('../utils/versions.js').Version} version
		 * @param {string} newName
		 */
		async handleDelete(version) {
			const index = this.versions.indexOf(version)
			this.versions.splice(index, 1)

			try {
				await deleteVersion(version)
			} catch (exception) {
				this.versions.push(version)
				showError(t('files_versions', 'Could not delete version'))
			}
		},

		/**
		 * Reset the current view to its default state
		 */
		resetState() {
			this.$set(this, 'versions', [])
		},
	},
}
</script>
