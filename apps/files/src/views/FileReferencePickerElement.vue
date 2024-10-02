<!--
  - SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<div :id="containerId">
		<FilePicker v-bind="filepickerOptions" @close="onClose" />
	</div>
</template>

<script lang="ts">
import type { Node as NcNode } from '@nextcloud/files'
import type { IFilePickerButton } from '@nextcloud/dialogs'

import { FilePickerVue as FilePicker } from '@nextcloud/dialogs/filepicker.js'
import { translate as t } from '@nextcloud/l10n'
import { generateUrl } from '@nextcloud/router'
import { defineComponent } from 'vue'

export default defineComponent({
	name: 'FileReferencePickerElement',
	components: {
		FilePicker,
	},
	props: {
		providerId: {
			type: String,
			required: true,
		},
		accessible: {
			type: Boolean,
			default: false,
		},
	},
	computed: {
		containerId() {
			return `filepicker-${Math.random().toString(36).slice(7)}`
		},
		filepickerOptions() {
			return {
				allowPickDirectory: true,
				buttons: this.buttonFactory,
				container: `#${this.containerId}`,
				multiselect: false,
				name: t('files', 'Select file or folder to link to'),
			}
		},
	},
	methods: {
		t,

		buttonFactory(selected: NcNode[]): IFilePickerButton[] {
			const buttons = [] as IFilePickerButton[]
			if (selected.length === 0) {
				return []
			}
			const node = selected.at(0)
			if (node.path === '/') {
				return [] // Do not allow selecting the users root folder
			}
			buttons.push({
				label: t('files', 'Choose {file}', { file: node.displayname }),
				type: 'primary',
				callback: this.onClose,
			})
			return buttons
		},

		onClose(nodes?: NcNode[]) {
			if (nodes === undefined || nodes.length === 0) {
				this.$emit('cancel')
			} else {
				this.onSubmit(nodes[0])
			}
		},

		onSubmit(node: NcNode) {
			const url = new URL(window.location.href)
			url.pathname = generateUrl('/f/{fileId}', { fileId: node.fileid! })
			url.search = ''
			this.$emit('submit', url.href)
		},
	},
})
</script>
