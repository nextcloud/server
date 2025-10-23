<!--
  - SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<div :id="containerId" />
</template>

<script setup lang="ts">
import type { IFilePickerButton } from '@nextcloud/dialogs'
import type { Node as NcNode } from '@nextcloud/files'

import { FilePickerBuilder } from '@nextcloud/dialogs'
import { t } from '@nextcloud/l10n'
import { onMounted } from 'vue'
import { generateFileUrl } from '../../../files_sharing/src/utils/generateUrl.ts'
import logger from '../logger.ts'

defineProps<{
	providerId: string
	accessible: boolean
}>()

const emit = defineEmits<{
	(e: 'submit', url: string): void
	(e: 'cancel'): void
}>()

const containerId = `filepicker-${Math.random().toString(36).slice(7)}`

const filePicker = new FilePickerBuilder(t('files', 'Select file or folder to link to'))
	.allowDirectories(true)
	.setButtonFactory(buttonFactory)
	.setContainer(`#${containerId}`)
	.setMultiSelect(false)
	.build()

onMounted(async () => {
	try {
		const [node] = await filePicker.pickNodes()
		onSubmit(node)
	} catch (error) {
		logger.debug('Aborted picking nodes:', { error })
		emit('cancel')
	}
})

/**
 * Get buttons for the file picker dialog
 *
 * @param selected - currently selected nodes
 */
function buttonFactory(selected: NcNode[]): IFilePickerButton[] {
	const buttons = [] as IFilePickerButton[]
	const node = selected[0]
	if (node === undefined) {
		return []
	}

	if (node.path === '/') {
		return [] // Do not allow selecting the users root folder
	}

	buttons.push({
		label: t('files', 'Choose {file}', { file: node.displayname }),
		variant: 'primary',
		callback: () => {}, // handled by the pickNodes method
	})
	return buttons
}

/**
 * @param node - selected node
 */
function onSubmit(node: NcNode) {
	emit('submit', generateFileUrl(node.fileid!))
}
</script>
