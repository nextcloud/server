<!--
  - @copyright Copyright (c) 2023 Julius Härtl <jus@bitgrid.net>
  -
  - @author Julius Härtl <jus@bitgrid.net>
  - @author Ferdinand Thiessen <opensource@fthiessen.de>
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
	<FilePicker allow-pick-directory
		:buttons="buttons"
		:container="null"
		:name="t('files', 'Select file or folder to link to')"
		:multiselect="false"
		@close="onClose" />
</template>

<script>
import { FilePickerVue } from '@nextcloud/dialogs'
import { generateUrl } from '@nextcloud/router'
import logger from '../logger.js'

export default {
	name: 'FileReferencePickerElement',
	components: {
		FilePicker: FilePickerVue,
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

	setup() {
		// Buttons to show
		const buttons = [
			{
				label: t('files', 'Choose'),
				type: 'primary',
				callback: (nodes) => {
					logger.debug('FileReferencePicker - Nodes picked', { nodes })
				},
			},
		]

		return {
			buttons,
		}
	},

	methods: {
		onClose(selectedNodes) {
			if (!selectedNodes || selectedNodes.length === 0) {
				this.$emit('cancel')
			} else {
				const fileLink = `${window.location.protocol}//${window.location.host}${generateUrl('/f/{fileId}', { fileId: selectedNodes[0].fileid })}`
				this.$emit('submit', fileLink)
			}
		},
	},
}
</script>
