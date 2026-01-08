<!--
  - SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import type { AdminThemingParameters } from '../../types.d.ts'

import { mdiImageOutline, mdiUndo } from '@mdi/js'
import axios from '@nextcloud/axios'
import { showError } from '@nextcloud/dialogs'
import { loadState } from '@nextcloud/initial-state'
import { t } from '@nextcloud/l10n'
import { generateUrl } from '@nextcloud/router'
import { computed, ref, useTemplateRef } from 'vue'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcIconSvgWrapper from '@nextcloud/vue/components/NcIconSvgWrapper'
import NcLoadingIcon from '@nextcloud/vue/components/NcLoadingIcon'

const props = defineProps<{
	name: string
	label: string
	disabled?: boolean
}>()

const emit = defineEmits<{
	updated: []
}>()

const isSaving = ref(false)
const mime = ref(loadState<AdminThemingParameters>('theming', 'adminThemingParameters')[props.name + 'Mime'] as string)

const inputElement = useTemplateRef('input')

const background = computed(() => {
	const baseUrl = generateUrl('/apps/theming/image/{key}', { key: props.name })
	return `url(${baseUrl}?v=${Date.now()}&m=${encodeURIComponent(mime.value)})`
})

/**
 * Open the file picker dialog
 */
function pickFile() {
	if (isSaving.value) {
		return
	}
	inputElement.value!.files = null
	inputElement.value!.click()
}

/**
 * Handle file input change event
 */
async function onChange() {
	if (!inputElement.value!.files?.[0]) {
		return
	}

	const file = inputElement.value!.files[0]!
	if (file.type && !file.type.startsWith('image/')) {
		showError(t('theming', 'Non image file selected'))
		return
	}

	isSaving.value = true

	const formData = new FormData()
	formData.append('image', file)
	formData.append('key', props.name)

	try {
		await axios.post(generateUrl('/apps/theming/ajax/uploadImage'), formData, {
			headers: {
				'Content-Type': 'multipart/form-data',
			},
		})
		mime.value = file.type
		emit('updated')
	} finally {
		isSaving.value = false
	}
}

/**
 * Reset the image to default
 */
async function resetToDefault() {
	if (isSaving.value) {
		return
	}

	isSaving.value = true
	try {
		await axios.post(generateUrl('/apps/theming/ajax/undoChanges'), {
			setting: props.name,
		})
		mime.value = ''
		emit('updated')
	} finally {
		isSaving.value = false
	}
}
</script>

<template>
	<div :class="$style.fileInputField">
		<NcButton
			:class="$style.fileInputField__button"
			alignment="start"
			:disabled
			size="large"
			@click="pickFile">
			<template #icon>
				<NcLoadingIcon v-if="isSaving" />
				<NcIconSvgWrapper v-else :path="mdiImageOutline" />
			</template>
			{{ label }}
		</NcButton>

		<div
			v-if="mime.startsWith('image/')"
			:class="$style.fileInputField__preview"
			role="img"
			:aria-label="t('theming', 'Preview of the selected image')" />

		<NcButton
			v-if="mime && !disabled"
			:aria-label="t('theming', 'Reset to default')"
			:title="t('theming', 'Reset to default')"
			size="large"
			variant="tertiary"
			@click="resetToDefault">
			<template #icon>
				<NcIconSvgWrapper :path="mdiUndo" />
			</template>
		</NcButton>
		<input
			ref="input"
			class="hidden-visually"
			aria-hidden="true"
			:disabled
			type="file"
			accept="image/*"
			:name
			@change="onChange">
	</div>
</template>

<style module>
.fileInputField {
	display: flex;
	flex-direction: row;
	flex-wrap: wrap;
	align-items: center;
	gap: calc(1.5 * var(--default-grid-baseline));
}

.fileInputField__button {
	min-width: clamp(200px, 25vw, 300px) !important;
}

.fileInputField__preview {
	height: var(--clickable-area-large);
	width: calc(var(--clickable-area-large) / 9 * 16);
	background: v-bind('background');
	background-size: contain;
	background-repeat: no-repeat;
	background-position: center;
	border: 2px solid var(--color-border-maxcontrast);
	border-radius: var(--border-radius-element);
}
</style>
