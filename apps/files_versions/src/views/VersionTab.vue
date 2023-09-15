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
			:version="version"
			:file-info="fileInfo"
			:is-current="version.mtime === fileInfo.mtime"
			:is-first-version="version.mtime === initialVersionMtime"
			@restore="handleRestore"
			@label-update="handleLabelUpdate"
			@delete="handleDelete" />
	</ul>
</template>

<script>
import { showError, showSuccess } from '@nextcloud/dialogs'
import { fetchVersions, deleteVersion, restoreVersion, setVersionLabel } from '../utils/versions.js'
import Version from '../components/Version.vue'
import { emit, subscribe, unsubscribe } from '@nextcloud/event-bus'

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
	mounted() {
		subscribe('files_versions:restore:restored', this.fetchVersions)
	},
	beforeUnmount() {
		unsubscribe('files_versions:restore:restored', this.fetchVersions)
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
		 * @return {number}
		 */
		initialVersionMtime() {
			return this.versions
				.map(version => version.mtime)
				.reduce((a, b) => Math.min(a, b))
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

			const restoreStartedEventState = {
				preventDefault: false,
				fileInfo: this.fileInfo,
				version,
			}
			emit('files_versions:restore:requested', restoreStartedEventState)
			if (restoreStartedEventState.preventDefault) {
				return
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
				emit('files_versions:restore:restored', version)
			} catch (exception) {
				this.fileInfo = oldFileInfo
				showError(t('files_versions', 'Could not restore version'))
				emit('files_versions:restore:failed', version)
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
	},
}
</script>
