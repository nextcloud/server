<!--
  - @copyright 2024 Christopher Ng <chrng8@gmail.com>
  -
  - @author Christopher Ng <chrng8@gmail.com>
  -
  - @license AGPL-3.0-or-later
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
  -
-->

<template>
	<NcDialog :name="name"
		out-transition
		size="normal"
		:can-close="false">
		<div class="dialog__content">
			<NcNoteCard class="dialog__note" type="info">{{ message }}</NcNoteCard>
			<ul class="dialog__list">
				<li v-for="node in nodes" :key="node.fileid">
					{{ node.attributes.displayName }}
				</li>
			</ul>
		</div>
		<template #actions>
			<NcButton type="tertiary" @click="cancel">
				{{ t('files_trashbin', 'Cancel') }}
			</NcButton>
			<NcButton type="secondary" @click="skip">
				{{ t('files_trashbin', 'Skip') }}
			</NcButton>
			<NcButton type="primary" @click="confirm">
				{{ t('files_trashbin', 'Confirm') }}
			</NcButton>
		</template>
	</NcDialog>
</template>

<script lang="ts">
import type { Node } from '@nextcloud/files'
import type { PropType } from 'vue'

import { defineComponent } from 'vue'
import { translate as t } from '@nextcloud/l10n'

import NcButton from '@nextcloud/vue/dist/Components/NcButton.js'
import NcDialog from '@nextcloud/vue/dist/Components/NcDialog.js'
import NcNoteCard from '@nextcloud/vue/dist/Components/NcNoteCard.js'

import { parseOriginalLocation, RestoreParents } from '../utils.ts'

export default defineComponent({
	name: 'RestoreParentsDialog',

	components: {
		NcButton,
		NcDialog,
		NcNoteCard,
	},

	props: {
		node: {
			type: Object as PropType<Node>,
			default: null,
		},

		nodes: {
			type: Array as PropType<Node[]>,
			default: () => [],
			validator: (value: Node[]) => value?.length > 0,
		},
	},

	data() {
		return {
		}
	},

	computed: {
		name() {
			return n(
				'files_trashbin',
				'Confirm restoration of parent folder',
				'Confirm restoration of parent folders',
				this.nodes.length,
			)
		},

		message() {
			return n(
				'files_trashbin',
				'{name} was originally in {location}. You may restore the parent folder listed below or skip parent folder resoration and restore {name} directly to All files.',
				'{name} was originally in {location}. You may restore the parent folders listed below or skip parent folder resoration and restore {name} directly to All files.',
				this.nodes.length,
				{
					name: this.node.attributes.displayName,
					location: parseOriginalLocation(this.node),
				},
			)
		},
	},

	methods: {
		t,
		confirm(): Promise<void> {
			this.$emit('close', RestoreParents.Confirm)
		},
		skip(): Promise<void> {
			this.$emit('close', RestoreParents.Skip)
		},
		cancel(): Promise<void> {
			this.$emit('close', RestoreParents.Cancel)
		},
	},
})
</script>

<style lang="scss" scoped>
.dialog {
	&__content {
		padding: 0 16px;
	}

	&__note {
		margin-top: 0 !important;
	}

	&__list {
		list-style-type: disc;
		list-style-position: inside;
		display: flex;
		flex-direction: column;
		gap: 4px 0;
	}
}
</style>
