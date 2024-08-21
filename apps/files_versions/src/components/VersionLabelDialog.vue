<!--
  - SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<NcDialog :buttons="dialogButtons"
		content-classes="version-label-modal"
		is-form
		:open="open"
		size="normal"
		:name="t('files_versions', 'Name this version')"
		@update:open="$emit('update:open', $event)"
		@submit="setVersionLabel(editedVersionLabel)">
		<NcTextField ref="labelInput"
			class="version-label-modal__input"
			:label="t('files_versions', 'Version name')"
			:placeholder="t('files_versions', 'Version name')"
			:value.sync="editedVersionLabel" />

		<p class="version-label-modal__info">
			{{ t('files_versions', 'Named versions are persisted, and excluded from automatic cleanups when your storage quota is full.') }}
		</p>
	</NcDialog>
</template>

<script lang="ts">
import { t } from '@nextcloud/l10n'
import { defineComponent } from 'vue'
import svgCheck from '@mdi/svg/svg/check.svg?raw'

import NcDialog from '@nextcloud/vue/dist/Components/NcDialog.js'
import NcTextField from '@nextcloud/vue/dist/Components/NcTextField.js'

type Focusable = Vue & { focus: () => void }

export default defineComponent({
	name: 'VersionLabelDialog',
	components: {
		NcDialog,
		NcTextField,
	},
	props: {
		open: {
			type: Boolean,
			default: false,
		},
		versionLabel: {
			type: String,
			default: '',
		},
	},
	data() {
		return {
			editedVersionLabel: '',
		}
	},
	computed: {
		dialogButtons() {
			const buttons: unknown[] = []
			if (this.versionLabel.trim() === '') {
				// If there is no label just offer a cancel action that just closes the dialog
				buttons.push({
					label: t('files_versions', 'Cancel'),
				})
			} else {
				// If there is already a label set, offer to remove the version label
				buttons.push({
					label: t('files_versions', 'Remove version name'),
					type: 'error',
					nativeType: 'reset',
					callback: () => { this.setVersionLabel('') },
				})
			}
			return [
				...buttons,
				{
					label: t('files_versions', 'Save version name'),
					type: 'primary',
					nativeType: 'submit',
					icon: svgCheck,
				},
			]
		},
	},
	watch: {
		versionLabel: {
			immediate: true,
			handler(label) {
				this.editedVersionLabel = label ?? ''
			},
		},
		open: {
			immediate: true,
			handler(open) {
				if (open) {
					this.$nextTick(() => (this.$refs.labelInput as Focusable).focus())
				}
				this.editedVersionLabel = this.versionLabel
			},
		},
	},
	methods: {
		setVersionLabel(label: string) {
			this.$emit('label-update', label)
		},

		t,
	},
})
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
