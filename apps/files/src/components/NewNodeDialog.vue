<!--
  - SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<NcDialog data-cy-files-new-node-dialog
		:name="name"
		:open="open"
		close-on-click-outside
		out-transition
		@update:open="emit('close', null)">
		<template #actions>
			<NcButton data-cy-files-new-node-dialog-submit
				type="primary"
				:disabled="validity !== ''"
				@click="submit">
				{{ t('files', 'Create') }}
			</NcButton>
		</template>
		<form ref="formElement"
			class="new-node-dialog__form"
			@submit.prevent="emit('close', localDefaultName)">
			<NcTextField ref="nameInput"
				data-cy-files-new-node-dialog-input
				:error="validity !== ''"
				:helper-text="validity"
				:label="label"
				:value.sync="localDefaultName" />
		</form>
	</NcDialog>
</template>

<script setup lang="ts">
import type { ComponentPublicInstance, PropType } from 'vue'
import { getUniqueName } from '@nextcloud/files'
import { t } from '@nextcloud/l10n'
import { extname } from 'path'
import { nextTick, onMounted, ref, watch, watchEffect } from 'vue'
import { getFilenameValidity } from '../utils/filenameValidity.ts'

import NcButton from '@nextcloud/vue/dist/Components/NcButton.js'
import NcDialog from '@nextcloud/vue/dist/Components/NcDialog.js'
import NcTextField from '@nextcloud/vue/dist/Components/NcTextField.js'

const props = defineProps({
	/**
	 * The name to be used by default
	 */
	defaultName: {
		type: String,
		default: t('files', 'New folder'),
	},
	/**
	 * Other files that are in the current directory
	 */
	otherNames: {
		type: Array as PropType<string[]>,
		default: () => [],
	},
	/**
	 * Open state of the dialog
	 */
	open: {
		type: Boolean,
		default: true,
	},
	/**
	 * Dialog name
	 */
	name: {
		type: String,
		default: t('files', 'Create new folder'),
	},
	/**
	 * Input label
	 */
	label: {
		type: String,
		default: t('files', 'Folder name'),
	},
})

const emit = defineEmits<{
	(event: 'close', name: string | null): void
}>()

const localDefaultName = ref<string>(props.defaultName)
const nameInput = ref<ComponentPublicInstance>()
const formElement = ref<HTMLFormElement>()
const validity = ref('')

/**
 * Focus the filename input field
 */
function focusInput() {
	nextTick(() => {
		// get the input element
		const input = nameInput.value?.$el.querySelector('input')
		if (!props.open || !input) {
			return
		}

		// length of the basename
		const length = localDefaultName.value.length - extname(localDefaultName.value).length
		// focus the input
		input.focus()
		// and set the selection to the basename (name without extension)
		input.setSelectionRange(0, length)
	})
}

/**
 * Trigger submit on the form
 */
function submit() {
	formElement.value?.requestSubmit()
}

// Reset local name on props change
watch(() => [props.defaultName, props.otherNames], () => {
	localDefaultName.value = getUniqueName(props.defaultName, props.otherNames).trim()
})

// Validate the local name
watchEffect(() => {
	if (props.otherNames.includes(localDefaultName.value.trim())) {
		validity.value = t('files', 'This name is already in use.')
	} else {
		validity.value = getFilenameValidity(localDefaultName.value.trim())
	}
	const input = nameInput.value?.$el.querySelector('input')
	if (input) {
		input.setCustomValidity(validity.value)
		input.reportValidity()
	}
})

// Ensure the input is focussed even if the dialog is already mounted but not open
watch(() => props.open, () => {
	nextTick(() => {
		focusInput()
	})
})

onMounted(() => {
	// on mounted lets use the unique name
	localDefaultName.value = getUniqueName(localDefaultName.value, props.otherNames).trim()
	nextTick(() => focusInput())
})
</script>

<style scoped>
.new-node-dialog__form {
	/* Ensure the dialog does not jump when there is a validity error */
	min-height: calc(2 * var(--default-clickable-area));
}
</style>
