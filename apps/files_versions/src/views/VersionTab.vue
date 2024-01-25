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
	<div class="versions-tab__container">
		<VirtualScrolling :sections="sections"
			:header-height="0">
			<template slot-scope="{visibleSections}">
				<ul data-files-versions-versions-list>
					<template v-if="visibleSections.length === 1">
						<Version v-for="(row) of visibleSections[0].rows"
							:key="row.items[0].mtime"
							:can-view="canView"
							:can-compare="canCompare"
							:load-preview="isActive"
							:version="row.items[0]"
							:file-info="fileInfo"
							:is-current="row.items[0].mtime === fileInfo.mtime"
							:is-first-version="row.items[0].mtime === initialVersionMtime"
							@click="openVersion"
							@compare="compareVersion"
							@restore="handleRestore"
							@label-update-request="handleLabelUpdateRequest(row.items[0])"
							@delete="handleDelete" />
					</template>
				</ul>
			</template>
			<NcLoadingIcon v-if="loading" slot="loader" class="files-list-viewer__loader" />
		</VirtualScrolling>
		<NcModal v-if="showVersionLabelForm"
			:title="t('files_versions', 'Name this version')"
			@close="showVersionLabelForm = false">
			<VersionLabelForm :version-label="editedVersion.label" @label-update="handleLabelUpdate" />
		</NcModal>
	</div>
</template>

<script>
import path from 'path'

import { showError, showSuccess } from '@nextcloud/dialogs'
import isMobile from '@nextcloud/vue/dist/Mixins/isMobile.js'
import { emit, subscribe, unsubscribe } from '@nextcloud/event-bus'
import { getCurrentUser } from '@nextcloud/auth'
import NcLoadingIcon from '@nextcloud/vue/dist/Components/NcLoadingIcon.js'
import NcModal from '@nextcloud/vue/dist/Components/NcModal.js'

import { fetchVersions, deleteVersion, restoreVersion, setVersionLabel } from '../utils/versions.ts'
import Version from '../components/Version.vue'
import VirtualScrolling from '../components/VirtualScrolling.vue'
import VersionLabelForm from '../components/VersionLabelForm.vue'

export default {
	name: 'VersionTab',
	components: {
		Version,
		VirtualScrolling,
		VersionLabelForm,
		NcLoadingIcon,
		NcModal,
	},
	mixins: [
		isMobile,
	],
	data() {
		return {
			fileInfo: null,
			isActive: false,
			/** @type {import('../utils/versions.ts').Version[]} */
			versions: [],
			loading: false,
			showVersionLabelForm: false,
		}
	},
	computed: {
		sections() {
			const rows = this.orderedVersions.map(version => ({ key: version.mtime, height: 68, sectionKey: 'versions', items: [version] }))
			return [{ key: 'versions', rows, height: 68 * this.orderedVersions.length }]
		},

		/**
		 * Order versions by mtime.
		 * Put the current version at the top.
		 *
		 * @return {import('../utils/versions.ts').Version[]}
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
	mounted() {
		subscribe('files_versions:restore:restored', this.fetchVersions)
	},
	beforeUnmount() {
		unsubscribe('files_versions:restore:restored', this.fetchVersions)
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
		 * @param {import('../utils/versions.ts').Version} version
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
		 * @param {import('../utils/versions.ts').Version} version
		 */
		handleLabelUpdateRequest(version) {
			this.showVersionLabelForm = true
			this.editedVersion = version
		},

		/**
		 * Handle label-updated event from Version.vue
		 * @param {string} newLabel
		 */
		async handleLabelUpdate(newLabel) {
			const oldLabel = this.editedVersion.label
			this.editedVersion.label = newLabel
			this.showVersionLabelForm = false

			try {
				await setVersionLabel(this.editedVersion, newLabel)
				this.editedVersion = null
			} catch (exception) {
				this.editedVersion.label = oldLabel
				showError(this.t('files_versions', 'Could not set version label'))
				logger.error('Could not set version label', { exception })
			}
		},

		/**
		 * Handle deleted event from Version.vue
		 *
		 * @param {import('../utils/versions.ts').Version} version
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
			// We also point to the original filename if the version is the current one.
			const versions = this.versions.map(version => ({
				...version,
				filename: version.mtime === this.fileInfo.mtime ? path.join('files', getCurrentUser()?.uid ?? '', this.fileInfo.path, this.fileInfo.name) : version.filename,
				hasPreview: false,
				previewUrl: undefined,
			}))

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
<style lang="scss">
.versions-tab__container {
	height: 100%;
}
</style>
