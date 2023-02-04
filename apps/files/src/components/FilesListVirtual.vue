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
	<VirtualList class="files-list"
		:data-component="FileEntry"
		:data-key="getFileId"
		:data-sources="nodes"
		:estimate-size="55"
		:table-mode="true"
		item-class="files-list__row"
		wrap-class="files-list__body">
		<template #before>
			<caption v-show="false" class="files-list__caption">
				{{ summary }}
			</caption>
		</template>

		<template #header>
			<FilesListHeader :nodes="nodes" />
		</template>
	</VirtualList>
</template>

<script lang="ts">
import { Folder, File } from '@nextcloud/files'
import { translate, translatePlural } from '@nextcloud/l10n'
import VirtualList from 'vue-virtual-scroll-list'
import Vue from 'vue'

import FileEntry from './FileEntry.vue'
import FilesListHeader from './FilesListHeader.vue'

export default Vue.extend({
	name: 'FilesListVirtual',

	components: {
		VirtualList,
		FilesListHeader,
	},

	props: {
		nodes: {
			type: [File, Folder],
			required: true,
		},
	},

	data() {
		return {
			FileEntry,
		}
	},

	computed: {
		files() {
			return this.nodes.filter(node => node.type === 'file')
		},

		summaryFile() {
			const count = this.files.length
			return translatePlural('files', '{count} file', '{count} files', count, { count })
		},
		summaryFolder() {
			const count = this.nodes.length - this.files.length
			return translatePlural('files', '{count} folder', '{count} folders', count, { count })
		},
		summary() {
			return translate('files', '{summaryFile} and {summaryFolder}', this)
		},
	},

	methods: {
		getFileId(node) {
			return node.attributes.fileid
		},

		t: translate,
	},
})
</script>

<style scoped lang="scss">
.files-list {
	--row-height: 55px;
	--checkbox-padding: calc((var(--row-height) - var(--checkbox-size)) / 2);
	--checkbox-size: 24px;
	--clickable-area: 44px;
	--icon-preview-size: 32px;

	display: block;
	overflow: auto;
	height: 100%;

	&::v-deep {
		tbody, thead, tfoot {
			display: flex;
			flex-direction: column;
			width: 100%;
		}

		thead {
			// Pinned on top when scrolling
			position: sticky;
			z-index: 10;
			top: 0;
			background-color: var(--color-main-background);
		}

		thead, .files-list__row {
			border-bottom: 1px solid var(--color-border);
		}
	}
}
</style>
