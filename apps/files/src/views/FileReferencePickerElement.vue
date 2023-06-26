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
	<div ref="picker" class="reference-file-picker" />
</template>

<script>
import { FilePickerType } from '@nextcloud/dialogs'
import { generateUrl } from '@nextcloud/router'
export default {
	name: 'FileReferencePickerElement',
	components: {
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
	mounted() {
		this.openFilePicker()
		window.addEventListener('click', this.onWindowClick)
	},
	beforeDestroy() {
		window.removeEventListener('click', this.onWindowClick)
	},
	methods: {
		onWindowClick(e) {
			if (e.target.tagName === 'A' && e.target.classList.contains('oc-dialog-close')) {
				this.$emit('cancel')
			}
		},
		async openFilePicker() {
			OC.dialogs.filepicker(
				t('files', 'Select file or folder to link to'),
				(file) => {
					const client = OC.Files.getClient()
					client.getFileInfo(file).then((_status, fileInfo) => {
						this.submit(fileInfo.id)
					})
				},
				false, // multiselect
				[], // mime filter
				false, // modal
				FilePickerType.Choose, // type
				'',
				{
					target: this.$refs.picker,
				},
			)
		},
		submit(fileId) {
			const fileLink = window.location.protocol + '//' + window.location.host
				+ generateUrl('/f/{fileId}', { fileId })
			this.$emit('submit', fileLink)
		},
	},
}
</script>

<style scoped lang="scss">
.reference-file-picker {
	flex-grow: 1;
	padding: 12px 16px 16px 16px;

	&:deep(.oc-dialog) {
		transform: none !important;
		box-shadow: none !important;
		flex-grow: 1 !important;
		position: static !important;
		width: 100% !important;
		height: auto !important;
		padding: 0 !important;
		max-width: initial;

		.oc-dialog-close {
			display: none;
		}

		.oc-dialog-buttonrow.onebutton.aside {
			position: absolute;
			padding: 12px 32px;
		}

		.oc-dialog-content {
			max-width: 100% !important;
		}
	}
}
</style>
