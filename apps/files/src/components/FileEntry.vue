<!--
  - @copyright Copyright (c) 2019 Gary Kim <gary@garykim.dev>
  -
  - @author Gary Kim <gary@garykim.dev>
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
  - MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  - GNU Affero General Public License for more details.
  -
  - You should have received a copy of the GNU Affero General Public License
  - along with this program. If not, see <http://www.gnu.org/licenses/>.
  -
  -->
<template>
	<Fragment>
		<td class="files-list__row-checkbox">
			<NcCheckboxRadioSwitch :aria-label="t('files', 'Select the row for {displayName}', { displayName })"
				:checked.sync="selectedFiles"
				:value="fileid.toString()"
				name="selectedFiles" />
		</td>

		<!-- Icon or preview -->
		<td class="files-list__row-icon">
			<FolderIcon v-if="source.type === 'folder'" />
		</td>

		<!-- Link to file and -->
		<td class="files-list__row-name">
			<a v-bind="linkTo">
				{{ displayName }}
			</a>
		</td>
	</Fragment>
</template>

<script lang="ts">
import { Folder, File } from '@nextcloud/files'
import { Fragment } from 'vue-fragment'
import { join } from 'path'
import { translate } from '@nextcloud/l10n'
import FolderIcon from 'vue-material-design-icons/Folder.vue'
import NcCheckboxRadioSwitch from '@nextcloud/vue/dist/Components/NcCheckboxRadioSwitch.js'
import Vue from 'vue'

import logger from '../logger'
import { useSelectionStore } from '../store/selection'
import { useFilesStore } from '../store/files'

export default Vue.extend({
	name: 'FileEntry',

	components: {
		FolderIcon,
		Fragment,
		NcCheckboxRadioSwitch,
	},

	props: {
		index: {
			type: Number,
			required: true,
		},
		source: {
			type: [File, Folder],
			required: true,
		},
	},

	setup() {
		const filesStore = useFilesStore()
		const selectionStore = useSelectionStore()
		return {
			filesStore,
			selectionStore,
		}
	},

	computed: {
		dir() {
			// Remove any trailing slash but leave root slash
			return (this.$route?.query?.dir || '/').replace(/^(.+)\/$/, '$1')
		},

		fileid() {
			return this.source.attributes.fileid
		},
		displayName() {
			return this.source.attributes.displayName
				|| this.source.basename
		},

		linkTo() {
			if (this.source.type === 'folder') {
				const to = { ...this.$route, query: { dir: join(this.dir, this.source.basename) } }
				return {
					is: 'router-link',
					title: this.t('files', 'Open folder {name}', { name: this.displayName }),
					to,
				}
			}
			return {
				href: this.source.source,
				// TODO: Use first action title ?
				title: this.t('files', 'Download file {name}', { name: this.displayName }),
			}
		},

		selectedFiles: {
			get() {
				return this.selectionStore.selected
			},
			set(selection) {
				logger.debug('Added node to selection', { selection })
				this.selectionStore.set(selection)
			},
		},
	},

	methods: {
		/**
		 * Get a cached note from the store
		 *
		 * @param {number} fileId the file id to get
		 * @return {Folder|File}
		 */
		 getNode(fileId) {
			return this.filesStore.getNode(fileId)
		},

		t: translate,
	},
})
</script>

<style scoped lang="scss">
@import '../mixins/fileslist-row.scss'
</style>
