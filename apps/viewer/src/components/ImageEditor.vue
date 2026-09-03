<!--
  - SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<ImageEditor
		:src="file.source"
		:label="file.basename"
		:exportOptions="exportOptions"
		:saving="saving"
		class="viewer__image-editor"
		@save="onSave"
		@cancel="onCancel"
		@error="onError" />
</template>

<script setup lang="ts">
import type { IFile } from '@nextcloud/files'
import type { ExportOptions, ExportResult } from '@nextcloud/image-editor'

import axios from '@nextcloud/axios'
import { showError, showSuccess } from '@nextcloud/dialogs'
import { emit as emitBus } from '@nextcloud/event-bus'
import { ImageEditor } from '@nextcloud/image-editor'
import { t } from '@nextcloud/l10n'
import { computed, ref } from 'vue'
import { logger } from '../services/logger.ts'

import '@nextcloud/image-editor/style'

const props = defineProps<{ file: IFile }>()

const emit = defineEmits<{
	close: []
	/** The saved image as an object URL, so the viewer can show it without refetching. */
	saved: [string]
}>()
// Raised while the edited image is being uploaded, so the editor's save button
// keeps its progress indicator until the file has actually landed.
const saving = ref(false)
// Overwrite the file in its own format so a photo does not balloon into a large
// lossless PNG; fall back to PNG for anything that is not a known lossy format.
const exportOptions = computed<ExportOptions>(() => {
	if (props.file.mime === 'image/jpeg' || props.file.mime === 'image/webp') {
		return { format: props.file.mime, quality: 0.9 }
	}
	return { format: 'image/png' }
})

/**
 * Persist the edited image over the original file.
 *
 * @param result - The exported image
 */
async function onSave(result: ExportResult) {
	saving.value = true
	try {
		const response = await axios.put(props.file.encodedSource, result.blob)

		// Bump the etag so the viewer's preview cache is busted and the edited
		// image is shown once the handler re-renders (see getPreviewIfAny). The
		// node is a shared model object, so updating it in place is intentional.
		const etag = response.headers?.['oc-etag'] ?? response.headers?.etag
		if (etag) {
			// eslint-disable-next-line vue/no-mutating-props -- shared node model, updated in place like the Files app does
			props.file.attributes.etag = String(etag).replace(/"/g, '')
		}

		// Hand the viewer the edited bytes so it shows them instantly instead of
		// refetching the (now cache-busted) preview.
		emit('saved', URL.createObjectURL(result.blob))
		emitBus('files:node:updated', props.file)
		showSuccess(t('viewer', 'Image saved'))
		emit('close')
	} catch (error) {
		logger.error('Failed to save the edited image', { error })
		showError(t('viewer', 'Could not save the image'))
	} finally {
		saving.value = false
	}
}

/**
 * Close the editor without saving.
 */
function onCancel() {
	emit('close')
}

/**
 * Surface a loading/export failure from the editor.
 *
 * @param error - The error raised by the editor
 */
function onError(error: Error) {
	logger.error('Image editor error', { error })
	showError(t('viewer', 'The image could not be edited.'))
}
</script>

<style scoped>
/* Full-viewport overlay so the editor fills the screen regardless of the
   (collapsing) handler container it replaces. */
.viewer__image-editor {
	position: fixed;
	inset: 0;
	z-index: 10102;
}
</style>
