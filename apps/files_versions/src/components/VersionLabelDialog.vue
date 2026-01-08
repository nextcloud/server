<!--
  - SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<NcDialog
		:buttons="dialogButtons"
		content-classes="version-label-modal"
		is-form
		:open="open"
		size="normal"
		:name="t('files_versions', 'Name this version')"
		@update:open="$emit('update:open', $event)"
		@submit="setVersionLabel(internalLabel)">
		<NcTextField
			ref="labelInput"
			v-model="internalLabel"
			class="version-label-modal__input"
			:label="t('files_versions', 'Version name')"
			:placeholder="t('files_versions', 'Version name')" />

		<p class="version-label-modal__info">
			{{ t('files_versions', 'Named versions are persisted, and excluded from automatic cleanups when your storage quota is full.') }}
		</p>
	</NcDialog>
</template>

<script lang="ts" setup>
import svgCheck from '@mdi/svg/svg/check.svg?raw'
import { t } from '@nextcloud/l10n'
import { computed, nextTick, ref, useTemplateRef, watchEffect } from 'vue'
import NcDialog from '@nextcloud/vue/components/NcDialog'
import NcTextField from '@nextcloud/vue/components/NcTextField'

const props = defineProps({
	open: {
		type: Boolean,
		default: false,
	},

	label: {
		type: String,
		default: '',
	},
})

const emit = defineEmits(['update:open', 'update:label'])

const labelInput = useTemplateRef('labelInput')

const internalLabel = ref('')

const dialogButtons = computed(() => {
	const buttons: unknown[] = []
	if (props.label.trim() === '') {
		// If there is no label just offer a cancel action that just closes the dialog
		buttons.push({
			label: t('files_versions', 'Cancel'),
		})
	} else {
		// If there is already a label set, offer to remove the version label
		buttons.push({
			label: t('files_versions', 'Remove version name'),
			type: 'reset',
			variant: 'error',
			callback: () => { setVersionLabel('') },
		})
	}
	return [
		...buttons,
		{
			label: t('files_versions', 'Save version name'),
			icon: svgCheck,
			type: 'submit',
			variant: 'primary',
		},
	]
})

watchEffect(() => {
	internalLabel.value = props.label ?? ''
})

watchEffect(() => {
	if (props.open) {
		nextTick(() => labelInput.value?.focus())
	}
	internalLabel.value = props.label
})

/**
 *
 * @param label - The new label
 */
function setVersionLabel(label: string) {
	emit('update:label', label)
}
</script>

<style scoped lang="scss">
.version-label-modal {
	&__info {
		color: var(--color-text-maxcontrast);
		margin-block: calc(3 * var(--default-grid-baseline));
	}

	&__input {
		margin-block-start: calc(2 * var(--default-grid-baseline));
	}
}
</style>
