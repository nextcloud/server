<script setup lang="ts">
import type { IFolder, INode, NewMenuEntry } from '@nextcloud/files'
import type { FilePickerItem, FilePickerItemGroup } from '@nextcloud/vue/components/NcFilePicker'

import { getNewFileMenu, NewMenuEntryCategory } from '@nextcloud/files'
import { getUploader } from '@nextcloud/files/upload'
import { t } from '@nextcloud/l10n'
import { computed } from 'vue'
import NcFilePicker from '@nextcloud/vue/components/NcFilePicker'

const { folder, context } = defineProps<{
	/** Allow uploading to the current directory */
	directory?: boolean
	/** Whether to show only the icon */
	iconOnly?: boolean
	/** Whether to allow multiple file selection */
	multiple?: boolean
	/** The current folder */
	folder: IFolder
	/** The context for the upload (content of the folder) */
	context: INode[]
}>()

const actions = computed(() => {
	const menu = getNewFileMenu()
	const entries = menu.getEntries()
		.filter((entry) => !entry.enabled || entry.enabled(folder))
		.sort((a, b) => a.order !== undefined && b.order !== undefined ? a.order - b.order : (a.order !== undefined ? -1 : 1))

	const groups: FilePickerItemGroup[] = []
	const uploadEntries = entries.filter((entry) => entry.category === NewMenuEntryCategory.UploadFromDevice)
	if (uploadEntries.length) {
		groups.push({
			caption: t('files', 'Upload from device'),
			actions: uploadEntries.map((entry) => ({
				label: entry.displayName,
				iconSvg: entry.iconSvgInline,
				onClick: () => handleActionClick(entry),
			} as FilePickerItem)),
		})
	}

	const createNewEntries = entries.filter((entry) => entry.category === NewMenuEntryCategory.CreateNew)
	if (createNewEntries.length) {
		groups.push({
			caption: t('files', 'Create new'),
			actions: createNewEntries.map((entry) => ({
				label: entry.displayName,
				iconSvg: entry.iconSvgInline,
				onClick: () => handleActionClick(entry),
			} as FilePickerItem)),
		})
	}

	const otherEntries = entries.filter((entry) => entry.category !== NewMenuEntryCategory.UploadFromDevice && entry.category !== NewMenuEntryCategory.CreateNew)
	if (otherEntries.length) {
		groups.push({
			caption: t('files', 'Other'),
			actions: otherEntries.map((entry) => ({
				label: entry.displayName,
				iconSvg: entry.iconSvgInline,
				onClick: () => handleActionClick(entry),
			} as FilePickerItem)),
		})
	}
	return groups
})

/**
 * Handle click on a menu entry
 *
 * @param entry - The menu entry that was clicked
 */
function handleActionClick(entry: NewMenuEntry) {
	entry.handler(folder, context)
}

/**
 * Hanlde picking files from the file picker
 *
 * @param files - The picked files
 */
async function onPick(files: File[]) {
	const uploader = getUploader()
	await uploader.batchUpload('', files, { callback: handleConflicts, root: folder.path })
}

/**
 * Handle conflicts that occur during upload
 *
 * @param nodes - The nodes that are being uploaded
 * @param currentPath - The current path
 */
async function handleConflicts(nodes: string[], currentPath: string): Promise<false | Record<string, string>> {
	console.error('TODO: HANDLE CONFLICTS', { nodes, currentPath })
	return Object.fromEntries(nodes.map((node) => [node, node]))
}
</script>

<template>
	<NcFilePicker
		:label="t('files', 'New')"
		:actions
		:directory
		:iconOnly
		:multiple
		variant="primary"
		@pick="onPick" />
</template>
