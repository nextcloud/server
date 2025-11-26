<!--
  - SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<div v-if="fileInfo !== null" class="versions-tab__container">
		<VirtualScrolling
			:sections="sections"
			:header-height="0">
			<template #default="{ visibleSections }">
				<ul :aria-label="t('files_versions', 'File versions')" data-files-versions-versions-list>
					<template v-if="visibleSections.length === 1">
						<VersionEntry
							v-for="(row) of visibleSections[0].rows"
							:key="row.items[0].version.mtime"
							:can-view="canView"
							:can-compare="canCompare"
							:load-preview="isActive"
							:version="row.items[0].version"
							:file-info="fileInfo"
							:is-current="row.items[0].version.mtime === fileInfo.mtime"
							:is-first-version="row.items[0].version.mtime === initialVersionMtime"
							@click="openVersion"
							@compare="compareVersion"
							@restore="handleRestore"
							@label-update-request="handleLabelUpdateRequest(row.items[0].version)"
							@delete="handleDelete" />
					</template>
				</ul>
			</template>
			<template #loader>
				<NcLoadingIcon v-if="loading" class="files-list-viewer__loader" />
			</template>
		</VirtualScrolling>
		<VersionLabelDialog
			v-if="editedVersion"
			v-model:open="showVersionLabelForm"
			:label="editedVersion.label"
			@update:label="handleLabelUpdate" />
	</div>
</template>

<script lang="ts" setup>
import type { LegacyFileInfo } from '../../../files/src/services/FileInfo.ts'
import type { Version } from '../utils/versions.ts'

import { getCurrentUser } from '@nextcloud/auth'
import { showError, showSuccess } from '@nextcloud/dialogs'
import { emit, subscribe, unsubscribe } from '@nextcloud/event-bus'
import { t } from '@nextcloud/l10n'
import { useIsMobile } from '@nextcloud/vue/composables/useIsMobile'
import path from 'path'
import { computed, onBeforeUnmount, onMounted, ref } from 'vue'
import NcLoadingIcon from '@nextcloud/vue/components/NcLoadingIcon'
import VersionEntry from '../components/VersionEntry.vue'
import VersionLabelDialog from '../components/VersionLabelDialog.vue'
import VirtualScrolling from '../components/VirtualScrolling.vue'
import logger from '../utils/logger.ts'
import { deleteVersion, fetchVersions, restoreVersion, setVersionLabel } from '../utils/versions.ts'

const isMobile = useIsMobile()

const fileInfo = ref<LegacyFileInfo | null>(null)
const isActive = ref<boolean>(false)
const versions = ref<Version[]>([])
const loading = ref(false)
const showVersionLabelForm = ref(false)
const editedVersion = ref<Version | null>(null)

/**
 * Order versions by mtime.
 * Put the current version at the top.
 */
const orderedVersions = computed(() => {
	return [...versions.value].sort((a, b) => {
		if (fileInfo.value === null) {
			return 0
		}

		if (a.mtime === fileInfo.value.mtime) {
			return -1
		} else if (b.mtime === fileInfo.value.mtime) {
			return 1
		} else {
			return b.mtime - a.mtime
		}
	})
})

const sections = computed(() => {
	const rows = orderedVersions.value.map((version) => ({ key: version.mtime.toString(), height: 68, sectionKey: 'versions', items: [{ id: version.mtime.toString(), version }] }))
	return [{ key: 'versions', rows, height: 68 * orderedVersions.value.length }]
})

/**
 * Return the mtime of the first version to display "Initial version" label
 */
const initialVersionMtime = computed(() => {
	return versions.value
		.map((version) => version.mtime)
		.reduce((a, b) => Math.min(a, b))
})

const viewerFileInfo = computed(() => {
	if (fileInfo.value === null) {
		return null
	}

	// We need to remap bitmask to dav permissions as the file info we have is converted through client.js
	let davPermissions = ''
	if (fileInfo.value.permissions & 1) {
		davPermissions += 'R'
	}
	if (fileInfo.value.permissions & 2) {
		davPermissions += 'W'
	}
	if (fileInfo.value.permissions & 8) {
		davPermissions += 'D'
	}
	return {
		...fileInfo.value,
		mime: fileInfo.value.mimetype,
		basename: fileInfo.value.name,
		filename: fileInfo.value.path + '/' + fileInfo.value.name,
		permissions: davPermissions,
		fileid: fileInfo.value.id,
	}
})

const canView = computed(() => {
	if (fileInfo.value === null) {
		return false
	}

	return window.OCA.Viewer?.mimetypesCompare?.includes(fileInfo.value.mimetype)
})

