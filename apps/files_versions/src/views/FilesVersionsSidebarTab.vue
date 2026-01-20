<!--
  - SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<div v-if="node" class="versions-tab__container">
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
							:load-preview="active"
							:version="row.items[0].version"
							:node="node"
							:is-current="row.items[0].version.mtime === currentVersionMtime"
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
import type { IFolder, INode, IView } from '@nextcloud/files'
import type { Version } from '../utils/versions.ts'

import { showError, showSuccess } from '@nextcloud/dialogs'
import { emit } from '@nextcloud/event-bus'
import { t } from '@nextcloud/l10n'
import { useIsMobile } from '@nextcloud/vue/composables/useIsMobile'
import { computed, ref, toRef, watch } from 'vue'
import NcLoadingIcon from '@nextcloud/vue/components/NcLoadingIcon'
import VersionEntry from '../components/VersionEntry.vue'
import VersionLabelDialog from '../components/VersionLabelDialog.vue'
import VirtualScrolling from '../components/VirtualScrolling.vue'
import logger from '../utils/logger.ts'
import { deleteVersion, fetchVersions, restoreVersion, setVersionLabel } from '../utils/versions.ts'

const props = defineProps<{
	active: boolean
	node: INode

	// eslint-disable-next-line vue/no-unused-properties -- required by SidebarTab but we do not need it
	folder: IFolder
	// eslint-disable-next-line vue/no-unused-properties -- required by SidebarTab but we do not need it
	view: IView
}>()

const isMobile = useIsMobile()
const versions = ref<Version[]>([])
const loading = ref(false)
const showVersionLabelForm = ref(false)
const editedVersion = ref<Version | null>(null)

watch(toRef(() => props.node), async () => {
	if (!props.node) {
		return
	}

	try {
		loading.value = true
		versions.value = await fetchVersions(props.node)
	} finally {
		loading.value = false
	}
}, { immediate: true })

const currentVersionMtime = computed(() => props.node?.mtime?.getTime() ?? 0)

/**
 * Order versions by mtime.
 * Put the current version at the top.
 */
const orderedVersions = computed(() => {
	return [...versions.value].sort((a, b) => {
		if (!props.node) {
			return 0
		}

		if (a.mtime === props.node.mtime?.getTime()) {
			return -1
		} else if (b.mtime === props.node.mtime?.getTime()) {
			return 1
		} else {
			return b.mtime - a.mtime
		}
	})
})

const sections = computed(() => {
	const rows = orderedVersions.value.map((version) => ({
		key: version.mtime.toString(),
		height: 68,
		sectionKey: 'versions',
		items: [{ id: version.mtime.toString(), version }],
	}))
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

const canView = computed(() => {
	if (!props.node) {
		return false
	}

	return window.OCA.Viewer?.mimetypes?.includes(props.node?.mime)
})

const canCompare = computed(() => {
	return !isMobile.value
		&& window.OCA.Viewer?.mimetypesCompare?.includes(props.node?.mime)
})

/**
 * Handle restored event from Version.vue
 *
 * @param version The version to restore
 */
async function handleRestore(version: Version) {
	if (!props.node) {
		return
	}

	// Update local copy of fileInfo as rendering depends on it.
	const restoredNode = props.node.clone()
	restoredNode.attributes.etag = version.etag
	restoredNode.size = version.size
	restoredNode.mtime = new Date(version.mtime)

	const restoreStartedEventState = {
		preventDefault: false,
		node: restoredNode,
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
		emit('files:node:updated', restoredNode)
		emit('files_versions:restore:restored', { node: restoredNode, version })
	} catch {
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
 * @param payload - The event payload
 * @param payload.version - The version to open
 */
function openVersion({ version }: { version: Version }) {
	if (props.node === null) {
		return
	}

	// Open current file view instead of read only
	if (version.mtime === props.node?.mtime?.getTime()) {
		window.OCA.Viewer.open({ path: props.node.path })
		return
	}

	window.OCA.Viewer.open({
		fileInfo: {
			...version,
			// Versions previews are too small for our use case, so we override previewUrl
			// to either point to the original file or original version.
			filename: version.filename,
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

	window.OCA.Viewer.compare(
		{ path: props.node!.path },
		_versions.find((v) => v.source === version.source),
	)
}
</script>

<style lang="scss">
.versions-tab__container {
	height: 100%;
}
</style>
