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
	<ul data-files-versions-versions-list>
		<Version v-for="version in orderedVersions"
			:key="version.mtime"
			:can-view="canView"
			:can-compare="canCompare"
			:load-preview="isActive"
			:version="version"
			:file-info="fileInfo"
			:is-current="version.mtime === fileInfo.mtime"
			:is-first-version="version.mtime === initialVersionMtime"
			@click="openVersion"
			@compare="compareVersion"
			@restore="handleRestore"
			@label-update="handleLabelUpdate"
			@delete="handleDelete" />
	</ul>
</template>

<script>
import { showError, showSuccess } from '@nextcloud/dialogs'
import isMobile from '@nextcloud/vue/dist/Mixins/isMobile.js'
import { fetchVersions, deleteVersion, restoreVersion, setVersionLabel } from '../utils/versions.js'
import Version from '../components/Version.vue'

export default {
	name: 'VersionTab',
	components: {
		Version,
	},
	mixins: [
		isMobile,
	],
	data() {
		return {
			fileInfo: null,
			isActive: false,
			/** @type {import('../utils/versions.js').Version[]} */
			versions: [],
			loading: false,
		}
	},
	computed: {
		/**
		 * Order versions by mtime.
		 * Put the current version at the top.
		 *
		 * @return {import('../utils/versions.js').Version[]}
		 */
		orderedVersions() {
			return [...this.versions].sort((a, b) => {
				if (a.mtime === this.fileInfo.mtime) {
					return -1
				} else if (b.mtime === this.fileInfo.mtime) {
					return 1
				} else {
					return b.mtime - a.mtime
				}
			})
		},

		/**
		 * Return the mtime of the first version to display "Initial version" label
		 *
		 * @return {number}
		 */
		initialVersionMtime() {
			return this.versions
				.map(version => version.mtime)
				.reduce((a, b) => Math.min(a, b))
		},

		viewerFileInfo() {
			// We need to remap bitmask to dav permissions as the file info we have is converted through client.js
			let davPermissions = ''
			if (this.fileInfo.permissions & 1) {
				davPermissions += 'R'
			}
			if (this.fileInfo.permissions & 2) {
				davPermissions += 'W'
			}
			if (this.fileInfo.permissions & 8) {
				davPermissions += 'D'
			}
			return {
				...this.fileInfo,
				mime: this.fileInfo.mimetype,
				basename: this.fileInfo.name,
				filename: this.fileInfo.path + '/' + this.fileInfo.name,
				permissions: davPermissions,
				fileid: this.fileInfo.id,
			}
		},

		/** @return {boolean} */
		canView() {
			return window.OCA.Viewer?.mimetypesCompare?.includes(this.fileInfo.mimetype)
		},

		canCompare() {
			return !this.isMobile
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
		 * @param {boolean} isActive whether the tab is active
		 */
		async setIsActive(isActive) {
			this.isActive = isActive
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
		 * Handle restored event from Version.vue
		 *
		 * @param {import('../utils/versions.js').Version} version
		 */
		async handleRestore(version) {
			// Update local copy of fileInfo as rendering depends on it.
			const oldFileInfo = this.fileInfo
			this.fileInfo = {
				...this.fileInfo,
				size: version.size,
				mtime: version.mtime,
			}

			try {
				await restoreVersion(version)
				if (version.label !== '') {
					showSuccess(t('files_versions', `${version.label} restored`))
				} else if (version.mtime === this.initialVersionMtime) {
					showSuccess(t('files_versions', 'Initial version restored'))
				} else {
					showSuccess(t('files_versions', 'Version restored'))
				}
				await this.fetchVersions()
			} catch (exception) {
				this.fileInfo = oldFileInfo
				showError(t('files_versions', 'Could not restore version'))
			}
		},

		/**
		 * Handle label-updated event from Version.vue
		 *
		 * @param {import('../utils/versions.js').Version} version
		 * @param {string} newName
		 */
		async handleLabelUpdate(version, newName) {
			const oldLabel = version.label
			version.label = newName

			try {
				await setVersionLabel(version, newName)
			} catch (exception) {
				version.label = oldLabel
				showError(t('files_versions', 'Could not set version name'))
			}
		},

		/**
		 * Handle deleted event from Version.vue
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

		openVersion({ version }) {
			// Open current file view instead of read only
			if (version.mtime === this.fileInfo.mtime) {
				OCA.Viewer.open({ fileInfo: this.viewerFileInfo })
				return
			}

			// Versions previews are too small for our use case, so we override hasPreview and previewUrl
			// which makes the viewer render the original file.
			const versions = this.versions.map(version => ({ ...version, hasPreview: false, previewUrl: undefined }))

			OCA.Viewer.open({
				fileInfo: versions.find(v => v.source === version.source),
				enableSidebar: false,
			})
		},

		compareVersion({ version }) {
			const versions = this.versions.map(version => ({ ...version, hasPreview: false, previewUrl: undefined }))

			OCA.Viewer.compare(this.viewerFileInfo, versions.find(v => v.source === version.source))
		},
	},
}
</script>