const canCompare = computed(() => {
	return !isMobile.value
})

onMounted(() => {
	subscribe('files_versions:restore:restored', fetchVersions)
})

onBeforeUnmount(() => {
	unsubscribe('files_versions:restore:restored', fetchVersions)
})

defineExpose({
	/**
	 * Update current fileInfo and fetch new data
	 *
	 * @param _fileInfo the current file FileInfo
	 */
	async update(_fileInfo: LegacyFileInfo) {
		fileInfo.value = _fileInfo
		resetState()
		internalFetchVersions()
	},

	/**
	 * @param _isActive whether the tab is active
	 */
	async setIsActive(_isActive: boolean) {
		isActive.value = _isActive
	},
})

/**
 * Get the existing versions infos
 */
async function internalFetchVersions() {
	try {
		loading.value = true
		versions.value = await fetchVersions(fileInfo.value)
	} finally {
		loading.value = false
	}
}

/**
 * Handle restored event from Version.vue
 *
 * @param version The version to restore
 */
async function handleRestore(version: Version) {
	// Update local copy of fileInfo as rendering depends on it.
	const oldFileInfo = fileInfo.value
	fileInfo.value = {
		...fileInfo.value,
		size: version.size,
		mtime: version.mtime,
	}

	const restoreStartedEventState = {
		preventDefault: false,
		fileInfo: fileInfo.value,
		version,
	}
	emit('files_versions:restore:requested', restoreStartedEventState)
	if (restoreStartedEventState.preventDefault) {
		return
	}

	try {
		await restoreVersion(version)
		if (version.label) {
			showSuccess(t('files_versions', `${version.label} restored`))
		} else if (version.mtime === initialVersionMtime.value) {
			showSuccess(t('files_versions', 'Initial version restored'))
		} else {
			showSuccess(t('files_versions', 'Version restored'))
		}
		emit('files_versions:restore:restored', version)
	} catch {
		fileInfo.value = oldFileInfo
		showError(t('files_versions', 'Could not restore version'))
		emit('files_versions:restore:failed', version)
	}
}

/**
 * Handle label-updated event from Version.vue
 *
 * @param version The version to update
 */
function handleLabelUpdateRequest(version: Version) {
	showVersionLabelForm.value = true
	editedVersion.value = version
}

/**
 * Handle label-updated event from Version.vue
 *
 * @param newLabel The new label
 */
async function handleLabelUpdate(newLabel: string) {
	if (editedVersion.value === null) {
		throw new Error('editedVersion should be set at that point')
	}

	const oldLabel = editedVersion.value.label
	editedVersion.value.label = newLabel
	showVersionLabelForm.value = false

	try {
		await setVersionLabel(editedVersion.value, newLabel)
		editedVersion.value = null
	} catch (exception) {
		editedVersion.value!.label = oldLabel
		showError(t('files_versions', 'Could not set version label'))
		logger.error('Could not set version label', { exception })
	}
}

/**
 * Handle deleted event from Version.vue
 *
 * @param version The version to delete
 */
async function handleDelete(version: Version) {
	const index = versions.value.indexOf(version)
	versions.value.splice(index, 1)

	try {
		await deleteVersion(version)
	} catch {
		versions.value.push(version)
		showError(t('files_versions', 'Could not delete version'))
	}
}

/**
 * Reset the current view to its default state
 */
function resetState() {
	versions.value = []
}

/**
 * @param payload - The event payload
 * @param payload.version - The version to open
 */
function openVersion({ version }: { version: Version }) {
	if (fileInfo.value === null) {
		return
	}

	// Open current file view instead of read only
	if (version.mtime === fileInfo.value.mtime) {
		window.OCA.Viewer.open({ fileInfo: viewerFileInfo.value })
		return
	}

	window.OCA.Viewer.open({
		fileInfo: {
			...version,
			// Versions previews are too small for our use case, so we override previewUrl
			// to either point to the original file or original version.
			filename: version.mtime === fileInfo.value.mtime ? path.join('files', getCurrentUser()?.uid ?? '', fileInfo.value.path, fileInfo.value.name) : version.filename,
			previewUrl: undefined,
		},
		enableSidebar: false,
	})
}

/**
 * @param payload - The event payload
 * @param payload.version - The version to compare
 */
function compareVersion({ version }: { version: Version }) {
	const _versions = versions.value.map((version) => ({ ...version, previewUrl: undefined }))

	window.OCA.Viewer.compare(viewerFileInfo.value, _versions.find((v) => v.source === version.source))
}
</script>

<style lang="scss">
.versions-tab__container {
	height: 100%;
}
</style>
