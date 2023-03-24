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
	<RecycleScroller ref="recycleScroller"
		class="files-list"
		key-field="source"
		:items="nodes"
		:item-size="55"
		:table-mode="true"
		item-class="files-list__row"
		item-tag="tr"
		list-class="files-list__body"
		list-tag="tbody"
		role="table">
		<template #default="{ item }">
			<FileEntry :is-size-available="isSizeAvailable" :source="item" />
		</template>

		<!-- <template #before>
			<caption v-show="false" class="files-list__caption">
				{{ summary }}
			</caption>
		</template> -->

		<template #before>
			<FilesListHeader :nodes="nodes" :is-size-available="isSizeAvailable" />
		</template>
	</RecycleScroller>
</template>

<script lang="ts">
import { RecycleScroller } from 'vue-virtual-scroller'
import { translate, translatePlural } from '@nextcloud/l10n'
import Vue from 'vue'

import FileEntry from './FileEntry.vue'
import FilesListHeader from './FilesListHeader.vue'

export default Vue.extend({
	name: 'FilesListVirtual',

	components: {
		RecycleScroller,
		FileEntry,
		FilesListHeader,
	},

	props: {
		nodes: {
			type: Array,
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
		isSizeAvailable() {
			return this.nodes.some(node => node.attributes.size !== undefined)
		},
	},

	mounted() {
		// Make the root recycle scroller a table for proper semantics
		this.$el.querySelector('.vue-recycle-scroller__slot').setAttribute('role', 'thead')
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
	--cell-margin: 14px;

	--checkbox-padding: calc((var(--row-height) - var(--checkbox-size)) / 2);
	--checkbox-size: 24px;
	--clickable-area: 44px;
	--icon-preview-size: 32px;

	display: block;
	overflow: auto;
	height: 100%;

	&::v-deep {
		tbody, .vue-recycle-scroller__slot {
			display: flex;
			flex-direction: column;
			width: 100%;
			// Necessary for virtual scrolling absolute
			position: relative;
		}

		// Table header
		.vue-recycle-scroller__slot {
			// Pinned on top when scrolling
			position: sticky;
			z-index: 10;
			top: 0;
			height: var(--row-height);
			background-color: var(--color-main-background);
		}

		tr {
			position: absolute;
			display: flex;
			align-items: center;
			width: 100%;
			border-bottom: 1px solid var(--color-border);

			&:hover,
			&:focus,
			&:active {
				background-color: var(--color-background-dark);
			}
		}
	}
}

</style>
