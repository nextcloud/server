<!--
  - @copyright Copyright (c) 2023 Julius Härtl <jus@bitgrid.net>
  -
  - @author Julius Härtl <jus@bitgrid.net>
  -
  - @license GNU AGPL version 3 or any later version
  -
  - This program is free software: you can redistribute it and/or modify
  - it under the terms of the GNU Affero General Public License as
  - published by the Free Software Foundation, either version 3 of the
  - License, or (at your option) any later version.
  -
  - This program is distributed in the hope that it will be useful,
  - but WITHOUT ANY WARRANTY; without even the implied warranty of
  - MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
  - GNU Affero General Public License for more details.
  -
  - You should have received a copy of the GNU Affero General Public License
  - along with this program. If not, see <http://www.gnu.org/licenses/>.
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
				allowPickDirectory: false,
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
				buttons.push({
					label: t('files', 'Choose file'),
					type: 'tertiary' as never,
					callback: this.onClose,
				})
			} else {
				buttons.push({
					label: t('files', 'Choose {file}', { file: selected[0].basename }),
					type: 'primary',
					callback: this.onClose,
				})
			}
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
